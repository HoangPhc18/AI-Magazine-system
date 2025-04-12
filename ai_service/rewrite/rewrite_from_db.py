#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Script to fetch articles from database and rewrite them using Gemma2
Can be run automatically via cron job:
    */30 * * * * cd /path/to/ai_service/rewrite && python rewrite_from_db.py --auto
"""

import os
import sys
import json
import time
import argparse
import requests
from datetime import datetime
import mysql.connector
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Process command line arguments
def parse_args():
    parser = argparse.ArgumentParser(description='Rewrite articles from database.')
    parser.add_argument('--auto', action='store_true', help='Run in automatic mode without prompts')
    parser.add_argument('--limit', type=int, default=3, help='Limit number of articles to process (default: 3)')
    parser.add_argument('--delete', action='store_true', help='Delete original articles after rewriting')
    parser.add_argument('--log', type=str, default='rewriter.log', help='Log file (default: rewriter.log)')
    parser.add_argument('--ids', type=str, help='Comma-separated list of article IDs to rewrite (e.g. "1,2,3")')
    return parser.parse_args()

# Setup logging
def setup_logging(log_file):
    import logging
    logging.basicConfig(
        filename=log_file,
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    return logging.getLogger('rewriter')

# Database configuration
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "localhost"),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "aimagazinedb"),
    "port": int(os.getenv("DB_PORT", "3306"))
}

# Ollama API configuration
OLLAMA_URL = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "gemma2")

def connect_to_database():
    """Connect to the database"""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if connection.is_connected():
            print(f"Connected to MySQL database: {DB_CONFIG['database']}")
            return connection
    except Exception as e:
        print(f"Error connecting to database: {e}")
        return None

def get_unprocessed_articles(connection, limit=3, article_ids=None):
    """Get articles that have not been rewritten yet
    
    Args:
        connection: Database connection
        limit: Maximum number of articles to retrieve (default: 3)
        article_ids: List of specific article IDs to retrieve
    
    Returns:
        List of articles to be rewritten
    """
    if not connection:
        return []
        
    try:
        cursor = connection.cursor(dictionary=True)
        
        if article_ids:
            # Query to get specific articles by ID
            placeholders = ', '.join(['%s'] * len(article_ids))
            query = f"""
            SELECT id, title, content, source_name, source_url, category
            FROM articles
            WHERE id IN ({placeholders})
            AND content IS NOT NULL 
            AND LENGTH(content) > 100
            LIMIT %s
            """
            
            # Add limit as the last parameter
            params = article_ids + [limit]
            cursor.execute(query, params)
        else:
            # Query to get articles that haven't been rewritten yet
            query = """
            SELECT id, title, content, source_name, source_url, category
            FROM articles
            WHERE content IS NOT NULL 
            AND LENGTH(content) > 100
            AND is_ai_rewritten = 0
            ORDER BY created_at DESC
            LIMIT %s
            """
            
            cursor.execute(query, (limit,))
            
        articles = cursor.fetchall()
        print(f"Retrieved {len(articles)} articles for rewriting")
        
        return articles
    except Exception as e:
        print(f"Error retrieving articles: {e}")
        return []
    finally:
        if 'cursor' in locals():
            cursor.close()

def rewrite_with_gemma2(text, word_limit=True):
    """Rewrite text using Gemma2 via Ollama API"""
    if not text:
        print("No text provided for rewriting")
        return None, 0
        
    # Create prompt based on word limit
    if word_limit:
        prompt = f"""Hãy viết lại bài viết sau đây thành một đoạn văn ngắn gọn, tập trung vào thông tin quan trọng:

{text}
"""
    else:
        prompt = f"""Hãy viết lại bài viết sau đây với phong cách mạch lạc và hấp dẫn, giữ nguyên ý nghĩa chính:

