from flask import Flask, request, jsonify
import os
import requests
import json
import mysql.connector
from dotenv import load_dotenv
import logging
import datetime
import time
import string
import random
import re
import google.generativeai as genai

# Load environment variables
load_dotenv()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME')
}

# Google Gemini configuration
GEMINI_API_KEY = os.getenv('GEMINI_API_KEY', 'AIzaSyDNYibANNjOZOG5dDPb6YlZ72bXkr7mvL4')
GEMINI_MODEL = os.getenv('GEMINI_MODEL', 'gemini-1.5-flash-latest')

# Ollama configuration (keeping for backward compatibility)
OLLAMA_HOST = os.getenv('OLLAMA_HOST', 'http://localhost:11434')
OLLAMA_MODEL = os.getenv('OLLAMA_MODEL', 'gemma2:latest')

# Timeout configuration
API_TIMEOUT = int(os.getenv('API_TIMEOUT', 3600))
MAX_RETRIES = int(os.getenv('MAX_RETRIES', 3))
INITIAL_BACKOFF = int(os.getenv('INITIAL_BACKOFF', 5))
MAX_TEXT_SIZE = int(os.getenv('MAX_TEXT_SIZE', 8000))

# Initialize Gemini API
genai.configure(api_key=GEMINI_API_KEY)

def get_db_connection():
    """Create and return a database connection"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except mysql.connector.Error as err:
        logger.error(f"Database connection error: {err}")
        return None

def clean_json_string(json_str):
    """
    Clean JSON string by removing control characters and fixing common issues
    
    Args:
        json_str (str): Potentially invalid JSON string
        
    Returns:
        str: Cleaned JSON string
    """
    # Remove control characters
    json_str = re.sub(r'[\x00-\x1F\x7F-\x9F]', '', json_str)
    
    # Fix common JSON formatting issues
    json_str = json_str.replace('\n', ' ').replace('\r', ' ')
    json_str = json_str.replace('\\n', '\\\\n').replace('\\r', '\\\\r')
    
    # Remove trailing commas before closing brackets
    json_str = re.sub(r',\s*}', '}', json_str)
    json_str = re.sub(r',\s*]', ']', json_str)
    
    return json_str

def extract_json_from_text(text):
    """
    Extract valid JSON from text which might contain other content
    
    Args:
        text (str): Text that may contain JSON
        
    Returns:
        dict: Parsed JSON or fallback dictionary
    """
    try:
        # Try if the text is already valid JSON
        return json.loads(clean_json_string(text))
    except json.JSONDecodeError:
        pass
    
    # Look for JSON between code blocks
    json_patterns = [
        r'```json(.*?)```',  # JSON in code blocks with json tag
        r'```(.*?)```',       # JSON in any code blocks
        r'{.*}',              # Just find any JSON-like structure
    ]
    
    for pattern in json_patterns:
        matches = re.findall(pattern, text, re.DOTALL)
        for match in matches:
            try:
                return json.loads(clean_json_string(match))
            except json.JSONDecodeError:
                continue
    
    # If no valid JSON found, try to build a JSON manually
    # Look for title pattern
    title_match = re.search(r'(?:title|Title)["\'\s:]+([^"\'\n]+)', text)
    title = title_match.group(1).strip() if title_match else "Rewritten Article"
    
    # Rest of the text as content
    content = text
    
    return {"title": title, "content": content}

def clean_content_for_db(content):
    """
    Clean content for database storage by removing special characters and JSON artifacts
    
    Args:
        content (str): The content to clean
        
    Returns:
        str: Cleaned content ready for database storage
    """
    if not content:
        return ""
        
    # Remove JSON-specific escape sequences
    content = content.replace('\\n', '\n').replace('\\r', '\r').replace('\\"', '"')
    
    # Remove any remaining backslashes before other characters
    content = re.sub(r'\\(.)', r'\1', content)
    
    # Remove control characters
    content = re.sub(r'[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]', '', content)
    
    # Clean up excessive newlines (more than 2 consecutive newlines)
    content = re.sub(r'\n{3,}', '\n\n', content)
    
    # Fix spacing after period if needed
    content = re.sub(r'\.(?=[A-Z])', '. ', content)
    
    # Ensure there are spaces after commas
    content = re.sub(r',(?=[^\s])', ', ', content)
    
    return content.strip()

def save_rewritten_article(post_id, post_data, rewritten_data):
    """Save the rewritten article to the database"""
    conn = get_db_connection()
    if not conn:
        logger.error("Could not connect to database")
        return False
    
    try:
        cursor = conn.cursor()
        now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        # Get title and content from rewritten_data
        title = rewritten_data.get("title", "")
        content = rewritten_data.get("content", "")
        
        # Clean content before storing in database
        clean_title = clean_content_for_db(title)
        clean_content = clean_content_for_db(content)
        
        # Log the cleaned content
        logger.info(f"Cleaned title: {clean_title[:50]}...")
        logger.info(f"Cleaned content sample: {clean_content[:100]}...")
        
        # Generate slug from title
        slug = generate_slug(clean_title)
        
        # Insert into rewritten_articles table
        insert_query = """
        INSERT INTO rewritten_articles (
            title, slug, content, meta_description, user_id, category_id, 
            original_article_id, ai_generated, status, 
            created_at, updated_at
        )
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        
        # Create a meta description from the first 100-200 chars of the content
        meta_desc = clean_content[:200] + "..." if len(clean_content) > 200 else clean_content
        
        # Default values for user_id and category_id, adjust as needed
        user_id = 1  # System user
        category_id = 1  # Default category
        
        cursor.execute(insert_query, (
            clean_title,
            slug,
            clean_content,
            meta_desc,
            user_id,
            category_id,
            post_id,
            1,  # ai_generated = true
            'pending',  # status
            now,
            now
        ))
        
        # Update facebook_posts to mark as processed
        update_query = """
        UPDATE facebook_posts
        SET processed = 1, updated_at = %s
        WHERE id = %s
        """
        cursor.execute(update_query, (now, post_id))
        
        conn.commit()
        logger.info(f"Successfully saved rewritten article for post ID {post_id}")
        return True
    except mysql.connector.Error as err:
        logger.error(f"Database error when saving article: {err}")
        return False
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

