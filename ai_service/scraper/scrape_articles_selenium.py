import json
import time
import sys
import os
import requests
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

# 🔹 Laravel Backend API URL - Update as needed
BACKEND_API_URL = "http://localhost:8000/api/articles/import"

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

def extract_content(driver, url, title="Unknown title"):
    """
    Trích xuất nội dung có cấu trúc từ URL bài viết, tập trung vào tiêu đề và thẻ p
    
    Args:
        driver (WebDriver): Driver Selenium
        url (str): URL của bài viết
        title (str): Tiêu đề bài viết
        
    Returns:
        str: Nội dung bài viết
    """
    try:
        print(f"📄 Đang truy cập URL: {url}")
        driver.get(url)
        
        # Chờ nội dung tải xong
        WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        
        # Lấy nội dung trang
        page_content = driver.page_source
        
        # Sử dụng BeautifulSoup để phân tích HTML
        soup = BeautifulSoup(page_content, "html.parser")
        
        # Trích xuất tiêu đề từ thẻ h1 hoặc các thẻ tiêu đề phổ biến
        extracted_title = ""
        heading_tags = soup.find_all(['h1', 'h2'])
        for tag in heading_tags:
            if tag.text.strip() and len(tag.text.strip()) > 10:
                extracted_title = tag.text.strip()
                break
        
        # Trích xuất nội dung từ các thẻ p (paragraph)
        content_paragraphs = []
        paragraphs = soup.find_all('p')
        
        for p in paragraphs:
            # Lọc các đoạn văn có nghĩa (loại bỏ các đoạn quá ngắn hoặc là thông tin phụ)
            text = p.text.strip()
            if text and len(text) > 20:  # Chỉ lấy đoạn văn dài hơn 20 ký tự
                content_paragraphs.append(text)
        
        # Kết hợp nội dung thành một chuỗi văn bản
        full_content = ""
        
        # Thêm tiêu đề vào nội dung (nếu tìm thấy)
        if extracted_title:
            full_content += extracted_title + "\n\n"
        
        # Thêm nội dung của các đoạn văn
        if content_paragraphs:
            full_content += "\n\n".join(content_paragraphs)
        else:
            # Nếu không tìm thấy thẻ p có nội dung, thử phương pháp trích xuất văn bản thô
            logging.warning(f"Không tìm thấy nội dung từ thẻ p cho URL: {url}, dùng phương pháp dự phòng")
            
            # Loại bỏ các phần tử script, style, nav, header, footer, ads
            for element in soup(['script', 'style', 'nav', 'header', 'footer', 'iframe', 'aside']):
                element.extract()
            
            # Lấy văn bản
            text = soup.get_text()
            
            # Xử lý văn bản
            lines = (line.strip() for line in text.splitlines())
            chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
            full_content = "\n".join(chunk for chunk in chunks if chunk)
        
        word_count = len(full_content.split())
        print(f"✅ Trích xuất thành công nội dung ({word_count} từ) từ: {title}")
        
        return full_content
    
    except Exception as e:
        logging.error(f"Lỗi khi trích xuất nội dung từ {url}: {str(e)}")
        print(f"⚠️ Không thể trích xuất nội dung từ: {title}")
        return ""

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
        logging.warning(f"Bỏ qua URL video không phù hợp: {url}")
        return False
    
    return True

def enrich_article(driver, article):
    """
    Làm phong phú thêm dữ liệu bài viết bằng cách trích xuất nội dung đầy đủ từ URL
    
    Args:
        driver (WebDriver): Driver Selenium
        article (dict): Thông tin bài viết
        
    Returns:
        dict: Bài viết đã được làm phong phú thêm
    """
    url = article.get("source_url")
    # Bỏ qua nếu không có URL
    if not url:
        logging.warning(f"Không có URL cho bài viết: {article.get('title', 'Unknown title')}")
        return article
    
    # Kiểm tra URL có phù hợp hay không
    if not filter_article(url):
        logging.info(f"Bỏ qua URL không phù hợp: {url}")
        return None
    
    try:
        full_content = extract_content(driver, url, article.get("title", "Unknown title"))
        # Cập nhật nội dung
        article["content"] = full_content
        
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
        logging.error(f"Lỗi khi trích xuất nội dung cho {url}: {str(e)}")
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
    """Main function to orchestrate the content enrichment process"""
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
            print(f"[{i+1}/{len(articles)}] Đang xử lý: {article['title']}")
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
