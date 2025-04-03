#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Command-line interface (CLI) cho dịch vụ viết lại nội dung
"""

import os
import sys
import argparse
import json
from typing import Dict, List, Any, Optional, Union
from pathlib import Path
from dotenv import load_dotenv

# Add parent directory to path to enable direct imports
parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, parent_dir)

# Direct imports without relative paths
from rewrite.rewrite import RewriteService, rewrite_content
from rewrite.utils import (
    logger, 
    DEFAULT_MODEL_GPT, 
    DEFAULT_MODEL_OLLAMA,
    DEFAULT_CONFIG,
    save_config,
    check_api_keys,
    is_ollama_available
)

# Tải biến môi trường từ file .env nếu có
load_dotenv()

def parse_args():
    """
    Phân tích tham số dòng lệnh
    
    Returns:
        argparse.Namespace: Các tham số
    """
    parser = argparse.ArgumentParser(
        description="Dịch vụ viết lại nội dung bài viết bằng AI",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter
    )
    
    # Tham số chung
    parser.add_argument("--provider", "-p", choices=["openai", "ollama"], default="openai",
                        help="Nhà cung cấp mô hình AI")
    parser.add_argument("--config", "-c", type=str, default=None,
                        help="Đường dẫn đến file cấu hình")
    parser.add_argument("--verbose", "-v", action="store_true",
                        help="Hiển thị nhiều thông tin hơn")
                        
    # Các lệnh con
    subparsers = parser.add_subparsers(dest="command", help="Các lệnh")
    
    # Lệnh rewrite - viết lại nội dung
    rewrite_parser = subparsers.add_parser("rewrite", help="Viết lại nội dung")
    rewrite_source = rewrite_parser.add_mutually_exclusive_group(required=True)
    rewrite_source.add_argument("--text", "-t", type=str,
                               help="Nội dung cần viết lại")
    rewrite_source.add_argument("--file", "-f", type=str,
                               help="Đường dẫn đến file chứa nội dung cần viết lại")
    rewrite_parser.add_argument("--output", "-o", type=str, default=None,
                              help="Đường dẫn đến file đầu ra (nếu không cung cấp, in ra console)")
    rewrite_parser.add_argument("--prompt", type=str, default=None,
                              help="Prompt tùy chỉnh để viết lại nội dung")
    
    # Tham số riêng cho OpenAI
    openai_group = rewrite_parser.add_argument_group("OpenAI")
    openai_group.add_argument("--api-key", type=str, default=None,
                             help="OpenAI API key (nếu không cung cấp, sử dụng biến môi trường OPENAI_API_KEY)")
    openai_group.add_argument("--model", type=str, default=DEFAULT_MODEL_GPT,
                             help="Tên mô hình OpenAI")
    
    # Tham số riêng cho Ollama
    ollama_group = rewrite_parser.add_argument_group("Ollama")
    ollama_group.add_argument("--ollama-model", type=str, default=DEFAULT_MODEL_OLLAMA,
                             help="Tên mô hình Ollama")
    ollama_group.add_argument("--ollama-url", type=str, default="http://localhost:11434",
                             help="URL của Ollama API")
    
    # Tham số chung cho cả hai
    rewrite_parser.add_argument("--temperature", type=float, default=0.7,
                              help="Nhiệt độ sinh văn bản (0.0 - 1.0)")
    
    # Lệnh config - quản lý cấu hình
    config_parser = subparsers.add_parser("config", help="Quản lý cấu hình")
    config_cmd = config_parser.add_mutually_exclusive_group(required=True)
    config_cmd.add_argument("--show", action="store_true",
                           help="Hiển thị cấu hình hiện tại")
    config_cmd.add_argument("--set", nargs=2, metavar=("KEY", "VALUE"), action="append",
                          help="Đặt giá trị cấu hình (VD: --set openai.api_key sk-xxx)")
    config_cmd.add_argument("--reset", action="store_true",
                           help="Đặt lại cấu hình về mặc định")
    
    # Lệnh info - hiển thị thông tin
    info_parser = subparsers.add_parser("info", help="Hiển thị thông tin")
    
    return parser.parse_args()

def handle_rewrite(args):
    """
    Xử lý lệnh viết lại
    
    Args:
        args (argparse.Namespace): Các tham số
    """
    # Lấy nội dung từ văn bản hoặc file
    if args.text:
        content = args.text
    else:
        try:
            with open(args.file, "r", encoding="utf-8") as f:
                content = f.read()
        except Exception as e:
            logger.error(f"Không thể đọc file {args.file}: {str(e)}")
            sys.exit(1)
            
    # Cấu hình cho nhà cung cấp
    if args.provider == "openai":
        provider_config = {
            "api_key": args.api_key,
            "model": args.model,
            "temperature": args.temperature
        }
    else:  # ollama
        provider_config = {
            "model": args.ollama_model,
            "temperature": args.temperature,
            "base_url": args.ollama_url
        }
        
    # Khởi tạo dịch vụ hoặc sử dụng hàm tiện ích
    try:
        if args.config:
            service = RewriteService(args.config)
            # Cập nhật cấu hình tạm thời
            for key, value in provider_config.items():
                if value is not None:
                    service.update_config(args.provider, key, value)
            rewritten = service.rewrite(content, args.prompt)
        else:
            # Sử dụng hàm tiện ích khi không có file cấu hình
            api_key = args.api_key if args.provider == "openai" else None
            model = args.model if args.provider == "openai" else args.ollama_model
            rewritten = rewrite_content(
                content=content,
                provider=args.provider,
                api_key=api_key,
                model=model,
                temperature=args.temperature
            )
            
        # Ghi kết quả vào file hoặc in ra console
        if args.output:
            try:
                with open(args.output, "w", encoding="utf-8") as f:
                    f.write(rewritten)
                logger.info(f"Đã lưu nội dung đã viết lại vào {args.output}")
            except Exception as e:
                logger.error(f"Không thể ghi vào file {args.output}: {str(e)}")
                sys.exit(1)
        else:
            print("\n=== NỘI DUNG ĐÃ VIẾT LẠI ===\n")
            print(rewritten)
            print("\n==============================\n")
            
    except Exception as e:
        logger.error(f"Lỗi khi viết lại nội dung: {str(e)}")
        sys.exit(1)

def handle_config(args):
    """
    Xử lý lệnh cấu hình
    
    Args:
        args (argparse.Namespace): Các tham số
    """
    # Tạo đường dẫn cấu hình mặc định nếu không cung cấp
    config_path = args.config
    if not config_path:
        home_dir = Path.home()
        config_dir = home_dir / ".rewrite"
        os.makedirs(config_dir, exist_ok=True)
        config_path = str(config_dir / "config.json")
        
    # Tạo đối tượng dịch vụ
    service = RewriteService(config_path)
    
    if args.show:
        # Hiển thị cấu hình hiện tại
        config = service.get_config()
        print(json.dumps(config, indent=2, ensure_ascii=False))
        
    elif args.set:
        # Đặt giá trị cấu hình
        for key, value in args.set:
            # Xử lý key dạng a.b.c
            keys = key.split(".")
            
            if len(keys) == 1:
                # Key đơn giản
                service.config[keys[0]] = value
            elif len(keys) == 2:
                # Key dạng provider.setting
                provider, setting = keys
                service.update_config(provider, setting, value)
            else:
                logger.error(f"Định dạng key không hợp lệ: {key}")
                continue
                
        # Lưu cấu hình
        save_config(service.config, config_path)
        logger.info(f"Đã cập nhật cấu hình và lưu vào {config_path}")
        
    elif args.reset:
        # Đặt lại cấu hình về mặc định
        save_config(DEFAULT_CONFIG, config_path)
        logger.info(f"Đã đặt lại cấu hình về mặc định và lưu vào {config_path}")

def handle_info(args):
    """
    Xử lý lệnh thông tin
    
    Args:
        args (argparse.Namespace): Các tham số
    """
    # Kiểm tra các nhà cung cấp khả dụng
    api_keys = check_api_keys()
    ollama_available = is_ollama_available()
    
    print("\n=== THÔNG TIN DỊCH VỤ VIẾT LẠI ===\n")
    
    print("Nhà cung cấp khả dụng:")
    print(f"  - OpenAI API: {'Có' if api_keys.get('openai', False) else 'Không'}")
    print(f"  - Ollama local: {'Có' if ollama_available else 'Không'}")
    
    print("\nMô hình mặc định:")
    print(f"  - OpenAI: {DEFAULT_MODEL_GPT}")
    print(f"  - Ollama: {DEFAULT_MODEL_OLLAMA}")
    
    print("\nCấu hình:")
    if args.config:
        service = RewriteService(args.config)
        config = service.get_config()
        print(f"  - Đường dẫn: {args.config}")
        print(f"  - Nhà cung cấp hiện tại: {config.get('model_provider', 'openai')}")
    else:
        print("  - Sử dụng cấu hình mặc định")
        
    print("\n=================================\n")

def main():
    """
    Hàm chính của CLI
    """
    args = parse_args()
    
    # Thiết lập mức độ chi tiết logging
    if args.verbose:
        logger.setLevel("DEBUG")
    
    # Xử lý các lệnh
    if args.command == "rewrite":
        handle_rewrite(args)
    elif args.command == "config":
        handle_config(args)
    elif args.command == "info":
        handle_info(args)
    else:
        # Nếu không có lệnh, hiển thị trợ giúp
        print("Sử dụng --help để xem hướng dẫn sử dụng")
        
if __name__ == "__main__":
    main() 