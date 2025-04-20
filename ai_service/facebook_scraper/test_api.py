import requests
import json
import time

def test_api():
    url = "http://localhost:5004/api/scrape"
    data = {
        "url": "https://www.facebook.com/groups/example",
        "use_profile": True,
        "chrome_profile": "Default",
        "limit": 2,
        "headless": False  # Thêm tham số headless=False để dễ debug
    }
    
    try:
        print("Gửi request đến API...")
        response = requests.post(url, json=data)
        
        if response.status_code == 200:
            result = response.json()
            print(f"Success: {json.dumps(result, indent=2)}")
            
            if 'job_id' in result:
                job_id = result['job_id']
                print(f"Lấy trạng thái của job {job_id}...")
                time.sleep(2)  # Đợi một chút để job bắt đầu
                
                status_url = f"http://localhost:5004/api/jobs/{job_id}"
                status_response = requests.get(status_url)
                
                if status_response.status_code == 200:
                    status_result = status_response.json()
                    print(f"Trạng thái job: {json.dumps(status_result, indent=2)}")
                else:
                    print(f"Lỗi khi lấy trạng thái job: {status_response.status_code}")
                    print(status_response.text)
        else:
            print(f"Lỗi: {response.status_code}")
            print(response.text)
    except Exception as e:
        print(f"Lỗi: {str(e)}")

if __name__ == "__main__":
    test_api() 