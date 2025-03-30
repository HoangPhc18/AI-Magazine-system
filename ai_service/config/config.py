import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# API Keys
SERPAPI_KEY = os.getenv('SERPAPI_KEY')
WORLDNEWS_API_KEY = os.getenv('WORLDNEWS_API_KEY')
CURRENTS_API_KEY = os.getenv('CURRENTS_API_KEY')

# Database Configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'database': os.getenv('DB_NAME', 'AiMagazineDB')
}

# AI Configuration
OLLAMA_HOST = os.getenv('OLLAMA_HOST', 'http://localhost:11434')
MODEL_NAME = os.getenv('MODEL_NAME', 'gemma:2b')

# News Collection Settings
NEWS_LANGUAGE = 'vi'  # Vietnamese
NEWS_TIME_RANGE = '24h'  # Last 24 hours
MAX_ARTICLES_PER_CATEGORY = 10

# Content Rewriting Settings
MAX_CONTENT_LENGTH = 1000
MIN_CONTENT_LENGTH = 100 