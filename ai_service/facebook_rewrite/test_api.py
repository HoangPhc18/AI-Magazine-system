import requests
import json
import sys
import argparse

def test_single_rewrite(text=None, post_id=None):
    """
    Test the rewrite API for a single Facebook post
    
    Args:
        text (str): Text to rewrite. If None, uses a default example.
        post_id (int): Optional post ID to save to database
    """
    if text is None:
        text = "I just launched a new product. It's pretty good I think. Check it out on our website."
    
    payload = {"text": text}
    if post_id:
        payload["post_id"] = post_id
    
    try:
        # Make request to the API
        response = requests.post(
            "http://localhost:5001/api/rewrite",
            headers={"Content-Type": "application/json"},
            json=payload,
            timeout=300
        )
        
        # Print the response
        if response.status_code == 200:
            result = response.json()
            print("\n=== API Response ===")
            print(f"Original: {text}")
            
            if "rewritten" in result:
                rewritten = result["rewritten"]
                print(f"\nRewritten Title: {rewritten.get('title')}")
                print(f"\nRewritten Content: {rewritten.get('content')}")
            
            if "saved_to_db" in result:
                print(f"\nSaved to database: {result.get('saved_to_db')}")
            
            print("===================\n")
            return True
        else:
            print(f"Error: API returned status code {response.status_code}")
            print(response.text)
            return False
    
    except requests.exceptions.RequestException as e:
        print(f"Request error: {e}")
        return False

def test_batch_processing(limit=3):
    """
    Test batch processing of Facebook posts
    
    Args:
        limit (int): Maximum number of posts to process
    """
    try:
        # Make request to the API
        response = requests.post(
            "http://localhost:5001/api/process-batch",
            headers={"Content-Type": "application/json"},
            json={"limit": limit},
            timeout=600
        )
        
        # Print the response
        if response.status_code == 200:
            result = response.json()
            print("\n=== Batch Processing Results ===")
            print(f"Processed: {result.get('processed_count')} posts")
            
            for idx, item in enumerate(result.get('results', [])):
                print(f"\n--- Post {idx + 1} ---")
                print(f"Post ID: {item.get('post_id')}")
                if "error" in item:
                    print(f"Error: {item.get('error')}")
                else:
                    print(f"Title: {item.get('title')}")
                    print(f"Saved: {item.get('saved')}")
            
            print("===================\n")
            return True
        else:
            print(f"Error: API returned status code {response.status_code}")
            print(response.text)
            return False
    
    except requests.exceptions.RequestException as e:
        print(f"Request error: {e}")
        return False

def main():
    parser = argparse.ArgumentParser(description='Test Facebook post rewriting API')
    parser.add_argument('--text', type=str, help='Text to rewrite')
    parser.add_argument('--post-id', type=int, help='Post ID to associate with rewrite')
    parser.add_argument('--batch', action='store_true', help='Run batch processing')
    parser.add_argument('--limit', type=int, default=3, help='Limit for batch processing')
    
    args = parser.parse_args()
    
    if args.batch:
        test_batch_processing(args.limit)
    else:
        test_single_rewrite(args.text, args.post_id)

if __name__ == "__main__":
    main() 