def rewrite_text_with_gemini_api(original_text):
    """
    Rewrite the given text using Google Gemini API
    
    Args:
        original_text (str): The original Facebook post to rewrite
        
    Returns:
        dict: The rewritten content with title
    """
    # Log debug information
    logger.info(f"Using Gemini model: {GEMINI_MODEL}")
    
    # Limit text size to avoid excessively long prompts
    limited_text = limit_text_size(original_text)
    
    def make_request():
        try:
            # Configure the model
            model = genai.GenerativeModel(GEMINI_MODEL)
            
            # Create the prompt
            prompt = f"""Hãy viết lại bài đăng Facebook dưới đây thành một bài báo có nội dung chính xác và đầy đủ.
            
Bài đăng Facebook gốc:
{limited_text}

Yêu cầu:
1. Tạo một tiêu đề hấp dẫn cho bài báo bằng tiếng Việt (không quá 100 ký tự)
2. Mở rộng nội dung chi tiết hơn và hấp dẫn hơn
3. Làm cho nó chuyên nghiệp trong khi vẫn giữ ý nghĩa ban đầu
4. Định dạng nội dung thành các đoạn văn rõ ràng, mỗi đoạn cách nhau đúng một dòng trống
5. KHÔNG bao gồm các dấu ngoặc, JSON hoặc định dạng đặc biệt trong nội dung viết lại
6. Tất cả nội dung phải được viết bằng tiếng Việt
7. Cung cấp văn bản thuần túy, KHÔNG bao gồm bất kỳ định dạng JSON nào trong phản hồi

Định dạng phản hồi:
Tiêu đề: [tiêu đề bài viết]

[nội dung bài viết với các đoạn văn được định dạng rõ ràng]"""
            
            # Generate response with timeout
            response = model.generate_content(prompt)
            response_text = response.text
            
            # Log the raw response for debugging
            logger.info(f"Raw model response: {response_text[:100]}...")
            
            # Extract title and content from response
            title_match = re.search(r'Tiêu đề:?\s*(.+)', response_text)
            title = title_match.group(1).strip() if title_match else "Bài viết đã viết lại"
            
            # Remove the title line and any lines before it to get the content
            content_lines = response_text.split('\n')
            content_start = 0
            for i, line in enumerate(content_lines):
                if "Tiêu đề:" in line or re.match(r'^[Tt]iêu đề', line):
                    content_start = i + 1
                    break
            
            # Join remaining lines as content, skipping empty lines at the start
            while content_start < len(content_lines) and not content_lines[content_start].strip():
                content_start += 1
                
            content = '\n'.join(content_lines[content_start:])
            
            # Clean up content
            content = clean_content_for_db(content)
            title = clean_content_for_db(title)
            
            return {
                "title": title,
                "content": content
            }
            
        except Exception as e:
            logger.error(f"Google Gemini API error: {str(e)}")
            raise requests.exceptions.RequestException(f"Google Gemini API error: {str(e)}")
    
    try:
        # Use retry for request
        result = retry_with_backoff(make_request, max_retries=MAX_RETRIES, initial_backoff=INITIAL_BACKOFF)
        return result
        
    except requests.exceptions.RequestException as e:
        logger.error(f"Request error after retries: {e}")
        return {
            "title": "Lỗi kết nối",
            "content": f"Không thể kết nối đến dịch vụ AI sau nhiều lần thử: {str(e)}"
        }

