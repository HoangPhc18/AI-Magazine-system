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
    },
    "laodong.vn": {
        "title": "h1.article__title, h1.title, .detail-title h1",
        "content": "div.article__contents, div.article-content, div.detail-content",
    },
    "baodautu.vn": {
        "title": "h1.detail-title",
        "content": "div.detail-content",
    },
    "tienphong.vn": {
        "title": "h1.article-title",
        "content": "div.article-content",
    },
    "24h.com.vn": {
        "title": "h1.brmCne, h1.clrTit",
        "content": "div.text-conent, div.brmDtl",
    }
}

def extract_laodong_content(html_content):
    """
    Phương pháp trích xuất đặc biệt cho trang laodong.vn
    
    Args:
        html_content (str): Nội dung HTML của trang
        
    Returns:
        dict: Dictionary chứa tiêu đề và nội dung bài viết
    """
    logger.info("Using special extractor for laodong.vn")
    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Tìm tiêu đề - thử nhiều bộ chọn khác nhau
    title = ""
    title_selectors = [
        ".detail-title h1",
        "h1.article__title",
        "h1.title",
        "header h1",
        ".article-head h1"
    ]
    
    for selector in title_selectors:
        title_element = soup.select_one(selector)
        if title_element and title_element.get_text().strip():
            title = title_element.get_text().strip()
            logger.info(f"Found title with selector '{selector}': {title}")
            break
    
    # Tìm nội dung chính
    content = ""
    
    # Thử các bộ chọn nội dung
    content_selectors = [
        "div.article__contents", 
        "div.article-content", 
        "div.detail-content",
        ".contentbody",
        "#content_detail",
        ".article-body",
        ".detail-content-body"
    ]
    
    for selector in content_selectors:
        content_element = soup.select_one(selector)
        if content_element:
            # Lọc nội dung cho sạch
            paragraphs = []
            
            # Thử lấy tất cả thẻ p trong container
            for p in content_element.find_all('p'):
                p_text = p.get_text().strip()
                if p_text and len(p_text) > 15 and not p_text.startswith("VIDEO:"):
                    paragraphs.append(p_text)
            
            # Nếu không có đủ đoạn văn, thử lấy text trực tiếp
            if len(paragraphs) < 3:
                direct_text = content_element.get_text().strip()
                content = re.sub(r'\s+', ' ', direct_text)
            else:
                content = "\n\n".join(paragraphs)
                
            if content and len(content) > 200:
                logger.info(f"Found content with selector '{selector}', length: {len(content)}")
                break
    
    if title and content:
        return {
            "title": title,
            "content": content
        }
    
    logger.warning("Special extractor for laodong.vn failed")
    return {"title": "", "content": ""}

