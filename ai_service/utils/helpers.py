from datetime import datetime, timedelta
from newspaper import Article
from tqdm import tqdm

def get_date_range():
    """Get date range for news collection (last 24 hours)"""
    end_date = datetime.now()
    start_date = end_date - timedelta(hours=24)
    return start_date, end_date

def extract_article_content(url):
    """Extract article content from URL using newspaper3k"""
    try:
        article = Article(url)
        article.download()
        article.parse()
        return article.text
    except Exception as e:
        print(f"Error extracting content from {url}: {e}")
        return None

def clean_text(text):
    """Clean and normalize text"""
    if not text:
        return ""
    # Remove extra whitespace
    text = ' '.join(text.split())
    # Remove special characters
    text = text.replace('\n', ' ').replace('\r', '')
    return text.strip()

def format_date(date):
    """Format date for API requests"""
    return date.strftime('%Y-%m-%d')

def process_articles(articles, callback):
    """Process articles with progress bar"""
    results = []
    for article in tqdm(articles, desc="Processing articles"):
        result = callback(article)
        if result:
            results.append(result)
    return results 