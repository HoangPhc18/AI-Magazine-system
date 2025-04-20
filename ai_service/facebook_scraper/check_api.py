import requests
import json
import sys

def check_api_status():
    """Kiểm tra xem API có hoạt động hay không"""
    try:
        response = requests.get("http://localhost:5004/health")
        if response.status_code == 200:
            data = response.json()
            print("✅ API đang hoạt động!")
            print(f"⏺️ Trạng thái: {data['status']}")
            print(f"⏺️ Jobs đang chạy: {data['active_jobs']}")
            print(f"⏺️ Jobs đã hoàn thành: {data['completed_jobs']}")
            return True
        else:
            print(f"❌ API trả về lỗi: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Không thể kết nối đến API: {str(e)}")
        return False

def list_all_jobs():
    """Liệt kê tất cả các jobs"""
    try:
        response = requests.get("http://localhost:5004/api/jobs")
        if response.status_code == 200:
            data = response.json()
            
            print("\n📋 DANH SÁCH JOBS:")
            
            # Các jobs đang chạy
            active_jobs = data.get('active', {})
            if active_jobs:
                print("\n🔵 Jobs đang chạy:")
                for job_id, job_info in active_jobs.items():
                    print(f"  • Job ID: {job_id}")
                    print(f"    URL: {job_info.get('url')}")
                    print(f"    Bắt đầu: {job_info.get('started_at')}")
                    print(f"    Trạng thái: {job_info.get('status')}")
                    print()
            else:
                print("\n🔵 Không có job nào đang chạy")
            
            # Các jobs đã hoàn thành
            completed_jobs = data.get('completed', {})
            if completed_jobs:
                print("\n🟢 Jobs đã hoàn thành:")
                for job_id, job_info in completed_jobs.items():
                    status = job_info.get('status')
                    status_emoji = "✅" if status == "completed" else "❌"
                    print(f"  • Job ID: {job_id} {status_emoji}")
                    print(f"    URL: {job_info.get('url')}")
                    print(f"    Trạng thái: {status}")
                    if 'count' in job_info:
                        print(f"    Số bài viết: {job_info.get('count')}")
                    if 'error' in job_info:
                        print(f"    Lỗi: {job_info.get('error')}")
                    print(f"    Kết thúc: {job_info.get('completed_at')}")
                    print()
            else:
                print("\n🟢 Không có job nào đã hoàn thành")
            
            return True
        else:
            print(f"❌ API trả về lỗi: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Không thể kết nối đến API: {str(e)}")
        return False

if __name__ == "__main__":
    print("Kiểm tra trạng thái Facebook Scraper API...")
    if check_api_status():
        list_all_jobs()
    else:
        print("\n❗ Vui lòng đảm bảo rằng API đang chạy bằng cách thực thi lệnh sau:")
        print("   python main.py")
        sys.exit(1) 