def extract_from_any_element(html_content):
    """
    Phương pháp trích xuất cuối cùng khi các phương pháp khác đều thất bại.
    Tìm text có ý nghĩa từ bất kỳ phần tử nào.
    
    Args:
        html_content (str): Nội dung HTML
        
    Returns:
        dict: Dictionary chứa tiêu đề và nội dung
    """
    logger.info("Using last resort extraction method - extracting from any meaningful element")
    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Lấy tiêu đề từ thẻ title
    title = ""
    if soup.title:
        title = soup.title.string.strip()
        
    # Lọc ra tất cả các thẻ có thể chứa nội dung
    content_candidates = []
    
    # 1. Tìm tất cả các div có ít nhất 3 thẻ p
    for div in soup.find_all('div'):
        paragraphs = div.find_all('p')
        if len(paragraphs) >= 3:
            text = ""
            for p in paragraphs:
                p_text = p.get_text().strip()
                if len(p_text) > 30:  # Đủ dài để là đoạn văn có ý nghĩa
                    text += p_text + "\n\n"
            
            if len(text) > 300:  # Đoạn văn có ít nhất 300 ký tự
                content_candidates.append(text)
    
    # 2. Tìm các thẻ article
    for article in soup.find_all('article'):
        text = article.get_text().strip()
        if len(text) > 300:
            content_candidates.append(text)
    
    # 3. Tìm các section
    for section in soup.find_all('section'):
        paragraphs = section.find_all('p')
        if len(paragraphs) >= 2:
            text = ""
            for p in paragraphs:
                p_text = p.get_text().strip()
                if len(p_text) > 20:
                    text += p_text + "\n\n"
            if len(text) > 200:
                content_candidates.append(text)
    
    # Nếu có nhiều đoạn văn, chọn đoạn dài nhất
    if content_candidates:
        content = max(content_candidates, key=len)
        logger.info(f"Extracted content with length {len(content)}")
        return {
            "title": title,
            "content": content
        }
    
    logger.warning("Last resort extraction method failed")
    return {"title": title, "content": ""}

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
        html_content = response.text
        
        # Lưu HTML để debug nếu cần
        debug_filename = f"debug_{domain.replace('.', '_')}.html"
        with open(debug_filename, "w", encoding="utf-8") as f:
            f.write(html_content)
        logger.info(f"Saved HTML content to {debug_filename} for debugging")
        
        # Special handler for laodong.vn
        if "laodong.vn" in domain:
            result = extract_laodong_content(html_content)
            if result["title"] and result["content"]:
                return result
        
        # Try using domain-specific selectors first
        for domain_key, selectors in DOMAIN_SELECTORS.items():
            if domain_key in domain:
                logger.info(f"Using custom selectors for domain: {domain_key}")
                soup = BeautifulSoup(html_content, 'html.parser')
                
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
        
        try:
            # Disable signal handling in trafilatura to prevent "signal only works in main thread" error
            import signal
            original_sighup = signal.getsignal(signal.SIGHUP)
            original_sigterm = signal.getsignal(signal.SIGTERM)
            original_sigint = signal.getsignal(signal.SIGINT)
            
            # Temporarily set signal handlers to default to avoid issues in non-main threads
            signal.signal(signal.SIGHUP, signal.SIG_DFL)
            signal.signal(signal.SIGTERM, signal.SIG_DFL)
            signal.signal(signal.SIGINT, signal.SIG_DFL)
            
            # Trực tiếp sử dụng html đã tải xuống thay vì gọi fetch_url lại
            result = trafilatura.extract(
                html_content,
                output_format="json",
                include_comments=False,
                include_tables=True,
                favor_precision=True,
                include_links=False,
                target_language="vi"
            )
            
            # Restore original signal handlers
            signal.signal(signal.SIGHUP, original_sighup)
            signal.signal(signal.SIGTERM, original_sigterm)
            signal.signal(signal.SIGINT, original_sigint)
            
            if result:
                import json
                data = json.loads(result)
                title = data.get("title", "")
                content = data.get("text", "")
                
                if title and len(content) > 100:  # Chỉ chấp nhận nội dung đủ dài
                    logger.info(f"Successfully extracted content using trafilatura (Title length: {len(title)}, Content length: {len(content)})")
                    return {
                        "title": title,
                        "content": content
                    }
                else:
                    logger.warning(f"Trafilatura extraction returned insufficient content: Title length={len(title)}, Content length={len(content)}")
            else:
                logger.warning("Trafilatura extraction returned None")
                
        except Exception as e:
            logger.error(f"Error using trafilatura: {str(e)}")
            logger.info("Continuing with fallback extraction methods")
        
        # If trafilatura fails, try a different approach with BeautifulSoup
        logger.info("Fallback to advanced extraction with BeautifulSoup")
        soup = BeautifulSoup(html_content, 'html.parser')
        
        # Get title - try multiple selectors
        title = ""
        title_selectors = ["h1", "h1.title", ".title", ".article-title", "h1.headline", "header h1"]
        for selector in title_selectors:
            title_elem = soup.select_one(selector)
            if title_elem and title_elem.get_text().strip():
                title = title_elem.get_text().strip()
                break
                
        if not title and soup.title:
            title = soup.title.string.strip()
        
        # Get main content (improved heuristic)
        main_content = ""
        
        # Try common article container selectors
        content_selectors = [
            "article", ".article", ".article-content", ".content", ".post-content",
            ".entry-content", "main", "#main-content", ".news-content", "[itemprop='articleBody']"
        ]
        
        for selector in content_selectors:
            content_container = soup.select_one(selector)
            if content_container:
                paragraphs = content_container.find_all('p')
                for p in paragraphs:
                    text = p.get_text().strip()
                    if text and len(text) > 20:  # Minor text filtering
                        main_content += text + "\n\n"
                        
                if len(main_content) > 200:  # If we got substantial content, use it
                    break
        
        # If we still don't have content, try getting all paragraphs
        if len(main_content) < 200:
            paragraphs = soup.find_all('p')
            filtered_paragraphs = []
            
            # First pass - collect paragraphs and their lengths
            for p in paragraphs:
                text = p.get_text().strip()
                if len(text) > 30:  # Only consider paragraphs with substantial text
                    filtered_paragraphs.append(text)
            
            # If we have enough paragraphs, combine them
            if len(filtered_paragraphs) >= 3:
                main_content = "\n\n".join(filtered_paragraphs)
        
        if title and len(main_content) > 200:
            logger.info(f"Extracted content with BeautifulSoup fallback (Title length: {len(title)}, Content length: {len(main_content)})")
            return {
                "title": title,
                "content": main_content
            }
        
        # Nếu tất cả phương pháp trên thất bại, dùng phương pháp cuối cùng
        logger.warning("All standard extraction methods failed, using last resort method")
        result = extract_from_any_element(html_content)
        if result["title"] and result["content"]:
            logger.info("Last resort method succeeded")
            return result
        
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