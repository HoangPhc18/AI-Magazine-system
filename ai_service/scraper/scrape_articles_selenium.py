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
            "title": ["h1.title-detail", "h1.title-post", ".title-news", ".title_news_detail"],
            "content": ["article.fck_detail", "div.fck_detail", ".content_detail", "article.content-detail"],
            "paragraphs": ["p.Normal", "p"],
            "exclude": [".author", ".copyright", ".relatebox", ".box-tag", ".social_pin", ".list-news", ".width_common", ".footer", ".header"]
        },
        # Tuổi Trẻ
        "tuoitre.vn": {
            "title": ["h1.article-title", "h1.title-2", "h1.detail-title", ".article-title"],
            "content": ["div.content.fck", "#main-detail-body", "div.detail-content", "#mainContent", ".content-news", ".detail-content-body"],
            "paragraphs": ["p"],
            "exclude": [".VCSortableInPreviewMode", ".relate-container", ".source", ".author", ".date-time"]
        },
        # Dân Trí
        "dantri.com.vn": {
            "title": ["h1.title-page", "h1.e-title", "h1.title", ".e-magazine", ".title-news"],
            "content": ["div.dt-news__content", "div.e-content", ".singular-content", ".article-body", ".dt-news__body"],
            "paragraphs": ["p"],
            "exclude": [".dt-news__sapo", ".author-info", ".article-topic", ".e-magazineplus", ".dt-news__meta"]
        },
        # Thanh Niên
        "thanhnien.vn": {
            "title": ["h1.detail-title", "h1.cms-title", ".details__headline", "h1.title-news"],
            "content": ["div.detail-content", "div.cms-body", ".l-content", ".details__content", "#abody", "#content-id"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source", ".details__meta", ".details__author", ".related-container"]
        },
        # VietnamNet
        "vietnamnet.vn": {
            "title": ["h1.content-detail-title", "h1.title", ".content-title", "h1.title-item-news", ".title-content"],
            "content": ["div.content-detail", ".ArticleContent", "#article-body", ".articleContent", ".boxPostDetail", ".detail-content"],
            "paragraphs": ["p"],
            "exclude": [".author-info", ".article-relate", ".box-taitro", ".box-title", ".article-tags"]
        },
        # Nhân Dân
        "nhandan.vn": {
            "title": ["div.box-title h1", ".nd-detail-title", ".title-detail", ".article-title", "h1.article-title"],
            "content": ["div.box-content-detail", "#nd-article-content", ".article-body", ".detail-content-wrap"],
            "paragraphs": ["p"],
            "exclude": [".box-author", ".article-meta", ".box-share"]
        },
        # Tiền Phong
        "tienphong.vn": {
            "title": ["h1.article__title", "h1.cms-title", ".headline", ".main-article-title", "h1.article-title"],
            "content": ["div.article__body", ".cms-body", ".article-body", ".article-content", ".main-article-body"],
            "paragraphs": ["p"],
            "exclude": [".article__author", ".article__tag", ".article__share", ".article__meta"]
        },
        # Báo Mới
        "baomoi.com": {
            "title": ["h1.bm-title", "h1.title", ".article-title", ".article-header", ".title", ".headline"],
            "content": ["div.content", ".bm-content", ".article-body", ".story__content", ".article__content"],
            "paragraphs": ["p"],
            "exclude": [".bm-source", ".bm-resource", ".relate-container", ".bm-avatar", ".top-comments"]
        },
        # Zing News
        "zingnews.vn": {
            "title": ["h1.the-article-title", "h1.article-title", ".the-article-header", ".page-title", ".article-header h1"],
            "content": ["div.the-article-body", "article.the-article-content", ".the-article-content", ".article-content"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source", ".article-tags", ".article-related", ".the-article-tags", ".the-article-meta"]
        },
        # 24h
        "24h.com.vn": {
            "title": ["h1.bld", "h1.clrTit", ".titCM", ".tuht-dts", "article h1"],
            "content": ["div.text-conent", "div.baiviet-bailienquan", ".colCenter-in", ".boxDtlBody", "article .ctTp"],
            "paragraphs": ["p"],
            "exclude": [".nguontin", ".baiviet-tags", ".bv-cp", ".fb-like", ".imgCation"]
        },
        # CafeF
        "cafef.vn": {
            "title": ["h1.title", ".articledetail_title", ".title-detail", "h1.title_detail"],
            "content": ["div#mainContent", ".contentdetail", ".article-body", "#CMS_Detail", "#content_detail_news"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source", ".relationnews", ".tindlienquan"]
        },
        # VOV
        "vov.vn": {
            "title": ["h1.article-title", ".main-article h1", ".cms-title", ".vovtitle"],
            "content": ["div.article-body", ".main-article-body", ".article-content", "#article_content", ".vov-content"],
            "paragraphs": ["p"],
            "exclude": [".article-author", ".article-tools", ".article-related"]
        },
        # Lao Động
        "laodong.vn": {
            "title": ["h1.title", ".article-title", ".headline", "h1.headline_detail"],
            "content": ["div.article-content", ".cms-body", ".contentbody", ".detail-content-body", "#box_details"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source", ".article-meta", ".article-tools", ".boxrelation"]
        },
        # Báo Thanh tra
        "thanhtra.com.vn": {
            "title": ["h1.title", ".article-title", ".news-title"],
            "content": [".article-body", ".news-content", ".content-body"],
            "paragraphs": ["p"],
            "exclude": [".author", ".article-info", ".tags"]
        },
        # Pháp Luật TP.HCM
        "plo.vn": {
            "title": ["h1.title", ".article__title", ".article-title"],
            "content": [".article__body", ".article-content", ".cms-body"],
            "paragraphs": ["p"],
            "exclude": [".author", ".source", ".tags-container"]
        },
        # Sài Gòn Giải Phóng
        "sggp.org.vn": {
            "title": ["h1.title", ".cms-title", ".article-title"],
            "content": [".article-content", ".cms-body", "#content_detail_news"],
            "paragraphs": ["p"],
            "exclude": [".author", ".nguon", ".source"]
        }
    }
    
    # Mặc định cho các trang không có cấu hình cụ thể
    default_selectors = {
        "title": ["h1", "h1.title", "h1.article-title", ".headline", ".article-headline", ".entry-title", ".post-title", ".main-title"],
        "content": ["article", "main", ".content", ".article-content", ".entry-content", ".post-content", ".news-content", ".main-content", "#content", "#main"],
        "paragraphs": ["p"],
        "exclude": [".comments", ".sidebar", ".related", ".footer", ".header", ".navigation", ".menu", ".ads", ".social", ".sharing", ".tags", ".author", ".meta", ".date"]
    }
    
    # Trả về bộ chọn tùy chỉnh hoặc mặc định
    for key in selectors:
        if key in domain:
            return selectors[key]
            
    return default_selectors

def extract_content(driver, url, title="Unknown title"):
    """
    Trích xuất nội dung dạng văn bản thuần túy từ URL bài viết, với xử lý tùy chỉnh cho các trang tin tức phổ biến.
    Giữ nguyên nội dung tiêu đề và bài viết không sửa đổi để lưu vào database.
    
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
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight * 0.3);")
        time.sleep(1.5)
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight * 0.7);")
        time.sleep(1.5)
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(1.5)
        
        # Lấy nội dung trang
        page_content = driver.page_source
        
        # Sử dụng BeautifulSoup để phân tích HTML
        soup = BeautifulSoup(page_content, "html.parser")
        
        # Xác định tên miền để áp dụng selector phù hợp
        domain = urlparse(url).netloc
        logger.info(f"Đang xử lý trang web: {domain}")
        
        # Lấy các bộ chọn tùy chỉnh theo trang web
        selectors = get_site_specific_selectors(url)
        
        # ---------- Trích xuất tiêu đề ----------
        extracted_title = ""
        
        # Thử các bộ chọn tiêu đề tùy chỉnh
        for selector in selectors["title"]:
            title_elements = soup.select(selector)
            if title_elements:
                for title_element in title_elements:
                    if title_element and title_element.text.strip():
                        extracted_title = title_element.text.strip()
                        logger.info(f"Đã tìm thấy tiêu đề từ bộ chọn '{selector}': {extracted_title[:50]}...")
                        break
                if extracted_title:
                    break
        
        # Nếu không tìm thấy, thử tìm từ các thẻ h1/h2 phổ biến
        if not extracted_title:
            heading_tags = soup.find_all(['h1', 'h2'], limit=5)
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
            content_elements = soup.select(selector)
            if content_elements:
                # Chọn phần tử nội dung lớn nhất (có nhiều text nhất)
                max_length = 0
                for element in content_elements:
                    text_length = len(element.get_text())
                    if text_length > max_length:
                        max_length = text_length
                        content_element = element
                
                if content_element:
                    logger.info(f"Đã tìm thấy phần tử nội dung từ bộ chọn '{selector}' ({max_length} ký tự)")
                    break
        
        # Trích xuất nội dung văn bản thuần túy
        if content_element:
            # Trước khi trích xuất, loại bỏ các phần tử không mong muốn từ nội dung
            for exclude_selector in selectors["exclude"]:
                for element in content_element.select(exclude_selector):
                    element.extract()
            
            # Lấy văn bản thuần túy, giữ nguyên cách đoạn
            paragraphs = []
            
            # Tìm tất cả các đoạn văn trong phần tử nội dung
            if "," in ", ".join(selectors["paragraphs"]):
                p_elements = content_element.select(", ".join(selectors["paragraphs"]))
            else:
                p_elements = content_element.find_all(selectors["paragraphs"][0])
                
            # Nếu không tìm thấy đoạn văn, lấy tất cả văn bản trong phần tử nội dung
            if not p_elements or len(p_elements) < 3:
                # Thử tìm tất cả các thẻ text nói chung
                all_text_elements = [el for el in content_element.find_all(text=True) if el.parent.name not in ['script', 'style']]
                clean_text = []
                for el in all_text_elements:
                    text = el.strip()
                    if text and len(text) > 10:
                        clean_text.append(text)
                
                if clean_text:
                    full_content = "\n\n".join(clean_text)
                else:
                    full_content = content_element.get_text(separator="\n\n", strip=True)
                
                logger.info(f"Đã trích xuất nội dung văn bản ({len(full_content.split())} từ) từ: {url}")
            else:
                # Lấy nội dung từ từng đoạn văn
                for p in p_elements:
                    text = p.text.strip()
                    if text:  # Lấy tất cả đoạn văn có nội dung
                        paragraphs.append(text)
                
                full_content = "\n\n".join(paragraphs)
                logger.info(f"Đã trích xuất nội dung từ {len(paragraphs)} đoạn văn, tổng cộng {len(full_content.split())} từ")
        else:
            # Nếu không tìm thấy phần tử nội dung, thử phương pháp chủ động hơn
            logger.warning(f"Không tìm thấy phần tử nội dung cụ thể cho URL: {url}, dùng phương pháp dự phòng")
            
            # Phương pháp 1: Tìm phần tử có nhiều thẻ <p> nhất
            article_candidates = []
            
            for tag in ['article', 'main', 'div.content', 'div.article', '.detail', '#detail', '#content']:
                elements = soup.select(tag)
                for element in elements:
                    p_count = len(element.find_all('p'))
                    if p_count >= 3:  # Ít nhất 3 đoạn văn
                        article_candidates.append((element, p_count))
            
            if article_candidates:
                # Sắp xếp theo số lượng thẻ p, lấy phần tử có nhiều thẻ p nhất
                article_candidates.sort(key=lambda x: x[1], reverse=True)
                content_element = article_candidates[0][0]
                logger.info(f"Đã tìm thấy phần tử nội dung bằng phương pháp phân tích cấu trúc ({article_candidates[0][1]} đoạn văn)")
                
                # Lấy tất cả các đoạn văn từ phần tử này
                paragraphs = []
                for p in content_element.find_all('p'):
                    text = p.text.strip()
                    if text and len(text) > 15:  # Chỉ lấy đoạn văn có đủ nội dung
                        paragraphs.append(text)
                
                if paragraphs:
                    full_content = "\n\n".join(paragraphs)
                    logger.info(f"Đã trích xuất nội dung dự phòng từ {len(paragraphs)} đoạn văn, tổng cộng {len(full_content.split())} từ")
                else:
                    # Không có đoạn văn, lấy tất cả văn bản
                    full_content = content_element.get_text(separator="\n\n", strip=True)
                    logger.info(f"Đã trích xuất toàn bộ văn bản từ phần tử nội dung ({len(full_content.split())} từ)")
            else:
                # Phương pháp 2: Lấy tất cả các đoạn văn từ trang
                paragraphs = []
                for p in soup.find_all('p'):
                    text = p.text.strip()
                    if text and len(text) > 20:  # Chỉ lấy đoạn văn có đủ nội dung
                        paragraphs.append(text)
                
                if paragraphs:
                    full_content = "\n\n".join(paragraphs)
                    logger.info(f"Đã trích xuất nội dung từ tất cả các đoạn văn trên trang ({len(paragraphs)} đoạn)")
                else:
                    # Phương pháp cuối cùng: lấy toàn bộ văn bản từ trang
                    main_content = soup.find('main') or soup.find('article') or soup.find('body')
                    full_content = main_content.get_text(separator="\n\n", strip=True)
                    logger.info(f"Đã trích xuất văn bản tổng thể ({len(full_content.split())} từ)")
        
        # Loại bỏ các dòng trống và làm sạch nội dung
        if full_content:
            # Loại bỏ khoảng trắng dư thừa
            full_content = re.sub(r'\n{3,}', '\n\n', full_content)
            
            # Nếu nội dung quá ngắn, thông báo cảnh báo
            if len(full_content.split()) < 50:
                logger.warning(f"Nội dung trích xuất quá ngắn ({len(full_content.split())} từ), có thể không chính xác")
        
        return extracted_title, full_content
    
    except Exception as e:
        logger.error(f"Lỗi khi trích xuất nội dung từ {url}: {str(e)}")
        import traceback
        logger.error(traceback.format_exc())
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
        if full_content:
            article["content"] = full_content
            logger.info(f"Đã cập nhật nội dung văn bản cho bài viết ({len(full_content.split())} từ)")
        
        # Cập nhật tóm tắt (summary) nếu không có hoặc là mặc định
        if not article.get("summary") or article.get("summary").startswith("Bài viết liên quan đến"):
            # Tạo tóm tắt từ nội dung văn bản
            if full_content:
                words = full_content.split()
                if len(words) > 30:
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
        
        print(f"🚀 Đang gửi {len(articles)} bài viết tới backend...")
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
    # Check for auto_send argument
    auto_send = "--auto-send" in sys.argv
    
    # Get input file from command line or use latest scraped file
    input_file = None
    for arg in sys.argv[1:]:
        if not arg.startswith("--") and arg.endswith(".json"):
            input_file = arg
            break
            
    if not input_file:
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
        
        # Automatically send to backend if there are articles
        if enriched_articles:
            send_to_backend(enriched_articles)
    
    finally:
        # Clean up
        driver.quit()
        print("🔚 Đã hoàn thành quá trình trích xuất nội dung")

if __name__ == "__main__":
    main()
