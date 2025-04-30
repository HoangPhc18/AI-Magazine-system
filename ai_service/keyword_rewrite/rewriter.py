#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module xử lý việc viết lại nội dung bài viết sử dụng Google Gemini API.
"""

import os
import sys
import logging
import time
import json
import requests
import google.generativeai as genai

# Import module config
from config import get_config, reload_config

# Tải cấu hình
config = get_config()

# Thiết lập logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler("rewriter.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

def rewrite_content(title, content, model=None, temperature=0.6, max_tokens=4000):
    """
    Viết lại nội dung bài viết sử dụng Gemini API.
    
    Args:
        title (str): Tiêu đề bài viết
        content (str): Nội dung bài viết gốc
        model (str, optional): Tên model Gemini sử dụng
        temperature (float, optional): Nhiệt độ cho việc sinh văn bản
        max_tokens (int, optional): Số token tối đa cho đầu ra
        
    Returns:
        str: Nội dung đã được viết lại
    """
    start_time = time.time()
    
    # Lấy cấu hình mới nhất
    current_config = get_config()
    
    # Nếu không cung cấp model, sử dụng từ cấu hình
    if not model:
        model = current_config.get("GEMINI_MODEL", "gemini-1.5-flash-latest")
    
    # Kiểm tra API key
    api_key = current_config.get("GEMINI_API_KEY", "")
    if not api_key:
        error_msg = "Error: Missing Gemini API key"
        logger.error(error_msg)
        return error_msg
    
    logger.info(f"Rewriting content with title: '{title[:50]}...' using model: {model}")
    
    try:
        # Cấu hình Gemini API
        genai.configure(api_key=api_key)
    
        # Chuẩn bị prompt
        prompt = f"""Bạn là biên tập viên của một trang tin tức hàng đầu. Nhiệm vụ của bạn là viết lại một bài báo dựa trên nội dung gốc được cung cấp.

Tiêu đề bài báo gốc: {title}

Nội dung bài báo gốc:
{content[:10000]}

Hướng dẫn:
1. Viết lại nội dung thành một bài báo hoàn chỉnh, chuyên nghiệp với độ dài khoảng 500-1000 từ.
2. Giữ nguyên ý nghĩa và thông tin quan trọng từ bài gốc.
3. Viết với giọng điệu trang trọng, chuyên nghiệp phù hợp với báo chí.
4. Đảm bảo bài viết mới có cấu trúc rõ ràng gồm: mở đầu hấp dẫn, thân bài phát triển ý, và kết luận mạnh mẽ.
5. Tránh sao chép câu văn từ bài gốc, hãy diễn đạt lại bằng từ ngữ của riêng bạn.
6. Tối ưu hóa bài viết cho SEO nhưng không làm giảm chất lượng nội dung.
7. QUAN TRỌNG: CHỈ TRẢ VỀ NỘI DUNG BÀI VIẾT ĐÃ VIẾT LẠI, KHÔNG THÊM BẤT KỲ CHÚ THÍCH, GIẢI THÍCH HAY HƯỚNG DẪN NÀO KHÁC.

Trả về bài viết đã viết lại:"""
        
        # Chuẩn bị tham số cho model
        generation_config = {
                "temperature": temperature,
                "max_output_tokens": max_tokens,
            "top_p": 0.95,
            "top_k": 50
        }
        
        # Khởi tạo model
        model = genai.GenerativeModel(model_name=model, generation_config=generation_config)
        
        # Tạo văn bản
        logger.info("Generating content with Gemini API...")
        
        # Tạo response
        response = model.generate_content(prompt)
        
        if not response or not response.text:
            error_msg = "Error: Empty response from Gemini API"
            logger.error(error_msg)
            return error_msg
        
        # Lấy kết quả
        result = response.text.strip()
        
        # Đo thời gian thực hiện
        duration = time.time() - start_time
        logger.info(f"Content generation completed in {duration:.2f} seconds")
            
        return result
            
    except Exception as e:
        error_msg = f"Error: {str(e)}"
        logger.error(error_msg)
        logger.exception("Exception details:")
        return error_msg

if __name__ == "__main__":
    import sys

    # Lấy tên file từ tham số dòng lệnh, hoặc sử dụng mặc định
    filename = sys.argv[1] if len(sys.argv) > 1 else "test_article.txt"

    try:
        with open(filename, 'r', encoding='utf-8') as file:
            content = file.read()
            title = "Tiêu đề bài viết test"
            
            print(f"Processing file: {filename}")
            result = rewrite_content(title, content)
            print("\n" + "="*50 + " RESULT " + "="*50 + "\n")
            print(result)
            
    except FileNotFoundError:
        print(f"File not found: {filename}")
        print("Usage: python rewriter.py [filename]")
        sys.exit(1)
