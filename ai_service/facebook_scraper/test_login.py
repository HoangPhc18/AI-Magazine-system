#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import logging
import json
import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

# Import config and login function from scraper_facebook.py
from config import get_config
from scraper_facebook import login_facebook, setup_driver, save_cookies, load_cookies

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("test_login")

def test_facebook_login():
    """Test Facebook login functionality"""
    
    # Get configuration
    config = get_config()
    
    # Display Facebook credentials (partially masked)
    username = config.get('FACEBOOK_USERNAME', '')
    password = config.get('FACEBOOK_PASSWORD', '')
    
    if username and password:
        masked_username = username[:3] + "***" + username[-4:] if len(username) > 7 else "***"
        masked_password = "****" + password[-2:] if len(password) > 2 else "****"
        logger.info(f"Using Facebook credentials: Username={masked_username}, Password={masked_password}")
    else:
        logger.error("Facebook credentials not found in configuration")
        return
    
    # Test with both headless and non-headless mode
    for headless in [True, False]:
        try:
            logger.info(f"Testing login with headless={headless}")
            
            # Setup driver
            driver = setup_driver(headless=headless, use_profile=False)
            
            if not driver:
                logger.error("Failed to setup Chrome driver")
                continue
            
            try:
                # Test cookie loading
                logger.info("Testing cookie loading...")
                cookie_result = load_cookies(driver)
                logger.info(f"Cookie loading result: {cookie_result}")
                
                # Navigate to Facebook
                driver.get("https://www.facebook.com")
                time.sleep(5)
                
                # Take screenshot after cookie loading
                screenshot_path = f"logs/cookie_login_{'headless' if headless else 'visible'}.png"
                driver.save_screenshot(screenshot_path)
                logger.info(f"Saved cookie login screenshot to {screenshot_path}")
                
                # Check if we're logged in
                if "facebook.com/login" in driver.current_url:
                    logger.info("Not logged in with cookies, trying credential login")
                    
                    # Test credential login
                    login_result = login_facebook(driver, username, password)
                    logger.info(f"Credential login result: {login_result}")
                    
                    # Take screenshot after credential login
                    screenshot_path = f"logs/credential_login_{'headless' if headless else 'visible'}.png"
                    driver.save_screenshot(screenshot_path)
                    logger.info(f"Saved credential login screenshot to {screenshot_path}")
                else:
                    logger.info("Successfully logged in with cookies")
                
                # Save cookies if login was successful
                if "facebook.com/login" not in driver.current_url:
                    logger.info("Login successful, saving cookies...")
                    save_result = save_cookies(driver)
                    logger.info(f"Cookie saving result: {save_result}")
            finally:
                # Clean up
                if driver:
                    driver.quit()
                    
        except Exception as e:
            logger.error(f"Error during login test: {str(e)}")

if __name__ == "__main__":
    test_facebook_login() 