#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Script để chuẩn bị dữ liệu JSON cho API endpoint import của Laravel
- Chuyển đổi cấu trúc dữ liệu để phù hợp với API
- Sửa các trường không đúng định dạng
"""

import os
import json
import sys
import argparse
from datetime import datetime
import re


def parse_date(date_str):
    """
    Phân tích chuỗi ngày tháng sang định dạng ISO cho Laravel
    
    Args:
        date_str (str): Chuỗi ngày tháng đầu vào
        
    Returns:
        str: Chuỗi ngày tháng theo định dạng ISO 8601
    """
    date_str = date_str.strip()
    
    # Tìm kiếm các mẫu phổ biến và chuyển đổi
    try:
        # Mẫu: 04/02/2025, 09:49 AM, +0000 UTC
        if re.match(r'\d{2}/\d{2}/\d{4},\s+\d{2}:\d{2}\s+[AP]M,\s+\+\d{4}\s+UTC', date_str):
            # Tách các phần từ định dạng đặc biệt này
            date_part, time_part = date_str.split(',', 1)
            time_with_zone = time_part.strip()
            
            # Tách AM/PM ra khỏi chuỗi
            time_match = re.match(r'(\d{2}:\d{2})\s+([AP]M)', time_with_zone)
            if time_match:
                time_str = time_match.group(1)
                am_pm = time_match.group(2)
                
                # Xử lý giờ 12h
                hour, minute = map(int, time_str.split(':'))
                if am_pm == 'PM' and hour < 12:
                    hour += 12
                elif am_pm == 'AM' and hour == 12:
                    hour = 0
                
                # Định dạng lại thành chuỗi thời gian 24h
                time_str = f"{hour:02d}:{minute:02d}:00"
                
                # Xử lý định dạng ngày tháng
                day, month, year = map(int, date_part.split('/'))
                
                # Tạo chuỗi ISO
                iso_date = f"{year}-{month:02d}-{day:02d}T{time_str}Z"
                return iso_date
            
        # Thử phân tích với datetime nếu là định dạng dễ nhận dạng
        return datetime.fromisoformat(date_str.replace('Z', '+00:00')).isoformat()
    
    except (ValueError, AttributeError) as e:
        print(f"Không thể phân tích chuỗi ngày '{date_str}': {str(e)}")
        # Trả về ngày hiện tại nếu không thể phân tích
        return datetime.now().isoformat()


def fix_json_for_api(input_file, output_file=None):
    """
    Chuyển đổi cấu trúc JSON để phù hợp với API Laravel
    
    Args:
        input_file (str): Đường dẫn file JSON đầu vào
        output_file (str): Đường dẫn file JSON đầu ra (tùy chọn)
        
    Returns:
        str: Đường dẫn file JSON đã được sửa
    """
    try:
        with open(input_file, "r", encoding="utf-8") as f:
            articles = json.load(f)
        
        if not articles:
            print(f"❌ Không có bài viết nào trong file {input_file}")
            return None
        
        print(f"📂 Đang xử lý {len(articles)} bài viết từ file {input_file}")
        
        # Chuyển đổi cấu trúc cho từng bài viết
        for article in articles:
            # Sửa trường source_name
            if isinstance(article.get("source_name"), dict):
                # Lấy tên nguồn từ đối tượng
                source_name_dict = article["source_name"]
                article["source_name"] = source_name_dict.get("name", "Unknown Source")
            
            # Chuyển đổi published_at thành định dạng ISO
            if "published_at" in article:
                article["published_at"] = parse_date(article["published_at"])
            
            # Cũng cập nhật trường date để giữ nhất quán
            if "date" in article:
                article["date"] = article.get("published_at", datetime.now().isoformat())
            
            # Chuyển đổi meta_data thành kiểu chuỗi JSON
            if isinstance(article.get("meta_data"), dict):
                article["meta_data"] = json.dumps(article["meta_data"])
            elif article.get("meta_data") is None:
                article["meta_data"] = json.dumps({})
            
            # Xử lý trường summary nếu là None
            if article.get("summary") is None:
                article["summary"] = ""
        
        # Lưu dữ liệu đã được sửa
        if not output_file:
            output_file = f"api_ready_{os.path.basename(input_file)}"
        
        with open(output_file, "w", encoding="utf-8") as f:
            json.dump(articles, f, ensure_ascii=False, indent=4)
        
        print(f"✅ Đã lưu dữ liệu đã sửa vào {output_file}")
        return output_file
    
    except Exception as e:
        print(f"❌ Lỗi khi xử lý file {input_file}: {str(e)}")
        return None


def main():
    parser = argparse.ArgumentParser(description="Chuẩn bị dữ liệu JSON cho API backend")
    parser.add_argument("input_file", help="File JSON đầu vào")
    parser.add_argument("--output", "-o", help="File JSON đầu ra (tùy chọn)")
    args = parser.parse_args()
    
    fixed_file = fix_json_for_api(args.input_file, args.output)
    
    if fixed_file:
        print(f"""
Để gửi dữ liệu đã sửa đến backend, bạn có thể chạy lệnh:
python main.py --skip-search --input-file={fixed_file}
""")


if __name__ == "__main__":
    main() 