import mysql.connector
from mysql.connector import Error
from config.config import DB_CONFIG

def get_db_connection():
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Error connecting to MySQL Database: {e}")
        return None

def execute_query(query, params=None):
    connection = get_db_connection()
    if connection is None:
        return None
    
    try:
        cursor = connection.cursor()
        if params:
            cursor.execute(query, params)
        else:
            cursor.execute(query)
        
        if query.lower().startswith('select'):
            result = cursor.fetchall()
            return result
        else:
            connection.commit()
            return cursor.rowcount
    except Error as e:
        print(f"Error executing query: {e}")
        return None
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

def insert_article(title, url, source, content, category_id):
    query = """
    INSERT INTO articles (title, url, source, content, category_id)
    VALUES (%s, %s, %s, %s, %s)
    """
    params = (title, url, source, content, category_id)
    return execute_query(query, params)

def insert_rewritten_article(article_id, rewritten_content, status='pending'):
    query = """
    INSERT INTO rewritten_articles (article_id, rewritten_content, status)
    VALUES (%s, %s, %s)
    """
    params = (article_id, rewritten_content, status)
    return execute_query(query, params)

def get_categories():
    query = "SELECT id, name FROM categories"
    return execute_query(query)

def get_unprocessed_articles():
    query = """
    SELECT a.id, a.title, a.content
    FROM articles a
    LEFT JOIN rewritten_articles r ON a.id = r.article_id
    WHERE r.id IS NULL
    """
    return execute_query(query) 