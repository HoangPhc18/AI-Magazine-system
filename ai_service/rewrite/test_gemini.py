#!/usr/bin/env python3
"""
Script kiểm tra tích hợp Gemini
"""

import os
import sys
from dotenv import load_dotenv

# Tải biến môi trường từ .env
load_dotenv()

# Thêm thư mục cha vào sys.path để import được module
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from rewrite import RewriteService
from rewrite.utils import logger

def test_gemini():
    """
    Kiểm tra chức năng viết lại nội dung bằng Gemini
    """
    print("=== Kiểm tra tích hợp Gemini ===")
    
    # Tạo instance RewriteService với provider là gemini
    service = RewriteService()
    
    # Thay đổi provider sang gemini nếu chưa phải
    current_provider = service.get_config().get("model_provider")
    if current_provider != "gemini":
        print(f"Thay đổi provider từ {current_provider} sang gemini")
        service.switch_provider("gemini")
    
    # Kiểm tra thông tin mô hình
    model_info = service.get_model_info()
    print(f"Thông tin mô hình: {model_info}")
    
    # Kiểm tra các provider khả dụng
    providers = service.get_available_providers()
    print(f"Các provider khả dụng: {providers}")
    
    # Viết lại nội dung
    test_content = """
    Trí tuệ nhân tạo (AI) đang thay đổi cách chúng ta làm việc, học tập và sống. 
    Từ trợ lý ảo đến xe tự lái, AI đang trở nên phổ biến trong nhiều lĩnh vực khác nhau. 
    Tuy nhiên, cùng với những lợi ích, AI cũng mang đến nhiều thách thức về đạo đức và quyền riêng tư.
    """
    
    print("\nNội dung gốc:")
    print(test_content)
    
    print("\nĐang viết lại nội dung bằng Gemini...")
    rewritten = service.rewrite(test_content)
    
    print("\nNội dung đã viết lại:")
    print(rewritten)
    
    print("\n=== Kiểm tra hoàn tất ===")

if __name__ == "__main__":
    try:
        test_gemini()
    except Exception as e:
        logger.error(f"Lỗi khi kiểm tra tích hợp Gemini: {str(e)}")
        print(f"Lỗi: {str(e)}")
        sys.exit(1) 