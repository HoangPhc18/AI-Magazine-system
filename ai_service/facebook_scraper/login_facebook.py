#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import os
import sys
import time
import json
import logging
import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import traceback

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("facebook_login")

# Configuration - Replace with your Facebook credentials
FB_USERNAME = "thaihoangphuc.21tin01tt.dtdm@gmail.com"
FB_PASSWORD = "Phuckg123"

def enable_chrome_driver_manager_logging():
    """Enable logging for ChromeDriverManager for better debugging"""
    import logging
    logging.basicConfig(level=logging.INFO)
    selenium_logger = logging.getLogger('selenium')
    selenium_logger.setLevel(logging.INFO)
    webdriver_manager_logger = logging.getLogger('webdriver_manager')
    webdriver_manager_logger.setLevel(logging.INFO)

def setup_chromedriver_for_selenium():
    """Force chromedriver-binary-auto-install to install the right version"""
    try:
        from webdriver_manager.chrome import ChromeDriverManager
        from webdriver_manager.core.utils import ChromeType

        # Be verbose about what's happening
        enable_chrome_driver_manager_logging()
    
        # This will install the ChromeDriver that matches the installed Chrome browser
        # and return the path to the executable
        driver_path = ChromeDriverManager().install()
        logger.info(f"Installed ChromeDriver at: {driver_path}")
        
        return driver_path
    except Exception as e:
        logger.error(f"Error installing ChromeDriver: {str(e)}")
        return None

def save_cookies(driver, filename="facebook_cookies.json"):
    """Save browser cookies to a file"""
    try:
        # Create cookies directory if it doesn't exist
        os.makedirs("cookies", exist_ok=True)
        
        # Save cookies to a file
        cookies = driver.get_cookies()
        if not cookies:
            logger.warning("No cookies to save")
            return False
            
        filepath = os.path.join("cookies", filename)
        with open(filepath, "w") as file:
            json.dump(cookies, file)
            
        logger.info(f"Successfully saved cookies to {filepath}")
        return True
    except Exception as e:
        logger.error(f"Error saving cookies: {str(e)}")
        return False

def login_facebook():
    """Login to Facebook and save the cookies"""
    driver = None
    
    try:
        # Setup Chrome options for Webdriver
        options = webdriver.ChromeOptions()
        
        # Common options
        options.add_argument("--disable-notifications")
        options.add_argument("--disable-infobars")
        options.add_argument("--disable-extensions")
        options.add_argument("--disable-gpu")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        
        # Experimental options
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option("useAutomationExtension", False)
        
        # Use remote debugging port
        options.add_argument("--remote-debugging-port=9222")
        
        # Bypass OS level alert
        options.add_argument("--use-mock-keychain")
        
        # Setup window size
        options.add_argument("--window-size=1920,1080")
        
        # Log flags that are being used
        logger.info(f"Chrome options: {options.arguments}")
        
        # Get ChromeDriver path
        chromedriver_path = setup_chromedriver_for_selenium()
        
        if not chromedriver_path:
            logger.error("Failed to set up ChromeDriver. Cannot proceed with login.")
            return False
            
        # Setup service
        service = Service(executable_path=chromedriver_path)
        
        # Initialize the driver
        logger.info("Initializing Chrome driver...")
        driver = webdriver.Chrome(service=service, options=options)
        
        # Set an implicit wait
        driver.implicitly_wait(10)
        
        # Navigate to Facebook login page
        logger.info("Navigating to Facebook login page...")
        driver.get("https://www.facebook.com/login")
        
        # Take screenshot for debugging
        os.makedirs("logs", exist_ok=True)
        driver.save_screenshot("logs/facebook_login_page.png")
        
        # Check if we are already on the login page
        if "Facebook" not in driver.title:
            logger.warning(f"Unexpected page title: {driver.title}")
            driver.save_screenshot("logs/unexpected_page.png")
        
        # Wait for and fill in email field
        logger.info("Filling in email field...")
        try:
            email_field = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.ID, "email"))
            )
            email_field.clear()
            email_field.send_keys(FB_USERNAME)
            logger.info("Email field filled successfully")
        except Exception as e:
            logger.error(f"Error filling email field: {str(e)}")
            driver.save_screenshot("logs/email_field_error.png")
            return False
        
        # Fill in password field
        logger.info("Filling in password field...")
        try:
            password_field = driver.find_element(By.ID, "pass")
            password_field.clear()
            password_field.send_keys(FB_PASSWORD)
            logger.info("Password field filled successfully")
        except Exception as e:
            logger.error(f"Error filling password field: {str(e)}")
            driver.save_screenshot("logs/password_field_error.png")
            return False
        
        # Take screenshot before clicking login
        driver.save_screenshot("logs/before_login.png")
        
        # Click login button
        logger.info("Clicking login button...")
        try:
            login_button = driver.find_element(By.NAME, "login")
            login_button.click()
            logger.info("Login button clicked successfully")
        except Exception as e:
            logger.error(f"Error clicking login button: {str(e)}")
            driver.save_screenshot("logs/login_button_error.png")
            return False
        
        # Wait for login to complete
        logger.info("Waiting for login to complete...")
        time.sleep(5)
        
        # Take screenshot after login
        driver.save_screenshot("logs/after_login.png")
        
        # Check if login successful
        if "login" not in driver.current_url and "checkpoint" not in driver.current_url:
            logger.info("Login successful!")
            
            # Save cookies
            save_result = save_cookies(driver)
            
            if save_result:
                logger.info("Cookies saved successfully")
                return True
            else:
                logger.error("Failed to save cookies")
                return False
        else:
            logger.error("Login failed. Still on login or checkpoint page.")
            driver.save_screenshot("logs/login_failed.png")
            return False
            
    except Exception as e:
        logger.error(f"Error during login process: {str(e)}")
        logger.error(traceback.format_exc())
        if driver:
            driver.save_screenshot("logs/login_exception.png")
        return False
        
    finally:
        # Clean up
        if driver:
            logger.info("Closing browser...")
            driver.quit()

if __name__ == "__main__":
    if login_facebook():
        print("Login successful and cookies saved!")
        sys.exit(0)
    else:
        print("Login failed. Check logs for details.")
        sys.exit(1) 