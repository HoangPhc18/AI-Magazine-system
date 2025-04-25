import logging
import json
import requests
from .main import ARTICLES_API_URL, ARTICLES_BATCH_API_URL, ARTICLES_IMPORT_API_URL, ARTICLES_CHECK_API_URL
import os

def save_article_to_json(article_data, output_path="data/articles"):
    """Lưu dữ liệu bài báo thành file JSON"""
    # ... existing code ...

def import_articles_to_backend(article_data):
    """Import article data to backend API"""
    # ... existing code ...

def check_article_exists(article_url):
    """Kiểm tra xem bài báo đã tồn tại trong database chưa"""
    try:
        response = requests.post(
            ARTICLES_CHECK_API_URL,
            json={"url": article_url},
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            result = response.json()
            return result.get("exists", False)
        else:
            logging.error(f"Failed to check article existence. Status code: {response.status_code}")
            return False
    except Exception as e:
        logging.error(f"Error checking article existence: {str(e)}")
        return False 