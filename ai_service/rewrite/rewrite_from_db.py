#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Script to fetch articles from database and rewrite them using Gemini 1.5 Flash
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

# Import module config
from config import get_config, reload_config

# Process command line arguments
def parse_args():
    """Parse command line arguments"""
    parser = argparse.ArgumentParser(description='Rewrite articles from database using AI')
    parser.add_argument('--limit', type=int, default=3, help='Limit number of articles to process (default: 3)')
    parser.add_argument('--article-id', type=int, help='Specific article ID to rewrite')
    parser.add_argument('--article-ids', type=str, help='Comma-separated list of article IDs to rewrite (e.g., "1,2,3")')
    parser.add_argument('--auto-delete', action='store_true', help='Delete original articles after rewriting')
    parser.add_argument('--log-file', type=str, default='rewriter.log', help='Log file path')
    parser.add_argument('--auto', action='store_true', help='Run in automatic mode with default settings')
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

# Tải cấu hình 
config = get_config()

# Database configuration
DB_CONFIG = {
    "host": config.get("DB_HOST", "host.docker.internal"),
    "user": config.get("DB_USER", "root"),
    "password": config.get("DB_PASSWORD", ""),
    "database": config.get("DB_NAME", "aimagazinedb"),
    "port": config.get("DB_PORT", 3306)
}

# AI Model configuration
GEMINI_API_KEY = config.get("GEMINI_API_KEY", "")
GEMINI_MODEL = config.get("GEMINI_MODEL", "gemini-1.5-flash-latest")

# Fallback Ollama configuration
OLLAMA_URL = config.get("OLLAMA_BASE_URL", "http://host.docker.internal:11434")
OLLAMA_MODEL = config.get("OLLAMA_MODEL", "gemma2:latest")

def connect_to_database():
    """Connect to the database"""
    try:
        # Tải lại cấu hình để có thông tin mới nhất
        current_config = get_config()
        
        # Tạo cấu hình kết nối mới
        db_config = {
            "host": current_config.get("DB_HOST", "host.docker.internal"),
            "user": current_config.get("DB_USER", "root"),
            "password": current_config.get("DB_PASSWORD", ""),
            "database": current_config.get("DB_NAME", "aimagazinedb"),
            "port": current_config.get("DB_PORT", 3306)
        }
        
        connection = mysql.connector.connect(**db_config)
        if connection.is_connected():
            print(f"Connected to MySQL database: {db_config['database']} at {db_config['host']}")
            return connection
    except Exception as e:
        print(f"Error connecting to database: {e}")
        return None

def get_unprocessed_articles(connection, limit=3, article_ids=None):
    """Get articles that have not been rewritten yet or specific articles by ID
    
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
        
        if article_ids and len(article_ids) > 0:
            # Convert all article_ids to integers to ensure they're valid
            try:
                article_ids = [int(aid) for aid in article_ids]
            except ValueError:
                print(f"Error: Invalid article ID(s) provided. Must be integers.")
                return []
                
            # Create placeholders for the IN clause
            placeholders = ', '.join(['%s'] * len(article_ids))
            
            # Query to get specific articles by ID regardless of is_ai_rewritten status
            query = f"""
            SELECT id, title, content, source_name, source_url, category, subcategory_id,
                   DATE_FORMAT(created_at, '%Y-%m-%d') as date
            FROM articles
            WHERE id IN ({placeholders})
            AND content IS NOT NULL 
            AND LENGTH(content) > 100
            LIMIT %s
            """
            
            # Add limit as the last parameter
            params = article_ids + [limit]
            cursor.execute(query, params)
            
            articles = cursor.fetchall()
            print(f"Retrieved {len(articles)} articles by ID")
            
            if len(articles) < len(article_ids):
                missing_ids = set(article_ids) - set(a['id'] for a in articles)
                print(f"Warning: Could not find articles with IDs: {missing_ids}")
                
            return articles
        else:
            # Query to get articles that haven't been rewritten yet
            query = """
            SELECT id, title, content, source_name, source_url, category, subcategory_id,
                   DATE_FORMAT(created_at, '%Y-%m-%d') as date
            FROM articles
            WHERE content IS NOT NULL 
            AND LENGTH(content) > 100
            AND is_ai_rewritten = 0
            ORDER BY id ASC
            LIMIT %s
            """
            
            cursor.execute(query, (limit,))
            
            articles = cursor.fetchall()
            print(f"Retrieved {len(articles)} unprocessed articles")
            return articles
    except Exception as e:
        print(f"Error retrieving articles: {e}")
        return []
    finally:
        if 'cursor' in locals():
            cursor.close()

def rewrite_with_gemini(text, word_limit=True):
    """Rewrite text using Gemini API"""
    if not text:
        print("No text provided for rewriting")
        return None, 0
        
    # Tải lại cấu hình để có thông tin mới nhất
    current_config = get_config()
    gemini_api_key = current_config.get("GEMINI_API_KEY", "")
    gemini_model = current_config.get("GEMINI_MODEL", "gemini-1.5-flash-latest")
        
    # Create prompt based on word limit
    if word_limit:
        prompt = f"""Hãy viết lại bài viết sau đây thành một bài viết đầy đủ thông tin, có cấu trúc rõ ràng. 
