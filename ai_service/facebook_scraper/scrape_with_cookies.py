#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import json
import requests
import argparse
import logging
import sys
import re
import mysql.connector
from datetime import datetime
from bs4 import BeautifulSoup

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("facebook_scraper")

def load_cookies(cookie_file="/app/facebook_scraper/cookies/facebook_cookies.json"):
    """Load cookies from file"""
    try:
        if not os.path.exists(cookie_file):
            logger.error(f"Cookie file not found: {cookie_file}")
            return None
        
        with open(cookie_file, "r") as f:
            cookies = json.load(f)
        
        if not cookies:
            logger.error("Cookie file is empty")
            return None
        
        # Convert cookies to requests format
        cookies_dict = {}
        for cookie in cookies:
            if "name" in cookie and "value" in cookie:
                cookies_dict[cookie["name"]] = cookie["value"]
        
        logger.info(f"Loaded {len(cookies_dict)} cookies from file")
        return cookies_dict
    except Exception as e:
        logger.error(f"Error loading cookies: {str(e)}")
        return None

def get_headers():
    """Get headers for requests"""
    return {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
        "Accept": "text/html,application/xhtml+xml,application/xml",
        "Accept-Language": "en-US,en;q=0.9",
        "Accept-Encoding": "gzip, deflate, br",
        "Connection": "keep-alive",
        "Cache-Control": "max-age=0",
        "Sec-Ch-Ua": "\"Not.A/Brand\";v=\"8\", \"Chromium\";v=\"114\"",
        "Sec-Ch-Ua-Mobile": "?0",
        "Sec-Ch-Ua-Platform": "\"Windows\"",
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "none",
        "Sec-Fetch-User": "?1",
        "Upgrade-Insecure-Requests": "1"
    }

def scrape_facebook_page(url, cookies, limit=10):
    """Scrape posts from a Facebook page or group using requests"""
    try:
        if not cookies:
            logger.error("No cookies available")
            return []
        
        logger.info(f"Scraping URL: {url}")
        
        # Make request to the page
        headers = get_headers()
        response = requests.get(url, cookies=cookies, headers=headers, allow_redirects=True)
        
        # Check if we're still logged in
        if "login" in response.url:
            logger.error("Cookies have expired. Need to login again.")
            return []
        
        # Save response for debugging
        os.makedirs("logs", exist_ok=True)
        with open("logs/facebook_page.html", "w", encoding="utf-8") as f:
            f.write(response.text)
        
        # Parse HTML
        soup = BeautifulSoup(response.text, "html.parser")
        
        # Extract posts using BeautifulSoup
        posts = []
        
        # Find all post divs
        post_divs = soup.find_all("div", {"role": "article"})
        logger.info(f"Found {len(post_divs)} potential posts")
        
        # Process each post
        for i, div in enumerate(post_divs[:limit]):
            try:
                post_data = extract_post_data(div, url)
                if post_data and post_data.get("content"):
                    posts.append(post_data)
                    logger.info(f"Extracted post {len(posts)}/{limit}")
                
                # Stop once we have enough posts
                if len(posts) >= limit:
                    break
            except Exception as e:
                logger.error(f"Error extracting post {i}: {str(e)}")
                continue
        
        logger.info(f"Successfully extracted {len(posts)} posts")
        return posts
    except Exception as e:
        logger.error(f"Error scraping Facebook page: {str(e)}")
        return []