def rewrite_text_with_gemma(original_text):
    """
    Rewrite the given text using AI model
    
    Args:
        original_text (str): The original Facebook post to rewrite
        
    Returns:
        dict: The rewritten content with title
    """
    # Default to using Google Gemini API
    return rewrite_text_with_gemini_api(original_text)
    
    # Legacy Ollama implementation is kept below but not used
    """
    # In ra thông tin debug
    logger.info(f"OLLAMA_HOST: {OLLAMA_HOST}, OLLAMA_MODEL: {OLLAMA_MODEL}, API_TIMEOUT: {API_TIMEOUT}")
    
    # Limit text size to avoid excessively long prompts
    limited_text = limit_text_size(original_text)
    
    def make_request():
        # Request to Ollama API using environment variables
        logger.info(f"Making request to Ollama API with timeout set to {API_TIMEOUT} seconds")
        response = requests.post(
            f"{OLLAMA_HOST}/api/generate",
            json={
                "model": OLLAMA_MODEL,
                "prompt": f'''Hãy viết lại bài đăng Facebook dưới đây thành một bài báo có nội dung chính xác và đầy đủ.
                
Bài đăng Facebook gốc:
{limited_text}

Yêu cầu:
1. Tạo một tiêu đề hấp dẫn cho bài báo bằng tiếng Việt
2. Mở rộng nội dung chi tiết hơn và hấp dẫn hơn
3. Làm cho nó chuyên nghiệp trong khi vẫn giữ ý nghĩa ban đầu
4. Định dạng nội dung với các đoạn văn phù hợp
5. Trả về một đối tượng JSON với các khóa 'title' và 'content' 
6. TẤT CẢ NỘI DUNG PHẢI ĐƯỢC VIẾT BẰNG TIẾNG VIỆT

Định dạng phản hồi của bạn dưới dạng JSON hợp lệ với cấu trúc sau:
{{
  "title": "Tiêu đề tiếng Việt của bạn ở đây",
  "content": "Nội dung mở rộng tiếng Việt của bạn ở đây"
}}''',
                "stream": False
            },
            timeout=API_TIMEOUT
        )
        
        if response.status_code != 200:
            raise requests.exceptions.RequestException(f"Ollama API error: {response.status_code} - {response.text}")
            
        return response
    
    try:
        # Sử dụng retry cho request
        response = retry_with_backoff(make_request, max_retries=MAX_RETRIES, initial_backoff=INITIAL_BACKOFF)
        
        result = response.json()
        response_text = result.get('response', '')
        
        # Log the raw response for debugging
        logger.info(f"Raw model response: {response_text[:100]}...")
        
        # Use improved JSON parsing
        parsed_result = extract_json_from_text(response_text)
        
        return {
            "title": parsed_result.get("title", "Bài viết đã viết lại"),
            "content": parsed_result.get("content", response_text)
        }
            
    except requests.exceptions.RequestException as e:
        logger.error(f"Request error after retries: {e}")
        return {
            "title": "Lỗi kết nối",
            "content": f"Không thể kết nối đến dịch vụ AI sau nhiều lần thử: {str(e)}"
        }
    """

def generate_slug(title):
    """Generate a URL-friendly slug from a title"""
    # Convert to lowercase and replace spaces with hyphens
    slug = title.lower().replace(' ', '-')
    
    # Remove special characters
    slug = ''.join(c for c in slug if c.isalnum() or c == '-')
    
    # Add random suffix to ensure uniqueness
    random_suffix = ''.join(random.choices(string.ascii_lowercase + string.digits, k=6))
    slug = f"{slug}-{random_suffix}"
    
    return slug