Giữ nguyên các thông tin quan trọng, ý nghĩa chính của bài viết gốc nhưng diễn đạt theo cách khác.
Chia thành các đoạn hợp lý, đảm bảo bài viết có độ dài từ 500-1000 từ:

{text}
"""
    else:
        prompt = f"""Hãy viết lại bài viết sau đây thành một bài viết có cấu trúc rõ ràng, mạch lạc và hấp dẫn.
Giữ nguyên các thông tin quan trọng, ý nghĩa chính của bài viết gốc nhưng diễn đạt theo cách khác.
Chia thành các đoạn hợp lý, đảm bảo bài viết có độ dài vừa phải:

{text}
"""
    
    # Track start time
    start_time = time.time()
    
    try:
        # Import Google Generative AI
        try:
            import google.generativeai as genai
            genai.configure(api_key=gemini_api_key)
        except ImportError:
            print("Error: Google Generative AI library not installed.")
            print("Please install with: pip install google-generativeai")
            return None, 0
            
        # Create a generative model
        model = genai.GenerativeModel(
            model_name=gemini_model,
            generation_config={
                "temperature": 0.7,
                "max_output_tokens": 8000,  # Tăng số lượng token tối đa cho bài viết dài hơn
            }
        )
        
        # Generate content
        response = model.generate_content(prompt)
        
        # Calculate elapsed time
        elapsed_time = time.time() - start_time
        
        # Return the generated text
        if response and hasattr(response, 'text'):
            return response.text, elapsed_time
        else:
            print("Error: No text returned from Gemini API")
            return None, elapsed_time
    
    except Exception as e:
        elapsed_time = time.time() - start_time
        print(f"Error with Gemini API: {e}")
        print("Falling back to Ollama API...")
        
        # Try with Ollama as fallback
        return rewrite_with_ollama(text, word_limit), elapsed_time

def rewrite_with_ollama(text, word_limit=True):
    """Rewrite text using Ollama API as fallback"""
    if not text:
        return None
        
    # Tải lại cấu hình để có thông tin mới nhất
    current_config = get_config()
    ollama_url = current_config.get("OLLAMA_BASE_URL", "http://host.docker.internal:11434")
    ollama_model = current_config.get("OLLAMA_MODEL", "gemma2:latest")
    
    # Create prompt based on word limit
    if word_limit:
        prompt = f"""Hãy viết lại bài viết sau đây thành một bài viết đầy đủ thông tin, có cấu trúc rõ ràng. 
Giữ nguyên các thông tin quan trọng, ý nghĩa chính của bài viết gốc nhưng diễn đạt theo cách khác.
Chia thành các đoạn hợp lý, đảm bảo bài viết có độ dài từ 500-1000 từ:

{text}
"""
    else:
        prompt = f"""Hãy viết lại bài viết sau đây thành một bài viết có cấu trúc rõ ràng, mạch lạc và hấp dẫn.
Giữ nguyên các thông tin quan trọng, ý nghĩa chính của bài viết gốc nhưng diễn đạt theo cách khác.
Chia thành các đoạn hợp lý, đảm bảo bài viết có độ dài vừa phải:

