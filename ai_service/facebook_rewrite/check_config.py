#!/usr/bin/env python
import os
import json

def check_config():
    """Kiểm tra cấu hình môi trường Docker"""
    # Lấy tất cả các biến môi trường
    config = {
        "DATABASE": {
            "DB_HOST": os.getenv('DB_HOST', 'không có'),
            "DB_USER": os.getenv('DB_USER', 'không có'),
            "DB_PASSWORD": os.getenv('DB_PASSWORD', 'không có'),
            "DB_NAME": os.getenv('DB_NAME', 'không có'),
        },
        "OLLAMA": {
            "OLLAMA_HOST": os.getenv('OLLAMA_HOST', 'không có'),
            "OLLAMA_MODEL": os.getenv('OLLAMA_MODEL', 'không có'),
        },
        "TIMEOUT": {
            "API_TIMEOUT": os.getenv('API_TIMEOUT', 'không có'),
            "MAX_RETRIES": os.getenv('MAX_RETRIES', 'không có'),
            "INITIAL_BACKOFF": os.getenv('INITIAL_BACKOFF', 'không có'),
            "MAX_TEXT_SIZE": os.getenv('MAX_TEXT_SIZE', 'không có'),
        },
        "SYSTEM": {
            "PORT": os.getenv('PORT', 'không có'),
            "PYTHON_VERSION": os.getenv('PYTHON_VERSION', 'không có'),
        }
    }
    
    # In ra thông tin cấu hình
    print("=== THÔNG TIN CẤU HÌNH ===")
    print(json.dumps(config, indent=2, ensure_ascii=False))
    
    # Kiểm tra kết nối đến Ollama
    print("\n=== KIỂM TRA KẾT NỐI OLLAMA ===")
    ollama_host = os.getenv('OLLAMA_HOST', 'http://localhost:11434')
    print(f"Kiểm tra kết nối đến: {ollama_host}")
    
    try:
        import requests
        response = requests.get(f"{ollama_host}/api/version", timeout=5)
        if response.status_code == 200:
            print(f"Kết nối thành công! Phiên bản: {response.json().get('version', 'unknown')}")
        else:
            print(f"Kết nối không thành công! Mã trạng thái: {response.status_code}")
    except Exception as e:
        print(f"Lỗi kết nối: {str(e)}")
    
    print("\n=== KIỂM TRA TIMEOUT REQUESTS ===")
    try:
        import requests.adapters
        from requests.packages.urllib3.util.timeout import Timeout
        
        # Lấy giá trị timeout mặc định của requests
        default_timeout = Timeout.DEFAULT_TIMEOUT
        print(f"Timeout mặc định của requests: {default_timeout}")
        
        # Kiểm tra timeout đã cấu hình
        configured_timeout = int(os.getenv('API_TIMEOUT', 300))
        print(f"Timeout đã cấu hình: {configured_timeout} giây")
        
        if configured_timeout != 300:
            print("✅ Timeout đã được cấu hình khác giá trị mặc định")
        else:
            print("⚠️ Timeout vẫn là giá trị mặc định (300)!")
    except Exception as e:
        print(f"Lỗi khi kiểm tra timeout: {str(e)}")

if __name__ == "__main__":
    check_config() 