def get_unprocessed_facebook_posts(limit=10):
    """Get unprocessed Facebook posts from the database"""
    conn = get_db_connection()
    if not conn:
        logger.error("Could not connect to database")
        return []
    
    try:
        cursor = conn.cursor(dictionary=True)
        query = """
        SELECT id, content, source_url, page_or_group_name
        FROM facebook_posts
        WHERE processed = 0
        LIMIT %s
        """
        cursor.execute(query, (limit,))
        posts = cursor.fetchall()
        return posts
    except mysql.connector.Error as err:
        logger.error(f"Database error: {err}")
        return []
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# Thêm hàm retry (thử lại) cho các kết nối đến API
def retry_with_backoff(func, max_retries=3, initial_backoff=5):
    """
    Retry a function with exponential backoff
    
    Args:
        func: Function to retry
        max_retries: Maximum number of retries
        initial_backoff: Initial backoff time in seconds
        
    Returns:
        Result of the function or raises the last exception
    """
    retries = 0
    backoff = initial_backoff
    
    while retries < max_retries:
        try:
            return func()
        except requests.exceptions.RequestException as e:
            retries += 1
            if retries == max_retries:
                logger.error(f"Max retries ({max_retries}) reached. Final error: {e}")
                raise
            
            wait_time = backoff * (2 ** (retries - 1))
            logger.warning(f"Request failed. Retrying in {wait_time} seconds... (Attempt {retries}/{max_retries})")
            time.sleep(wait_time)

def limit_text_size(text, max_size=None):
    """
    Limit text size to avoid excessively long prompts
    
    Args:
        text (str): Original text
        max_size (int): Maximum size in characters
        
    Returns:
        str: Limited text
    """
    if max_size is None:
        max_size = MAX_TEXT_SIZE
        
    if len(text) <= max_size:
        return text
    
    logger.warning(f"Limiting text from {len(text)} to {max_size} characters")
    
    # Try to find a sentence boundary near the limit
    truncated = text[:max_size]
    sentence_end = max(
        truncated.rfind('.'),
        truncated.rfind('!'),
        truncated.rfind('?'),
        truncated.rfind('\n')
    )
    
    # If found a sentence end, truncate there
    if sentence_end > max_size * 0.7:  # At least 70% of the desired length
        return text[:sentence_end+1] + "..."
    else:
        # Otherwise truncate at max_size and add ellipsis
        return text[:max_size] + "..."

@app.route('/api/rewrite', methods=['POST'])
def rewrite_post():
    """API endpoint to rewrite a single Facebook post"""
    if not request.is_json:
        return jsonify({"error": "Request must be JSON"}), 400
    
    data = request.get_json()
    post_id = data.get('post_id')
    text = data.get('text')
    
    if not text:
        return jsonify({"error": "No text provided"}), 400
    
    # Rewrite the text using the Gemma 2 model
    rewritten_data = rewrite_text_with_gemma(text)
    
    # Save to database if post_id is provided
    result = {"success": True, "rewritten": rewritten_data}
    
    if post_id:
        post_data = {"id": post_id, "content": text}
        save_result = save_rewritten_article(post_id, post_data, rewritten_data)
        result["saved_to_db"] = save_result
    
    return jsonify(result)

@app.route('/api/process-batch', methods=['POST'])
def process_batch():
    """Process a batch of unprocessed Facebook posts"""
    data = request.get_json() if request.is_json else {}
    limit = data.get('limit', 10)
    
    # Get unprocessed posts
    posts = get_unprocessed_facebook_posts(limit)
    
    if not posts:
        return jsonify({"message": "No unprocessed posts found"}), 200
    
    results = []
    for post in posts:
        try:
            # Process each post
            rewritten_data = rewrite_text_with_gemma(post["content"])
            save_result = save_rewritten_article(post["id"], post, rewritten_data)
            
            results.append({
                "post_id": post["id"],
                "title": rewritten_data["title"],
                "saved": save_result
            })
            
            # Add a small delay to avoid overwhelming the Ollama API
            time.sleep(1)
            
        except Exception as e:
            logger.error(f"Error processing post {post['id']}: {e}")
            results.append({
                "post_id": post["id"],
                "error": str(e)
            })
    
    return jsonify({
        "processed_count": len(results),
        "results": results
    })

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({"status": "healthy"}), 200

if __name__ == '__main__':
    # Sử dụng biến môi trường PORT nếu có, mặc định là 5005
    port = int(os.getenv('PORT', 5005))
    app.run(host='0.0.0.0', port=port, debug=True) 