{text}
"""
    
    # Create request data
    data = {
        "model": ollama_model,
        "prompt": prompt,
        "temperature": 0.7,
        "stream": False
    }
    
    try:
        # Send request to Ollama API
        response = requests.post(
            f"{ollama_url}/api/generate",
            json=data,
            timeout=240  # Timeout = 4 minutes
        )
        
        # Check if request was successful
        if response.status_code == 200:
            result = response.json()
            if "response" in result:
                return result["response"]
            else:
                print("Error: No response field in Ollama API result")
                return None
        else:
            print(f"Error: Ollama API returned status code {response.status_code}")
            print(f"Response: {response.text}")
            return None
            
    except Exception as e:
        print(f"Error with Ollama API: {e}")
        return None

def save_rewritten_article(connection, article_id, original_content, rewritten_content, metadata):
    """Save rewritten article to the rewritten_articles table"""
    if not connection:
        return False
        
    try:
        cursor = connection.cursor()
        
        # First get the original article data
        get_query = """
        SELECT title, slug, source_name, category, subcategory_id
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
        
        # Get the original category (as string) and set default category_id = 1
        category_name = original_article[3] or "Uncategorized"
        category_id = 1  # Default category ID
        
        # Get the original subcategory_id
        subcategory_id = original_article[4]  # This will be None if not set
        print(f"Original article subcategory_id: {subcategory_id}")
        
        # Try to find the category_id from categories table if it exists
        try:
            # Check if category is already a number (direct category_id)
            if str(category_name).isdigit():
                category_id = int(category_name)
                print(f"Using category ID directly from article: {category_id}")
            else:
                # Try to find by name or slug
                category_query = """
                SELECT id FROM categories WHERE name = %s OR slug = %s LIMIT 1
                """
                cursor.execute(category_query, (category_name, category_name))
                category_result = cursor.fetchone()
                if category_result:
                    category_id = category_result[0]
                    print(f"Found category ID {category_id} for name/slug: {category_name}")
                else:
                    print(f"Could not find category for '{category_name}', using default ID: 1")
        except Exception as e:
            print(f"Could not find category ID for '{category_name}', using default: {e}")
        
        # Insert query for rewritten article
        query = """
        INSERT INTO rewritten_articles 
        (title, slug, content, meta_description, 
         user_id, category_id, subcategory_id, original_article_id, ai_generated, 
         status, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
        """
        
        # Execute query with data from original article
        cursor.execute(query, (
            new_title,                    # title
            new_slug,                     # slug
            rewritten_content,            # content
            meta_description,             # meta_description
            1,                            # user_id (admin)
            category_id,                  # category_id
            subcategory_id,               # subcategory_id
            article_id,                   # original_article_id
            1,                            # ai_generated
            'pending'                     # status
        ))
        
        # Commit the transaction
        connection.commit()
        
        print(f"Saved rewritten article to rewritten_articles table (ID: {cursor.lastrowid})")
        print(f"Using category ID: {category_id} for rewritten article")
        print(f"Using subcategory ID: {subcategory_id} for rewritten article")
        
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
    subcategory_id = article.get('subcategory_id')
    
    print(f"\nProcessing article: {title}")
    print(f"Original content length: {len(content)} chars, {len(content.split())} words")
    print(f"Original article subcategory_id: {subcategory_id}")
    
    # Rewrite content using Gemini
    rewritten_content, processing_time = rewrite_with_gemini(content)
    
    if not rewritten_content:
        print("Failed to rewrite article")
        return False
    
    # Calculate word counts
    original_word_count = len(content.split())
    rewritten_word_count = len(rewritten_content.split())
    
    print(f"Rewritten content length: {len(rewritten_content)} chars, {rewritten_word_count} words")
    print(f"Processing time: {processing_time:.2f} seconds")
    
    # Check if word count is within target range
    in_target_range = 500 <= rewritten_word_count <= 1000
    if in_target_range:
        print("✅ Word count within target range (500-1000 words)")
    else:
        print(f"⚠️ Word count outside target range: {rewritten_word_count} words (target: 500-1000)")
    
    # Prepare metadata
    metadata = {
        "original_word_count": original_word_count,
        "rewritten_word_count": rewritten_word_count,
        "processing_time": processing_time,
        "provider": "gemini",
        "model": GEMINI_MODEL,
        "in_target_range": in_target_range,
        "subcategory_id": subcategory_id,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    }
    
    # Display preview
    print("\nPreview of rewritten content:")
    print(rewritten_content[:500] + "..." if len(rewritten_content) > 500 else rewritten_content)
    
    # Save to database
    print("\nSaving to database...")
    save_success = save_rewritten_article(connection, article_id, content, rewritten_content, metadata)
    
    if save_success:
        # Update the is_ai_rewritten flag
        update_article_flag(connection, article_id)
    
    return save_success

