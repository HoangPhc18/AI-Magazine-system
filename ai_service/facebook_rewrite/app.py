from flask import Flask, request, jsonify
import os
import requests
import json
import mysql.connector
import logging
import datetime
import time
import string
import random
import re
import google.generativeai as genai

# Import module config
from config import get_config, reload_config

# Tải cấu hình
config = get_config()

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': config.get('DB_HOST'),
    'user': config.get('DB_USER'),
    'password': config.get('DB_PASSWORD'),
    'database': config.get('DB_NAME')
}

# Google Gemini configuration
GEMINI_API_KEY = config.get('GEMINI_API_KEY', '')
GEMINI_MODEL = config.get('GEMINI_MODEL', 'gemini-1.5-flash-latest')

# Ollama configuration (keeping for backward compatibility)
OLLAMA_HOST = config.get('OLLAMA_HOST', 'http://localhost:11434')
OLLAMA_MODEL = config.get('OLLAMA_MODEL', 'gemma2:latest')

# Timeout configuration
API_TIMEOUT = config.get('API_TIMEOUT', 600)
MAX_RETRIES = config.get('MAX_RETRIES', 3)
INITIAL_BACKOFF = config.get('INITIAL_BACKOFF', 5)
MAX_TEXT_SIZE = config.get('MAX_TEXT_SIZE', 8000)

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
        
        # Find or create the "Tin tức" category
        find_category_query = """
        SELECT id FROM categories WHERE slug = 'tin-tuc' LIMIT 1
        """
        cursor.execute(find_category_query)
        category_result = cursor.fetchone()
        
        if category_result:
            category_id = category_result[0]
            logger.info(f"Found existing 'Tin tức' category with ID: {category_id}")
        else:
            # Create the "Tin tức" category
            create_category_query = """
            INSERT INTO categories (name, slug, description, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s)
            """
            cursor.execute(create_category_query, (
                'Tin tức',
                'tin-tuc',
                'Tin tức tổng hợp',
                now,
                now
            ))
            category_id = cursor.lastrowid
            logger.info(f"Created new 'Tin tức' category with ID: {category_id}")
        
        # Default values for user_id
        user_id = 1  # System user
        
        # First, create an Article entry to store the source information
        # Get the Facebook post source_url
        source_url = ""
        get_facebook_post_query = """
        SELECT source_url FROM facebook_posts WHERE id = %s
        """
        cursor.execute(get_facebook_post_query, (post_id,))
        facebook_post_result = cursor.fetchone()
        
        if facebook_post_result and facebook_post_result[0]:
            source_url = facebook_post_result[0]
            logger.info(f"Found source_url for Facebook post {post_id}: {source_url}")
        
        # Create the Article entry
        article_slug = f"{slug}-source-{post_id}"
        create_article_query = """
        INSERT INTO articles (
            title, slug, summary, content, source_name, source_url, is_processed, created_at, updated_at
        )
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        
        # Create summary from the first part of content
        summary = clean_content[:160] + "..." if len(clean_content) > 160 else clean_content
        
        cursor.execute(create_article_query, (
            clean_title,
            article_slug,
            summary,
            post_data.get("content", ""),
            "Facebook",
            source_url,
            1,  # is_processed = true
            now,
            now
        ))
        
        original_article_id = cursor.lastrowid
        logger.info(f"Created article record with ID {original_article_id} for storing source information")
        
        # Check if subcategories table exists
        subcategory_id = None
        try:
            # First check if subcategories table exists
            check_table_query = """
            SELECT COUNT(*)
            FROM information_schema.tables 
            WHERE table_schema = %s 
            AND table_name = 'subcategories'
            """
            cursor.execute(check_table_query, (DB_CONFIG['database'],))
            table_exists = cursor.fetchone()[0] > 0
            
            if table_exists:
                # Find or create the "Xu hướng" subcategory under the "Tin tức" category
                find_subcategory_query = """
                SELECT id FROM subcategories WHERE slug = 'xu-huong' AND parent_category_id = %s LIMIT 1
                """
                cursor.execute(find_subcategory_query, (category_id,))
                subcategory_result = cursor.fetchone()
                
                if subcategory_result:
                    subcategory_id = subcategory_result[0]
                    logger.info(f"Found existing 'Xu hướng' subcategory with ID: {subcategory_id}")
                else:
                    # Create the "Xu hướng" subcategory
                    create_subcategory_query = """
                    INSERT INTO subcategories (name, slug, description, parent_category_id, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s)
                    """
                    cursor.execute(create_subcategory_query, (
                        'Xu hướng',
                        'xu-huong',
                        'Các xu hướng mới nổi và phổ biến',
                        category_id,
                        now,
                        now
                    ))
                    subcategory_id = cursor.lastrowid
                    logger.info(f"Created new 'Xu hướng' subcategory with ID: {subcategory_id}")
            else:
                logger.warning("Subcategories table does not exist. Will save article without subcategory.")
        except mysql.connector.Error as e:
            logger.warning(f"Error when checking for subcategories: {e}. Will save article without subcategory.")
        
        # Create a meta description from the first 100-200 chars of the content
        meta_desc = clean_content[:200] + "..." if len(clean_content) > 200 else clean_content
        
        # Prepare the SQL statement based on whether we have a subcategory_id
        if subcategory_id:
            # Insert into rewritten_articles table with subcategory_id
            insert_query = """
            INSERT INTO rewritten_articles (
                title, slug, content, meta_description, user_id, category_id, subcategory_id,
                original_article_id, ai_generated, status, 
                created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            
            insert_params = (
                clean_title,
                slug,
                clean_content,
                meta_desc,
                user_id,
                category_id,
                subcategory_id,
                original_article_id,  # Use the created article record instead of post_id
                1,  # ai_generated = true
                'pending',  # status
                now,
                now
            )
        else:
            # Insert without subcategory_id field
            insert_query = """
            INSERT INTO rewritten_articles (
                title, slug, content, meta_description, user_id, category_id,
                original_article_id, ai_generated, status, 
                created_at, updated_at
            )
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            
            insert_params = (
                clean_title,
                slug,
                clean_content,
                meta_desc,
                user_id,
                category_id,
                original_article_id,  # Use the created article record instead of post_id
                1,  # ai_generated = true
                'pending',  # status
                now,
                now
            )
        
        cursor.execute(insert_query, insert_params)
        
        # Update facebook_posts to mark as processed
        update_query = """
        UPDATE facebook_posts
        SET processed = 1, updated_at = %s
        WHERE id = %s
        """
        cursor.execute(update_query, (now, post_id))
        
        conn.commit()
        logger.info(f"Successfully saved rewritten article for post ID {post_id} with category_id {category_id} and subcategory_id {subcategory_id}")
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
            logger.info(f"Processing post ID {post['id']}")
            rewritten_data = rewrite_text_with_gemma(post["content"])
            
            # Try to save with proper category and subcategory
            save_result = save_rewritten_article(post["id"], post, rewritten_data)
            
            results.append({
                "post_id": post["id"],
                "title": rewritten_data["title"],
                "saved": save_result
            })
            
            # Add a small delay to avoid overwhelming the API
            time.sleep(1)
            
        except mysql.connector.Error as db_err:
            # Handle database errors specifically
            error_msg = str(db_err)
            logger.error(f"Database error processing post {post['id']}: {error_msg}")
            
            if "categories" in error_msg or "subcategories" in error_msg:
                error_detail = "Error with categories or subcategories. Ensure tables exist and have correct structure."
            else:
                error_detail = "Database error occurred during processing."
                
            results.append({
                "post_id": post["id"],
                "error": error_detail,
                "detail": error_msg
            })
            
        except Exception as e:
            # Handle other errors
            logger.error(f"Error processing post {post['id']}: {e}")
            results.append({
                "post_id": post["id"],
                "error": "General processing error",
                "detail": str(e)
            })
    
    return jsonify({
        "processed_count": len(results),
        "results": results
    })

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    # Lấy cấu hình hiện tại
    current_config = get_config()
    
    return jsonify({
        "status": "healthy",
        "service": "facebook_rewrite_api",
        "gemini": {
            "model": current_config.get("GEMINI_MODEL"),
            "api_key_configured": bool(current_config.get("GEMINI_API_KEY"))
        },
        "database": {
            "host": current_config.get("DB_HOST"),
            "name": current_config.get("DB_NAME")
        },
        "config": {
            "max_text_size": current_config.get("MAX_TEXT_SIZE"),
            "api_timeout": current_config.get("API_TIMEOUT")
        }
    }), 200

@app.route('/reload-config', methods=['POST'])
def reload_configuration():
    """Endpoint để tải lại cấu hình từ file .env"""
    # Tải lại cấu hình
    global GEMINI_API_KEY, GEMINI_MODEL, OLLAMA_HOST, OLLAMA_MODEL
    global API_TIMEOUT, MAX_RETRIES, INITIAL_BACKOFF, MAX_TEXT_SIZE, DB_CONFIG
    
    try:
        new_config = reload_config()
        
        # Cập nhật các biến toàn cục
        GEMINI_API_KEY = new_config.get('GEMINI_API_KEY', '')
        GEMINI_MODEL = new_config.get('GEMINI_MODEL', 'gemini-1.5-flash-latest')
        OLLAMA_HOST = new_config.get('OLLAMA_HOST', 'http://localhost:11434')
        OLLAMA_MODEL = new_config.get('OLLAMA_MODEL', 'gemma2:latest')
        API_TIMEOUT = new_config.get('API_TIMEOUT', 600)
        MAX_RETRIES = new_config.get('MAX_RETRIES', 3)
        INITIAL_BACKOFF = new_config.get('INITIAL_BACKOFF', 5)
        MAX_TEXT_SIZE = new_config.get('MAX_TEXT_SIZE', 8000)
        
        # Cập nhật DB_CONFIG
        DB_CONFIG.update({
            'host': new_config.get('DB_HOST'),
            'user': new_config.get('DB_USER'),
            'password': new_config.get('DB_PASSWORD'),
            'database': new_config.get('DB_NAME')
        })
        
        # Khởi tạo lại Gemini API
        genai.configure(api_key=GEMINI_API_KEY)
        
        logger.info("Cấu hình đã được tải lại thành công")
        
        return jsonify({
            "status": "ok",
            "message": "Cấu hình đã được tải lại thành công",
            "config": {
                "gemini_model": GEMINI_MODEL,
                "api_timeout": API_TIMEOUT,
                "max_text_size": MAX_TEXT_SIZE
            }
        })
    except Exception as e:
        logger.error(f"Lỗi khi tải lại cấu hình: {e}")
        return jsonify({
            "status": "error",
            "message": f"Lỗi khi tải lại cấu hình: {str(e)}"
        }), 500

if __name__ == '__main__':
    # Sử dụng cấu hình từ config thay vì biến môi trường
    port = config.get('PORT_FACEBOOK_REWRITE', 5005)
    host = config.get('HOST', '0.0.0.0')
    debug = config.get('DEBUG', False)
    
    logger.info(f"Bắt đầu Facebook Rewrite API service tại {host}:{port}, debug={debug}")
    app.run(host=host, port=port, debug=debug) 