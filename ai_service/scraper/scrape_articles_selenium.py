#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module để trích xuất nội dung bài viết từ URL đã thu thập.
Mỗi URL được truy cập bằng Selenium để lấy nội dung đầy đủ.
Chạy sau khi google_news_serpapi.py đã thu thập các URL bài viết.
"""

import json
import time
import sys
import os
import requests
import re
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import logging
from bs4 import BeautifulSoup
from urllib.parse import urlparse

# 🔹 Laravel Backend API URL - Update as needed
BACKEND_API_URL = "http://localhost:8000/api/articles/import"

# Cấu hình logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"content_scraper_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

def setup_driver():
    """Setup and return a configured Chrome WebDriver instance"""
    chrome_options = Options()
    chrome_options.add_argument("--headless")  # Run in headless mode
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36")
    
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=chrome_options)
    
    return driver

def get_site_specific_selectors(url):
    """
    Trả về các bộ chọn CSS tùy chỉnh dựa trên tên miền
    
    Args:
        url (str): URL của bài viết
    
    Returns:
        dict: Các bộ chọn cho tiêu đề và nội dung
    """
    domain = urlparse(url).netloc
    
    selectors = {
        # VnExpress
        "vnexpress.net": {
            "title": ["h1.title-detail", "h1.title-post"],
            "content": ["article.fck_detail", "div.fck_detail", ".content_detail"],
            "paragraphs": ["p.Normal", "p"],
            "exclude": [".author", ".copyright", ".relatebox", ".box-tag"]
        },
        # Tuổi Trẻ
        "tuoitre.vn": {
            "title": ["h1.article-title", "h1.title-2"],
            "content": ["div.content.fck", "#main-detail-body"],
            "paragraphs": ["p"],
            "exclude": [".VCSortableInPreviewMode", ".relate-container"]
        },
        # Dân Trí
        "dantri.com.vn": {
            "title": ["h1.title-page", "h1.e-title"],
            "content": ["div.dt-news__content", "div.e-content"],
            "paragraphs": ["p"],
            "exclude": [".dt-news__sapo", ".author-info"]
        },
        # Thanh Niên
        "thanhnien.vn": {
            "title": ["h1.detail-title", "h1.cms-title"],
            "content": ["div.detail-content", "div.cms-body"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source"]
        },
        # VietnamNet
        "vietnamnet.vn": {
            "title": ["h1.content-detail-title", "h1.title"],
            "content": ["div.content-detail", ".ArticleContent", "#article-body"],
            "paragraphs": ["p"],
            "exclude": [".author-info", ".article-relate"]
        },
        # Nhân Dân
        "nhandan.vn": {
            "title": ["div.box-title h1", ".nd-detail-title"],
            "content": ["div.box-content-detail", "#nd-article-content"],
            "paragraphs": ["p"],
            "exclude": [".box-author"]
        },
        # Tiền Phong
        "tienphong.vn": {
            "title": ["h1.article__title", "h1.cms-title"],
            "content": ["div.article__body", ".cms-body"],
            "paragraphs": ["p"],
            "exclude": [".article__author", ".article__tag", ".article__share"]
        },
        # Báo Mới
        "baomoi.com": {
            "title": ["h1.bm-title", "h1.title"],
            "content": ["div.content", ".bm-content"],
            "paragraphs": ["p"],
            "exclude": [".bm-source", ".bm-resource"]
        },
        # Zing News
        "zingnews.vn": {
            "title": ["h1.the-article-title", "h1.article-title"],
            "content": ["div.the-article-body", "article.the-article-content"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source", ".article-tags", ".article-related"]
        },
        # 24h
        "24h.com.vn": {
            "title": ["h1.bld", "h1.clrTit"],
            "content": ["div.text-conent", "div.baiviet-bailienquan"],
            "paragraphs": ["p"],
            "exclude": [".nguontin", ".baiviet-tags"]
        }
    }
    
    # Mặc định cho các trang không có cấu hình cụ thể
    default_selectors = {
        "title": ["h1", "h1.title", "h1.article-title", ".headline", ".article-headline", ".entry-title"],
        "content": ["article", "main", ".content", ".article-content", ".entry-content", ".post-content"],
        "paragraphs": ["p"],
        "exclude": [".comments", ".sidebar", ".related", ".footer", ".header", ".navigation", ".menu", ".ads"]
    }
    
    # Trả về bộ chọn tùy chỉnh hoặc mặc định
    for key in selectors:
        if key in domain:
            return selectors[key]
            
    return default_selectors

def extract_content(driver, url, title="Unknown title"):
    """
    Trích xuất nội dung có cấu trúc từ URL bài viết, với xử lý tùy chỉnh cho các trang tin tức phổ biến
    
    Args:
        driver (WebDriver): Driver Selenium
        url (str): URL của bài viết
        title (str): Tiêu đề bài viết ban đầu (nếu có)
        
    Returns:
        tuple: (extracted_title, full_content)
    """
    try:
        logger.info(f"Đang truy cập URL: {url}")
        driver.get(url)
        
        # Chờ nội dung tải xong (tối đa 20 giây)
        WebDriverWait(driver, 20).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        
        # Đảm bảo trang đã tải đầy đủ bằng cách scroll xuống
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight * 0.5);")
        time.sleep(1)
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(1)
        
        # Lấy nội dung trang
        page_content = driver.page_source
        
        # Sử dụng BeautifulSoup để phân tích HTML
        soup = BeautifulSoup(page_content, "html.parser")
        
        # Lấy các bộ chọn tùy chỉnh theo trang web
        selectors = get_site_specific_selectors(url)
        
        # ---------- Trích xuất tiêu đề ----------
        extracted_title = ""
        
        # Thử các bộ chọn tiêu đề tùy chỉnh
        for selector in selectors["title"]:
            title_element = soup.select_one(selector)
            if title_element and title_element.text.strip():
                extracted_title = title_element.text.strip()
                logger.info(f"Đã tìm thấy tiêu đề từ bộ chọn '{selector}': {extracted_title[:50]}...")
                break
        
        # Nếu không tìm thấy, thử tìm từ các thẻ h1/h2 phổ biến
        if not extracted_title:
            heading_tags = soup.find_all(['h1', 'h2'], limit=3)
            for tag in heading_tags:
                text = tag.text.strip()
                if text and len(text) > 15:  # Tiêu đề thường dài hơn 15 ký tự
                    extracted_title = text
                    logger.info(f"Đã tìm thấy tiêu đề từ thẻ '{tag.name}': {extracted_title[:50]}...")
                    break
        
        # Thử lấy title từ thẻ title nếu vẫn không tìm thấy
        if not extracted_title and soup.title:
            # Lấy từ thẻ title, loại bỏ tên trang web nếu có
            page_title = soup.title.text.strip()
            # Loại bỏ phần đuôi như "| VnExpress", "- Dân Trí", v.v.
            extracted_title = re.sub(r'[-|]\s*[^-|]+$', '', page_title).strip()
            logger.info(f"Đã lấy tiêu đề từ thẻ title: {extracted_title[:50]}...")
        
        # Sử dụng tiêu đề đã có nếu vẫn không tìm thấy
        if not extracted_title and title and title != "Unknown title":
            extracted_title = title
            logger.info(f"Sử dụng tiêu đề đã có: {extracted_title[:50]}...")
        
        # ---------- Trích xuất nội dung ----------
        content_element = None
        
        # Thử các bộ chọn nội dung tùy chỉnh
        for selector in selectors["content"]:
            content_element = soup.select_one(selector)
            if content_element:
                logger.info(f"Đã tìm thấy phần tử nội dung từ bộ chọn '{selector}'")
                break
        
        # Loại bỏ các phần tử không mong muốn từ nội dung
        if content_element:
            for exclude_selector in selectors["exclude"]:
                for element in content_element.select(exclude_selector):
                    element.extract()
        
        # Trích xuất nội dung từ các đoạn văn
        content_paragraphs = []
        
        if content_element:
            # Tìm tất cả các đoạn văn trong phần tử nội dung
            paragraphs = content_element.select(", ".join(selectors["paragraphs"]))
            
            for p in paragraphs:
                text = p.text.strip()
                # Lọc các đoạn văn có nghĩa (loại bỏ các đoạn quá ngắn hoặc là thông tin phụ)
                if text and len(text) > 10:  # Lấy đoạn văn dài hơn 10 ký tự
                    content_paragraphs.append(text)
        else:
            # Nếu không tìm thấy phần tử nội dung, tìm tất cả các thẻ p
            logger.warning(f"Không tìm thấy phần tử nội dung cụ thể cho URL: {url}, dùng phương pháp dự phòng")
            
            # Loại bỏ các phần tử không mong muốn
            for selector in selectors["exclude"]:
                for element in soup.select(selector):
                    element.extract()
            
            # Loại bỏ các phần tử script, style, nav, header, footer, ads
            for element in soup(['script', 'style', 'nav', 'header', 'footer', 'iframe', 'aside']):
                element.extract()
            
            # Tìm tất cả các thẻ p
            paragraphs = soup.find_all('p')
            for p in paragraphs:
                text = p.text.strip()
                if text and len(text) > 20:  # Đoạn văn trong nội dung chính thường dài hơn
                    content_paragraphs.append(text)
        
        # Kết hợp nội dung thành một chuỗi văn bản
        full_content = ""
        
        # Thêm nội dung của các đoạn văn
        if content_paragraphs:
            full_content = "\n\n".join(content_paragraphs)
        else:
            # Phương pháp cuối cùng: lấy tất cả văn bản
            logger.warning(f"Không tìm thấy đoạn văn cho URL: {url}, dùng phương pháp trích xuất văn bản thô")
            
            # Lấy văn bản
            text = soup.get_text()
            
            # Xử lý văn bản
            lines = (line.strip() for line in text.splitlines())
            chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
            text_chunks = [chunk for chunk in chunks if chunk and len(chunk) > 20]
            
            # Lọc các đoạn văn có nghĩa
            filtered_chunks = []
            for chunk in text_chunks:
                # Loại bỏ các đoạn quá ngắn hoặc chỉ có ký tự đặc biệt/số
                if len(chunk) > 30 and not re.match(r'^[\d\W]+$', chunk):
                    filtered_chunks.append(chunk)
            
            if filtered_chunks:
                full_content = "\n\n".join(filtered_chunks)
        
        # Làm sạch nội dung
        # Loại bỏ khoảng trắng dư thừa
        full_content = re.sub(r'\s+', ' ', full_content).strip()
        # Loại bỏ các ký tự đặc biệt và dấu chấm câu dư thừa
        full_content = re.sub(r'[.]{2,}', '.', full_content)
        
        # Phân đoạn lại văn bản để dễ đọc
        paragraphs = [p.strip() for p in full_content.split('\n\n')]
        full_content = '\n\n'.join([p for p in paragraphs if p])
        
        # Kiểm tra kết quả
        word_count = len(full_content.split())
        if word_count < 50:
            logger.warning(f"Nội dung trích xuất quá ngắn ({word_count} từ) từ: {url}")
        else:
            logger.info(f"Trích xuất thành công nội dung ({word_count} từ) từ: {url}")
        
        return extracted_title, full_content
    
    except Exception as e:
        logger.error(f"Lỗi khi trích xuất nội dung từ {url}: {str(e)}")
        return title, ""

def filter_article(url):
    """
    Kiểm tra xem URL có phù hợp cho việc trích xuất nội dung hay không
    
    Args:
        url (str): URL của bài viết
        
    Returns:
        bool: True nếu URL phù hợp, False nếu cần loại bỏ
    """
    # Loại bỏ các URL từ vtv.vn/video/ vì đây là nội dung video
    if "vtv.vn/video/" in url:
        logger.warning(f"Bỏ qua URL video không phù hợp: {url}")
        return False
    
    # Kiểm tra các định dạng URL đa phương tiện khác
    media_patterns = ["/video/", "/clip/", "/podcast/", "/photo/", "/gallery/", "/infographic/"]
    for pattern in media_patterns:
        if pattern in url.lower():
            logger.warning(f"Bỏ qua URL đa phương tiện không phù hợp: {url}")
            return False
    
    return True

def enrich_article(driver, article):
    """
    Làm phong phú thêm dữ liệu bài viết bằng cách trích xuất nội dung đầy đủ từ URL
    
    Args:
        driver (WebDriver): Driver Selenium
        article (dict): Thông tin bài viết (với URL nhưng chưa có nội dung)
        
    Returns:
        dict: Bài viết đã được làm phong phú thêm với tiêu đề và nội dung đầy đủ
    """
    url = article.get("source_url")
    # Bỏ qua nếu không có URL
    if not url:
        logger.warning(f"Không có URL cho bài viết: {article.get('title', 'Unknown title')}")
        return article
    
    # Kiểm tra URL có phù hợp hay không
    if not filter_article(url):
        logger.info(f"Bỏ qua URL không phù hợp: {url}")
        return None
    
    try:
        # Trích xuất tiêu đề và nội dung từ URL
        extracted_title, full_content = extract_content(driver, url, article.get("title", "Unknown title"))
        
        # Cập nhật tiêu đề nếu đã trích xuất được
        if extracted_title and (not article.get("title") or article.get("title") == "Unknown title" or article.get("title").startswith("Tin tức mới nhất về")):
            article["title"] = extracted_title
            logger.info(f"Đã cập nhật tiêu đề: {extracted_title[:50]}...")
        
        # Cập nhật nội dung
        article["content"] = full_content
        
        # Cập nhật tóm tắt (summary) nếu không có hoặc là mặc định
        if not article.get("summary") or article.get("summary").startswith("Bài viết liên quan đến"):
            # Tạo tóm tắt từ 200 ký tự đầu tiên của nội dung
            if full_content:
                words = full_content.split()
                if len(words) > 30:  # Nếu có ít nhất 30 từ
                    summary = " ".join(words[:30]) + "..."
                    article["summary"] = summary
                    logger.info(f"Đã tạo tóm tắt tự động: {summary[:50]}...")
        
        # Xử lý meta_data (có thể là chuỗi JSON hoặc dict)
        if isinstance(article.get("meta_data"), str):
            try:
                meta_data = json.loads(article["meta_data"])
                meta_data["extracted_at"] = datetime.now().isoformat()
                meta_data["word_count"] = len(full_content.split())
                article["meta_data"] = json.dumps(meta_data)
            except json.JSONDecodeError:
                # Nếu không phải JSON hợp lệ, tạo mới
                article["meta_data"] = json.dumps({
                    "extracted_at": datetime.now().isoformat(),
                    "word_count": len(full_content.split())
                })
        else:
            # Xử lý trường hợp là dict
            if not article.get("meta_data"):
                article["meta_data"] = {}
            article["meta_data"]["extracted_at"] = datetime.now().isoformat()
            article["meta_data"]["word_count"] = len(full_content.split())
            
        return article
    except Exception as e:
        logger.error(f"Lỗi khi trích xuất nội dung cho {url}: {str(e)}")
        return article

def send_to_backend(articles):
    """
    Send articles to the Laravel backend API
    
    Args:
        articles (list): List of article data to send
        
    Returns:
        bool: Success status
    """
    try:
        payload = {"articles": articles}
        headers = {"Content-Type": "application/json"}
        
        response = requests.post(BACKEND_API_URL, json=payload, headers=headers)
        
        if response.status_code in (200, 201):
            result = response.json()
            print(f"✅ Đã gửi thành công {result.get('message', '')}")
            if 'errors' in result and result['errors']:
                print(f"⚠️ Có {len(result['errors'])} lỗi trong quá trình import:")
                for error in result['errors']:
                    print(f"  - {error}")
            return True
        else:
            print(f"❌ Lỗi khi gửi bài viết tới backend: {response.status_code} - {response.text}")
            return False
    except Exception as e:
        print(f"❌ Lỗi kết nối tới backend: {str(e)}")
        return False

def main():
    """
    Hàm chính để trích xuất nội dung từ các URL đã thu thập
    Chức năng: Đọc file JSON lưu URL từ google_news_serpapi.py, trích xuất nội dung và tiêu đề, 
    rồi lưu các bài viết đã làm phong phú vào file mới và có thể gửi tới backend.
    """
    # Get input file from command line or use latest scraped file
    if len(sys.argv) > 1:
        input_file = sys.argv[1]
    else:
        # Find latest scraped_articles file
        files = [f for f in os.listdir('.') if f.startswith('scraped_articles_') and f.endswith('.json')]
        if not files:
            print("❌ Không tìm thấy file bài viết. Hãy chạy google_news_serpapi.py trước!")
            return
        input_file = max(files)  # Get most recent file
    
    print(f"📂 Đang đọc dữ liệu từ file: {input_file}")
    
    # Load articles from file
    try:
        with open(input_file, "r", encoding="utf-8") as f:
            articles = json.load(f)
    except Exception as e:
        print(f"❌ Lỗi khi đọc file {input_file}: {str(e)}")
        return
    
    if not articles:
        print("❌ Không có bài viết nào trong file!")
        return
    
    print(f"🔍 Đã tìm thấy {len(articles)} bài viết để xử lý")
    
    # Setup WebDriver
    driver = setup_driver()
    
    try:
        # Process each article to get full content
        enriched_articles = []
        
        for i, article in enumerate(articles):
            print(f"[{i+1}/{len(articles)}] Đang xử lý: {article.get('title', 'Unknown title')}")
            enriched = enrich_article(driver, article)
            if enriched:
                enriched_articles.append(enriched)
            
            # Add delay between requests to avoid overloading servers
            if i < len(articles) - 1:
                time.sleep(2)
        
        # Save enriched articles to file
        output_file = f"enriched_articles_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json"
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(enriched_articles, f, ensure_ascii=False, indent=4)
        
        print(f"✅ Đã lưu {len(enriched_articles)} bài viết đã làm giàu vào {output_file}")
        
        # Ask to send to backend
        if enriched_articles:
            send_option = input("Bạn có muốn gửi bài viết tới backend? (y/n): ").lower()
            if send_option == 'y':
                send_to_backend(enriched_articles)
    
    finally:
        # Clean up
        driver.quit()
        print("🔚 Đã hoàn thành quá trình trích xuất nội dung")

if __name__ == "__main__":
    main()
