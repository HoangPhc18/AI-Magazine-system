#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import json
import requests
import argparse
import logging
import sys

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("facebook_cookies")

def load_cookies(cookie_file="cookies/facebook_cookies.json"):
    """Load cookies from file"""
    try:
        if not os.path.exists(cookie_file):
            logger.error(f"Cookie file not found: {cookie_file}")
            return None
        
        with open(cookie_file, "r") as f:
            cookies = json.load(f)
        
        if not cookies:
            logger.error("Cookie file is empty")
            return None
        
        # Convert cookies to requests format
        cookies_dict = {}
        for cookie in cookies:
            if "name" in cookie and "value" in cookie:
                cookies_dict[cookie["name"]] = cookie["value"]
        
        logger.info(f"Loaded {len(cookies_dict)} cookies from file")
        return cookies_dict
    except Exception as e:
        logger.error(f"Error loading cookies: {str(e)}")
        return None

def test_facebook_login(cookies=None):
    """Test if the cookies work for Facebook login"""
    try:
        if not cookies:
            cookies = load_cookies()
        
        if not cookies:
            logger.error("No cookies available")
            return False
        
        # Try accessing Facebook with cookies
        url = "https://www.facebook.com/me"
        
        # Set custom headers to avoid detection
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
            "Accept": "text/html,application/xhtml+xml,application/xml",
            "Accept-Language": "en-US,en;q=0.9",
            "Accept-Encoding": "gzip, deflate, br",
            "Connection": "keep-alive",
            "Cache-Control": "max-age=0",
            "Sec-Ch-Ua": "\"Not.A/Brand\";v=\"8\", \"Chromium\";v=\"114\"",
            "Sec-Ch-Ua-Mobile": "?0",
            "Sec-Ch-Ua-Platform": "\"Windows\"",
            "Sec-Fetch-Dest": "document",
            "Sec-Fetch-Mode": "navigate",
            "Sec-Fetch-Site": "none",
            "Sec-Fetch-User": "?1",
            "Upgrade-Insecure-Requests": "1"
        }
        
        logger.info(f"Sending request to {url}")
        response = requests.get(url, cookies=cookies, headers=headers, allow_redirects=True)
        
        # Save the response for debugging
        os.makedirs("logs", exist_ok=True)
        with open("logs/facebook_response.html", "w", encoding="utf-8") as f:
            f.write(response.text)
        
        # Check if login was successful
        if response.status_code == 200 and "login" not in response.url:
            logger.info("Login successful! Cookies are valid.")
            return True
        else:
            logger.error(f"Login failed. Status code: {response.status_code}, URL: {response.url}")
            return False
    except Exception as e:
        logger.error(f"Error testing Facebook login: {str(e)}")
        return False

def main():
    """Main function"""
    parser = argparse.ArgumentParser(description="Test Facebook cookies")
    parser.add_argument("--cookie-file", type=str, default="cookies/facebook_cookies.json", 
                      help="Path to cookie file")
    
    args = parser.parse_args()
    
    # Test login with cookies
    success = test_facebook_login(load_cookies(args.cookie_file))
    
    if success:
        print("Facebook cookies are valid!")
        return 0
    else:
        print("Facebook cookies are invalid or expired. Need to login again.")
        return 1

if __name__ == "__main__":
    sys.exit(main()) 