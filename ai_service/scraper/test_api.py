#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import json
import requests
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"scraper_test_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

def check_backend_api():
    # Get backend URL from environment variables
    backend_url = os.getenv('BACKEND_URL', 'http://host.docker.internal')
    backend_port = os.getenv('BACKEND_PORT', '80')
    
    # Build API URL
    if backend_port == '80':
        api_url = f"{backend_url}/api/categories"
    else:
        api_url = f"{backend_url}:{backend_port}/api/categories"
    
    try:
        logger.info(f"Testing API connection to: {api_url}")
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
            'Accept': 'application/json',
        }
        
        response = requests.get(api_url, headers=headers, timeout=10)
        
        if response.status_code == 200:
            categories = response.json()
            logger.info(f"API connection successful! Found {len(categories)} categories")
            
            # Print first 3 categories for verification
            for i, category in enumerate(categories[:3]):
                logger.info(f"Category {i+1}: ID={category.get('id')}, Name={category.get('name')}")
                
            return True
        else:
            logger.error(f"API HTTP error {response.status_code}: {response.text}")
            return False
    except Exception as e:
        logger.error(f"API connection error: {str(e)}")
        return False

if __name__ == "__main__":
    print("----- TESTING BACKEND API CONNECTION -----")
    success = check_backend_api()
    print(f"Connection test result: {'✓ Success' if success else '✗ Failed'}")
    print("-----------------------------------------") 