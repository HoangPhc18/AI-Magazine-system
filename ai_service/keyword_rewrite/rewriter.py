#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module for rewriting article content using Google Gemini API.
"""

import os
import sys
import logging
import time
import json
import requests
from datetime import datetime
import google.generativeai as genai

# Set environment variables directly
os.environ["GEMINI_MODEL"] = "gemini-1.5-flash-latest"
os.environ["GEMINI_API_KEY"] = "AIzaSyDNYibANNjOZOG5dDPb6YlZ72bXkr7mvL4"

# Get Gemini settings from environment
GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-1.5-flash-latest")
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "AIzaSyDNYibANNjOZOG5dDPb6YlZ72bXkr7mvL4")

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"content_rewriter_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

# Initialize Gemini API
genai.configure(api_key=GEMINI_API_KEY)

def rewrite_content(title, content, model=None, temperature=0.7, max_tokens=2048):
    """
    Rewrite article content using Google Gemini API.
    
    Args:
        title (str): Article title
        content (str): Original article content
        model (str): Gemini model to use (if None, uses GEMINI_MODEL from env)
        temperature (float): Temperature parameter for generation
        max_tokens (int): Maximum tokens to generate
        
    Returns:
        str: Rewritten content
    """
    # Use model from parameter or from environment
    model_to_use = model if model else GEMINI_MODEL
    
    logger.info(f"Rewriting content with title: {title}")
    logger.info(f"Using model: {model_to_use}, temperature: {temperature}")
    
    # Prepare prompt
    prompt = f"""Hãy viết lại bài viết sau đây bằng chính từ ngữ của bạn, 
giữ nguyên ý tưởng chính nhưng sử dụng cách diễn đạt khác:

Tiêu đề: {title}

Nội dung: {content}

Viết lại bài theo cách mới mẻ, không copy các câu từ nguyên gốc, 
nhưng vẫn giữ nguyên ý nghĩa tổng thể của bài viết. Hãy đảm bảo bài viết mới 
mạch lạc, rõ ràng và dễ hiểu."""
    
    try:
        # Configure the generation model
        start_time = time.time()
        
        logger.info(f"Initializing Gemini model: {model_to_use}")
        generation_model = genai.GenerativeModel(model_to_use)
        
        # Generate content
        logger.info(f"Generating content with temperature: {temperature}")
        response = generation_model.generate_content(
            prompt,
            generation_config={
                "temperature": temperature,
                "max_output_tokens": max_tokens,
            }
        )
        
        duration = time.time() - start_time
        
        if response and hasattr(response, 'text'):
            rewritten_content = response.text
            
            if not rewritten_content:
                logger.error("Empty response from Gemini API")
                return "Error: Empty response from Gemini API"
                
            logger.info(f"Successfully rewrote content using {model_to_use} in {duration:.2f} seconds (Length: {len(rewritten_content)})")
            
            return rewritten_content
        else:
            error_message = "Invalid response from Gemini API"
            if hasattr(response, 'prompt_feedback'):
                error_message += f": {response.prompt_feedback}"
            logger.error(error_message)
            return f"Error: {error_message}"
            
    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        return f"Error: {str(e)}"

if __name__ == '__main__':
    # Test the function
    if len(sys.argv) > 2:
        title = sys.argv[1]
        content_file = sys.argv[2]
        
        try:
            with open(content_file, 'r', encoding='utf-8') as f:
                content = f.read()
                
            result = rewrite_content(title, content)
            print(f"Rewritten content using {GEMINI_MODEL} ({len(result)} chars):")
            print("----------")
            print(result)
            print("----------")
        except Exception as e:
            print(f"Error: {str(e)}")
    else:
        print("Usage: python rewriter.py <title> <content_file>") 