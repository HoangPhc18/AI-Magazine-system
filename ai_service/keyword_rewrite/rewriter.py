#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Module for rewriting article content using Ollama models.
"""

import os
import sys
import logging
import time
import json
import requests
from datetime import datetime
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Get Ollama model from environment or use default
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "llama3:latest")
OLLAMA_HOST = os.getenv("OLLAMA_HOST", "http://localhost:11434")

# Danh sách các mô hình dự phòng
FALLBACK_MODELS = ["llama3:latest", "llama2:latest", "mistral:latest"]

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

def rewrite_content(title, content, model=None, temperature=0.7, max_tokens=2048):
    """
    Rewrite article content using Ollama.
    
    Args:
        title (str): Article title
        content (str): Original article content
        model (str): Ollama model to use (if None, uses OLLAMA_MODEL from env)
        temperature (float): Temperature parameter for generation
        max_tokens (int): Maximum tokens to generate
        
    Returns:
        str: Rewritten content
    """
    # Use model from parameter or from environment
    model_to_use = model if model else OLLAMA_MODEL
    
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
        # Check if Ollama is available
        ollama_url = f"{OLLAMA_HOST}/api/tags"
        logger.info(f"Checking Ollama availability at: {ollama_url}")
        
        try:
            response = requests.get(ollama_url)
            
            if response.status_code != 200:
                logger.error(f"Ollama service is not available at {OLLAMA_HOST}")
                return f"Error: Ollama service unavailable at {OLLAMA_HOST} (Status code: {response.status_code})"
                
        except requests.exceptions.RequestException as e:
            logger.error(f"Error connecting to Ollama at {OLLAMA_HOST}: {str(e)}")
            return f"Error: Unable to connect to Ollama service at {OLLAMA_HOST} - {str(e)}"
        
        # Call Ollama API
        start_time = time.time()
        generate_url = f"{OLLAMA_HOST}/api/generate"
        
        logger.info(f"Sending generation request to: {generate_url}")
        response = requests.post(
            generate_url,
            json={
                "model": model_to_use,
                "prompt": prompt,
                "temperature": temperature,
                "max_tokens": max_tokens,
                "stream": False
            }
        )
        
        duration = time.time() - start_time
        
        if response.status_code == 200:
            result = response.json()
            rewritten_content = result.get("response", "")
            
            if not rewritten_content:
                logger.error("Empty response from Ollama")
                return "Error: Empty response from Ollama"
                
            logger.info(f"Successfully rewrote content using {model_to_use} in {duration:.2f} seconds (Length: {len(rewritten_content)})")
            
            # Clean up the rewritten content
            # Remove any markdown formatting or special tokens
            rewritten_content = rewritten_content.replace("```", "").replace("###", "")
            
            return rewritten_content
        else:
            error_message = f"Ollama API error: {response.status_code} - {response.text}"
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
            print(f"Rewritten content using {OLLAMA_MODEL} ({len(result)} chars):")
            print("----------")
            print(result)
            print("----------")
        except Exception as e:
            print(f"Error: {str(e)}")
    else:
        print("Usage: python rewriter.py <title> <content_file>") 