#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module for scraping article content from a URL.
"""

import os
import sys
import logging
import requests
import re
import time
from bs4 import BeautifulSoup
from datetime import datetime
import trafilatura
from urllib.parse import urlparse

# Import cấu hình từ module config
from config import get_config

# Tải cấu hình
config = get_config()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"content_scraper_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

# List of common Vietnamese news domains and their article content selectors
DOMAIN_SELECTORS = {
    "vnexpress.net": {
        "title": "h1.title-detail",
        "content": "article.fck_detail",
    },
    "tuoitre.vn": {
        "title": "h1.article-title",
        "content": "div.article-content",
    },
    "thanhnien.vn": {
        "title": "h1.details__headline",
        "content": "div.details__content",
    },
    "dantri.com.vn": {
        "title": "h1.title-page",
        "content": "div.dt-news__content",
    },
    "vietnamnet.vn": {
        "title": "h1.content-detail-title",
        "content": "div.content-detail-body",
    },
    "nhandan.vn": {
        "title": "h1.article__title",
        "content": "div.article__body",
    }
}

# Kiểm tra xem có cấu hình bổ sung nào từ file .env
additional_domains = config.get("DOMAIN_SELECTORS", None)
if additional_domains and isinstance(additional_domains, dict):
    DOMAIN_SELECTORS.update(additional_domains)
    logger.info(f"Added {len(additional_domains)} custom domain selectors from config")

def extract_article_content(url):
    """
    Extract article title and content from a URL.
    
    Args:
        url (str): URL of the article
        
    Returns:
        dict: Dictionary containing title and content
    """
    logger.info(f"Extracting content from: {url}")
    
    # Tải lại cấu hình để có thông tin mới nhất
    config = get_config()
    
    # Sử dụng User-Agent từ cấu hình nếu có
    default_user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    user_agent = config.get("USER_AGENT", default_user_agent)
    
    headers = {
        "User-Agent": user_agent,
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    }
    
    try:
        # Validate URL
        if not url or not url.startswith('http'):
            logger.error(f"Invalid URL: {url}")
            return None
            
        # Get the domain from the URL
        domain = urlparse(url).netloc
        logger.info(f"Detected domain: {domain}")
        
        # Timeout từ cấu hình hoặc mặc định 15s
        timeout = config.get("REQUEST_TIMEOUT", 15)
        
        # Fetch the page with retries
        max_retries = 3
        retry_count = 0
        
        while retry_count < max_retries:
            try:
                response = requests.get(url, headers=headers, timeout=timeout)
                response.raise_for_status()
                break
            except (requests.RequestException, requests.HTTPError) as e:
                retry_count += 1
                if retry_count >= max_retries:
                    logger.error(f"Failed to fetch URL after {max_retries} attempts: {str(e)}")
                    return None
                logger.warning(f"Retry {retry_count}/{max_retries} - Error fetching URL: {str(e)}")
                time.sleep(2)  # Wait before retrying
        
        # Check content type to ensure it's HTML
        content_type = response.headers.get('Content-Type', '').lower()
        if 'text/html' not in content_type:
            logger.error(f"URL does not return HTML content: {content_type}")
            return None
            
        # Check for empty response
        if not response.text.strip():
            logger.error("Empty response from URL")
            return None
        
        # Parse HTML for title extraction in any case (used as fallback)
        soup = BeautifulSoup(response.text, 'html.parser')
        fallback_title = ""
        if soup.title:
            fallback_title = soup.title.string.strip() if soup.title.string else ""
        
        # Try using domain-specific selectors first
        extracted_data = None
        for domain_key, selectors in DOMAIN_SELECTORS.items():
            if domain_key in domain:
                logger.info(f"Using custom selectors for domain: {domain_key}")
                
                title_element = soup.select_one(selectors["title"])
                content_element = soup.select_one(selectors["content"])
                
                if title_element and content_element:
                    title = title_element.get_text().strip()
                    content = content_element.get_text().strip()
                    
                    # Clean up the content
                    content = re.sub(r'\s+', ' ', content)
                    
                    logger.info(f"Successfully extracted content using custom selectors (Title length: {len(title)}, Content length: {len(content)})")
                    extracted_data = {
                        "title": title,
                        "content": content
                    }
                    break
        
        # If domain-specific extraction fails, try trafilatura
        if not extracted_data or len(extracted_data.get("content", "")) < 200:  # Minimum content length check
            logger.info("Using trafilatura for content extraction")
            try:
                downloaded = trafilatura.fetch_url(url)
                
                if downloaded:
                    # Extract with trafilatura
                    result = trafilatura.extract(downloaded, output_format="json", include_comments=False, 
                                                include_tables=False, no_fallback=False)
                    
                    if result:
                        import json
                        data = json.loads(result)
                        title = data.get("title", "")
                        content = data.get("text", "")
                        
                        # If trafilatura didn't extract a title but we have fallback_title, use it
                        if not title and fallback_title:
                            title = fallback_title
                            logger.info(f"Using fallback title: {title}")
                        
                        logger.info(f"Successfully extracted content using trafilatura (Title length: {len(title)}, Content length: {len(content)})")
                        
                        if title and content and len(content) >= 200:
                            extracted_data = {
                                "title": title,
                                "content": content
                            }
            except Exception as e:
                logger.error(f"Error using trafilatura: {str(e)}")
        
        # If both methods fail, try a simple extraction with BeautifulSoup
        if not extracted_data or len(extracted_data.get("content", "")) < 200:
            logger.info("Fallback to simple extraction with BeautifulSoup")
            
            # Get title - use the one we already extracted
            title = fallback_title
            
            # Get main content (improved heuristic)
            main_content = ""
            
            # Try to find the main article container
            main_candidates = [
                soup.find('article'),
                soup.find('div', class_=lambda c: c and ('content' in c.lower() or 'article' in c.lower())),
                soup.find('div', id=lambda i: i and ('content' in i.lower() or 'article' in i.lower()))
            ]
            
            main_element = next((elem for elem in main_candidates if elem), None)
            
            if main_element:
                # Extract from main element
                paragraphs = main_element.find_all('p')
            else:
                # Extract from whole page
                paragraphs = soup.find_all('p')
            
            for p in paragraphs:
                text = p.get_text().strip()
                if len(text) > 30:  # Only consider paragraphs with substantial text
                    main_content += text + "\n\n"
            
            # If p tags didn't work, try div tags
            if len(main_content) < 200:
                div_texts = []
                for div in soup.find_all('div'):
                    if div.find('div') is None:  # Only leaf divs
                        text = div.get_text().strip()
                        if len(text) > 50 and len(text) < 1000:  # Reasonable length for a paragraph
                            div_texts.append(text)
                
                # Sort by length (descending) and take top 10
                div_texts.sort(key=len, reverse=True)
                main_content = "\n\n".join(div_texts[:10])
            
            if title and main_content and len(main_content) >= 200:
                logger.info(f"Extracted content with simple fallback (Title length: {len(title)}, Content length: {len(main_content)})")
                extracted_data = {
                    "title": title,
                    "content": main_content.strip()
                }
        
        # If we have content but no title, try to generate a title from content
        if extracted_data and extracted_data["content"] and not extracted_data.get("title"):
            # Generate title from first sentence of content
            first_sentence = re.split(r'(?<=[.!?])\s+', extracted_data["content"].strip())[0]
            if first_sentence and len(first_sentence) > 5:
                if len(first_sentence) > 100:
                    extracted_data["title"] = first_sentence[:100] + "..."
                else:
                    extracted_data["title"] = first_sentence
                logger.info(f"Generated title from content: {extracted_data['title']}")
        
        # Final check to make sure we have both title and content
        if extracted_data and extracted_data.get("title") and extracted_data.get("content"):
            # Final cleaning
            content = extracted_data["content"]
            
            # Remove excessive newlines
            content = re.sub(r'\n{3,}', '\n\n', content)
            
            # Ensure content is long enough
            if len(content) < 200:
                logger.error(f"Content too short after cleaning: {len(content)} chars")
                return None
                
            extracted_data["content"] = content
                
            logger.info(f"Successfully extracted content from URL: {url}")
            logger.info(f"Title: {extracted_data['title']}")
            logger.info(f"Content length: {len(extracted_data['content'])}")
            
            return extracted_data
        else:
            logger.error("Failed to extract complete content (missing title or content)")
            return None
            
    except Exception as e:
        logger.error(f"Error extracting content from URL: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
        return None

if __name__ == '__main__':
    # Test the function
    if len(sys.argv) > 1:
        url = sys.argv[1]
        result = extract_article_content(url)
        print(f"Title: {result['title']}")
        print(f"Content length: {len(result['content'])}")
        print("First 500 chars of content:")
        print(result['content'][:500] + "...")
    else:
        print("Usage: python scraper.py <url>") 