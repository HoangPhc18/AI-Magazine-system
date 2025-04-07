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

def extract_article_content(url):
    """
    Extract article title and content from a URL.
    
    Args:
        url (str): URL of the article
        
    Returns:
        dict: Dictionary containing title and content
    """
    logger.info(f"Extracting content from: {url}")
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
    }
    
    try:
        # Get the domain from the URL
        domain = urlparse(url).netloc
        logger.info(f"Detected domain: {domain}")
        
        # Fetch the page
        response = requests.get(url, headers=headers, timeout=15)
        response.raise_for_status()
        
        # Try using domain-specific selectors first
        for domain_key, selectors in DOMAIN_SELECTORS.items():
            if domain_key in domain:
                logger.info(f"Using custom selectors for domain: {domain_key}")
                soup = BeautifulSoup(response.text, 'html.parser')
                
                title_element = soup.select_one(selectors["title"])
                content_element = soup.select_one(selectors["content"])
                
                if title_element and content_element:
                    title = title_element.get_text().strip()
                    content = content_element.get_text().strip()
                    
                    # Clean up the content
                    content = re.sub(r'\s+', ' ', content)
                    
                    logger.info(f"Successfully extracted content using custom selectors (Title length: {len(title)}, Content length: {len(content)})")
                    return {
                        "title": title,
                        "content": content
                    }
        
        # If domain-specific extraction fails, fallback to trafilatura
        logger.info("Using trafilatura for content extraction")
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
                
                logger.info(f"Successfully extracted content using trafilatura (Title length: {len(title)}, Content length: {len(content)})")
                return {
                    "title": title,
                    "content": content
                }
        
        # If both methods fail, try a simple extraction with BeautifulSoup
        logger.info("Fallback to simple extraction with BeautifulSoup")
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # Get title
        title = soup.title.string if soup.title else ""
        
        # Get main content (simple heuristic)
        main_content = ""
        paragraphs = soup.find_all('p')
        for p in paragraphs:
            text = p.get_text().strip()
            if len(text) > 50:  # Only consider paragraphs with substantial text
                main_content += text + "\n\n"
        
        if title and main_content:
            logger.info(f"Extracted content with simple fallback (Title length: {len(title)}, Content length: {len(main_content)})")
            return {
                "title": title,
                "content": main_content
            }
        
        logger.warning("Failed to extract content using all methods")
        return {
            "title": "",
            "content": ""
        }
        
    except requests.exceptions.RequestException as e:
        logger.error(f"Error fetching URL: {str(e)}")
        return {
            "title": "",
            "content": f"Error fetching URL: {str(e)}"
        }
    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        return {
            "title": "",
            "content": f"Error extracting content: {str(e)}"
        }

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