{text}
"""
    
    # Prepare API request
    url = f"{OLLAMA_URL}/api/generate"
    payload = {
        "model": OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False,
        "options": {
            "temperature": 0.7
        }
    }
    
    # Track start time
    start_time = time.time()
    
    try:
        # Call Ollama API
        response = requests.post(url, json=payload)
        
        if response.status_code != 200:
            print(f"API error: {response.status_code} {response.text}")
            return None, 0
        
        # Parse response
        result = response.json()
        elapsed_time = time.time() - start_time
        
        return result.get("response", ""), elapsed_time
    
    except Exception as e:
        print(f"Error calling Gemma2 API: {e}")
        return None, 0

def save_rewritten_article(connection, article_id, original_content, rewritten_content, metadata):
    """Save rewritten article to the rewritten_articles table"""
    if not connection:
        return False
        
    try:
        cursor = connection.cursor()
        
        # First get the original article data
        get_query = """
        SELECT title, slug, source_name, category
        FROM articles 
        WHERE id = %s
        """
        cursor.execute(get_query, (article_id,))
        original_article = cursor.fetchone()
        
        if not original_article:
            print(f"Original article with ID {article_id} not found")
            return False
            
        # Create a new title for the rewritten article
        original_title = original_article[0]
        new_title = f"{original_title}"
        
        # Create a new slug based on the new title
        new_slug = f"{original_article[1]}-ai-rewrite"
        
        # Prepare metadata as JSON
        metadata_json = json.dumps(metadata)
        meta_description = f"AI rewritten version of the article: {original_title}"
        
        # Convert category to category_id (use 1 as default if not found)
        category_id = 1  # Default category ID
        
        # Insert query for rewritten article
        query = """
        INSERT INTO rewritten_articles 
        (title, slug, content, meta_description, 
         user_id, category_id, original_article_id, ai_generated, 
         status, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
        """
        
        # Execute query with data from original article
        cursor.execute(query, (
            new_title,                    # title
            new_slug,                     # slug
            rewritten_content,            # content
            meta_description,             # meta_description
            1,                            # user_id (admin)
            category_id,                  # category_id
            article_id,                   # original_article_id
            1,                            # ai_generated
            'pending'                     # status
        ))
        
        # Commit the transaction
        connection.commit()
        
        print(f"Saved rewritten article to rewritten_articles table (ID: {cursor.lastrowid})")
        
        # Also update the original article to mark it as rewritten
        update_query = """
        UPDATE articles
        SET is_ai_rewritten = 1,
            ai_rewritten_content = %s,
            meta_data = %s,
            updated_at = NOW()
        WHERE id = %s
        """
        
        cursor.execute(update_query, (
            rewritten_content,
            metadata_json,
            article_id
        ))
        connection.commit()
        
        print(f"Updated original article to mark as rewritten (ID: {article_id})")
        
        return True
    except Exception as e:
        print(f"Error saving rewritten article: {e}")
        return False
    finally:
        if 'cursor' in locals():
            cursor.close()

def delete_original_article(connection, article_id):
    """Delete the original article from the database after rewriting"""
    if not connection:
        return False
        
    try:
        cursor = connection.cursor()
        
        # Delete query
        query = "DELETE FROM articles WHERE id = %s"
        
        # Execute query
        cursor.execute(query, (article_id,))
        
        # Commit the transaction
        connection.commit()
        
        if cursor.rowcount > 0:
            print(f"Deleted original article (ID: {article_id}) from database")
            return True
        else:
            print(f"No article with ID {article_id} found for deletion")
            return False
    except Exception as e:
        print(f"Error deleting original article: {e}")
        return False
    finally:
        if 'cursor' in locals():
            cursor.close()

def process_article(connection, article):
    """Process a single article - rewrite and save"""
    if not article or not article.get('content'):
        print("Article has no content")
        return False
    
    article_id = article['id']
    title = article['title']
    content = article['content']
    
    print(f"\nProcessing article: {title}")
    print(f"Original content length: {len(content)} chars, {len(content.split())} words")
    
    # Rewrite content
    rewritten_content, processing_time = rewrite_with_gemma2(content)
    
    if not rewritten_content:
        print("Failed to rewrite article")
        return False
    
    # Calculate word counts
    original_word_count = len(content.split())
    rewritten_word_count = len(rewritten_content.split())
    
    print(f"Rewritten content length: {len(rewritten_content)} chars, {rewritten_word_count} words")
    print(f"Processing time: {processing_time:.2f} seconds")
    
    # Check if word count is within target range
    in_target_range = 50 <= rewritten_word_count <= 200
    if in_target_range:
        print("✅ Word count within target range (50-200 words)")
    else:
        print(f"⚠️ Word count outside target range: {rewritten_word_count} words")
    
    # Prepare metadata
    metadata = {
        "original_word_count": original_word_count,
        "rewritten_word_count": rewritten_word_count,
        "processing_time": processing_time,
        "provider": "ollama",
        "model": OLLAMA_MODEL,
        "in_target_range": in_target_range,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    }
    
    # Display preview
    print("\nPreview of rewritten content:")
    print(rewritten_content[:300] + "..." if len(rewritten_content) > 300 else rewritten_content)
    
    # Save to database
    print("\nSaving to database...")
    save_success = save_rewritten_article(connection, article_id, content, rewritten_content, metadata)
    
    if save_success:
        # Automatically delete the original article after successful rewriting
        print("Deleting original article from database...")
        delete_success = delete_original_article(connection, article_id)
        if not delete_success:
            print("Warning: Original article could not be deleted")
    
    return save_success

def main():
    """Main function"""
    # Parse command line arguments
    args = parse_args()
    
    # Setup logging
    logger = setup_logging(args.log)
    logger.info("=== ARTICLE REWRITING FROM DATABASE ===")
    
    print("=== ARTICLE REWRITING FROM DATABASE ===")
    
    # Connect to database
    connection = connect_to_database()
    if not connection:
        error_msg = "Failed to connect to database"
        logger.error(error_msg)
        print(error_msg)
        return
    
    # Automatically set to delete original articles if specified
    auto_delete = True if args.auto or args.delete else False
    print(f"Auto-delete mode: {'Enabled' if auto_delete else 'Disabled'}")
    logger.info(f"Auto-delete mode: {'Enabled' if auto_delete else 'Disabled'}")
    
    # Set limit of articles per run (maximum 3)
    limit = min(args.limit, 3)
    print(f"Processing limit: {limit} articles")
    logger.info(f"Processing limit: {limit} articles")
    
    # Process specific article IDs if provided
    article_ids = None
    if args.ids:
        article_ids = args.ids.split(',')
        print(f"Rewriting specific article IDs: {args.ids}")
        logger.info(f"Rewriting specific article IDs: {args.ids}")
    
    # Get articles to rewrite
    articles = get_unprocessed_articles(connection, limit=limit, article_ids=article_ids)
    
    if not articles:
        msg = "No articles found for rewriting"
        logger.info(msg)
        print(msg)
        connection.close()
        return
    
    # Process each article
    successful = 0
    for idx, article in enumerate(articles, 1):
        article_info = f"--- Article {idx}/{len(articles)} (ID: {article['id']}) ---"
        print(f"\n{article_info}")
        logger.info(article_info)
        
        # Process the article
        save_success = process_article(connection, article)
        
        # If auto-delete is enabled and not handled in process_article
        # (keeping this as a backup in case process_article implementation changes)
        if auto_delete and save_success:
            delete_msg = "Auto-delete enabled, ensuring original article is deleted..."
            print(delete_msg)
            logger.info(delete_msg)
            delete_original_article(connection, article['id'])
        
        if save_success:
            successful += 1
    
    # Summary
    summary = "\n=== SUMMARY ==="
    print(summary)
    logger.info(summary)
    
    processed_msg = f"Processed {len(articles)} articles"
    print(processed_msg)
    logger.info(processed_msg)
    
    success_msg = f"Successfully rewritten and saved: {successful}"
    print(success_msg)
    logger.info(success_msg)
    
    # Close database connection
    connection.close()
    close_msg = "Database connection closed"
    print(close_msg)
    logger.info(close_msg)
    
    return successful

if __name__ == "__main__":
    main() 