def extract_post_data(post_div, page_url):
    """Extract data from a post div"""
    try:
        # Extract content
        content_divs = post_div.find_all("div", {"dir": "auto", "style": True})
        content = ""
        
        if content_divs:
            # Get the longest content div
            content_div = max(content_divs, key=lambda div: len(div.text) if div.text else 0)
            content = content_div.text.strip()
        
        # If no content found, try different approach
        if not content:
            content_divs = post_div.select("div[data-ad-preview='message']")
            if content_divs:
                content = content_divs[0].text.strip()
        
        # If still no content, try any div with substantial text
        if not content:
            for div in post_div.find_all("div"):
                if div.text and len(div.text.strip()) > 50:
                    content = div.text.strip()
                    break
        
        # Clean content
        content = clean_content(content)
        
        # Extract post URL
        post_url = ""
        for a in post_div.find_all("a", href=True):
            href = a.get("href")
            # Check if this is a post link
            if href and any(pattern in href for pattern in ["/posts/", "/permalink/", "story_fbid="]):
                post_url = "https://www.facebook.com" + href if not href.startswith("http") else href
                break
        
        # If no URL found, use the page URL
        if not post_url:
            post_url = page_url
        
        # Create post data
        post_data = {
            "content": content,
            "url": post_url,
            "created_at": datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        return post_data
    except Exception as e:
        logger.error(f"Error extracting post data: {str(e)}")
        return None

def clean_content(content):
    """Clean post content"""
    if not content:
        return ""
    
    # Remove "See more" and similar phrases
    phrases = [
        "See more", "Xem thêm", "Show more", "Hiển thị thêm", 
        "See more options", "View more comments"
    ]
    
    for phrase in phrases:
        content = content.replace(phrase, "")
    
    # Remove multiple spaces and newlines
    content = re.sub(r'\s+', ' ', content).strip()
    
    return content

def save_posts_to_database(posts):
    """Save scraped posts to the database"""
    if not posts:
        logger.warning("No posts to save to database")
        return
    
    try:
        # Database configuration
        db_config = {
            "host": "host.docker.internal",
            "user": "tap_chi_dien_tu",
            "password": "Nh[Xg3KT06)FI91X",
            "database": "tap_chi_dien_tu",
            "port": 3306
        }
        
        # Connect to database
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()
        
        # SQL query for insertion
        insert_query = """
        INSERT INTO facebook_posts (content, source_url, processed, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        updated_at = VALUES(updated_at)
        """
        
        # Insert posts
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        for post in posts:
            content = post.get("content", "")
            url = post.get("url", "")
            
            # Skip posts with no content
            if not content:
                continue
            
            # Limit content length
            if len(content) > 10000:
                content = content[:9995] + "..."
            
            # Execute query
            cursor.execute(insert_query, (
                content,
                url,
                0,  # not processed
                now,
                now
            ))
        
        # Commit changes
        connection.commit()
        logger.info(f"Successfully saved {len(posts)} posts to database")
        
        # Close connection
        cursor.close()
        connection.close()
    except Exception as e:
        logger.error(f"Error saving posts to database: {str(e)}")

def main():
    """Main function"""
    parser = argparse.ArgumentParser(description="Scrape Facebook posts with cookies")
    parser.add_argument("url", help="URL of Facebook page or group to scrape")
    parser.add_argument("--limit", type=int, default=10, help="Maximum number of posts to scrape")
    parser.add_argument("--cookie-file", type=str, default="/app/facebook_scraper/cookies/facebook_cookies.json", 
                      help="Path to cookie file")
    parser.add_argument("--save-to-db", action="store_true", help="Save posts to database")
    
    args = parser.parse_args()
    
    # Load cookies
    cookies = load_cookies(args.cookie_file)
    
    if not cookies:
        logger.error("Failed to load cookies. Exiting.")
        return 1
    
    # Scrape posts
    posts = scrape_facebook_page(args.url, cookies, args.limit)
    
    if not posts:
        logger.error("Failed to scrape posts. Exiting.")
        return 1
    
    # Save to database if requested
    if args.save_to_db:
        save_posts_to_database(posts)
    
    # Print summary
    print(f"\nScraped {len(posts)} posts:")
    for i, post in enumerate(posts, 1):
        print(f"{i}. Content: {post.get('content', '')[:100]}...")
        print(f"   URL: {post.get('url', 'N/A')}")
        print()
    
    return 0

if __name__ == "__main__":
    sys.exit(main()) 