def update_article_flag(connection, article_id):
    """Update the is_ai_rewritten flag to 1 for the article"""
    if not connection:
        return False
        
    try:
        cursor = connection.cursor()
        
        # Update query
        query = "UPDATE articles SET is_ai_rewritten = 1 WHERE id = %s"
        
        # Execute query
        cursor.execute(query, (article_id,))
        
        # Commit the transaction
        connection.commit()
        
        if cursor.rowcount > 0:
            print(f"Updated is_ai_rewritten flag for article (ID: {article_id})")
            return True
        else:
            print(f"No article with ID {article_id} found for update")
            return False
    except Exception as e:
        print(f"Error updating article flag: {e}")
        return False
    finally:
        if 'cursor' in locals():
            cursor.close()

def main():
    """Main function"""
    # Parse command line arguments
    args = parse_args()
    
    # Setup logging
    logger = setup_logging(args.log_file)
    logger.info("=== ARTICLE REWRITING FROM DATABASE USING GEMINI ===")
    
    # Connect to database
    connection = connect_to_database()
    if not connection:
        print("Failed to connect to database")
        logger.error("Failed to connect to database")
        return
    
    # Automatically set to delete original articles if specified
    auto_delete = True if args.auto_delete else False
    print(f"Auto-delete mode: {'Enabled' if auto_delete else 'Disabled'}")
    logger.info(f"Auto-delete mode: {'Enabled' if auto_delete else 'Disabled'}")
    
    # Set processing limit
    limit = args.limit
    print(f"Processing limit: {limit} articles")
    logger.info(f"Processing limit: {limit} articles")
    
    # Prepare article IDs
    article_ids = None
    
    # Priority: --article-id (single) > --article-ids (multiple)
    if args.article_id is not None:
        article_ids = [str(args.article_id)]
        print(f"Rewriting specific article ID: {args.article_id}")
        logger.info(f"Rewriting specific article ID: {args.article_id}")
    elif args.article_ids:
        article_ids = args.article_ids.split(',')
        print(f"Rewriting specific article IDs: {args.article_ids}")
        logger.info(f"Rewriting specific article IDs: {args.article_ids}")
    
    # Get articles to rewrite
    articles = get_unprocessed_articles(connection, limit, article_ids)
    
    # Process articles
    total_processed = 0
    total_success = 0
    
    for idx, article in enumerate(articles):
        # Print progress
        if len(articles) > 1:
            print(f"\n--- Article {idx+1}/{len(articles)} (ID: {article['id']}) ---\n")
            logger.info(f"Processing article {idx+1}/{len(articles)} (ID: {article['id']})")
        
        # Process article
        success = process_article(connection, article)
        
        total_processed += 1
        if success:
            total_success += 1
            
            # Delete original article if auto_delete is enabled
            if auto_delete:
                print(f"Deleting original article from database...")
                logger.info(f"Deleting original article ID {article['id']} due to auto_delete mode")
                if delete_original_article(connection, article['id']):
                    print(f"Deleted original article (ID: {article['id']}) from database")
                    logger.info(f"Deleted original article (ID: {article['id']}) from database")
                else:
                    print(f"Failed to delete original article (ID: {article['id']})")
                    logger.error(f"Failed to delete original article (ID: {article['id']})")
    
    # Print summary
    print("\n=== SUMMARY ===")
    print(f"Processed {total_processed} articles")
    print(f"Successfully rewritten and saved: {total_success}")
    
    logger.info(f"Summary: Processed {total_processed} articles, Successfully rewritten: {total_success}")
    
    # Close database connection
    if connection.is_connected():
        connection.close()
        print("Database connection closed")
        logger.info("Database connection closed")

if __name__ == "__main__":
    main() 