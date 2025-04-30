import mysql.connector
import os
import logging

# Import module config
from config import get_config, reload_config

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Tải cấu hình
config = get_config()

# Database configuration
DB_CONFIG = {
    'host': config.get('DB_HOST'),
    'user': config.get('DB_USER'),
    'password': config.get('DB_PASSWORD')
}

def init_database():
    """Initialize the database and tables"""
    try:
        # First connect without specifying the database
        conn = mysql.connector.connect(
            host=DB_CONFIG['host'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password']
        )
        
        cursor = conn.cursor()
        
        # Create database if it doesn't exist
        db_name = config.get('DB_NAME')
        cursor.execute(f"CREATE DATABASE IF NOT EXISTS {db_name}")
        logger.info(f"Database '{db_name}' created or already exists")
        
        # Switch to the database
        cursor.execute(f"USE {db_name}")
        
        # Create table for Facebook rewrites
        cursor.execute("""
        CREATE TABLE IF NOT EXISTS facebook_rewrites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_text TEXT NOT NULL,
            rewritten_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
        """)
        logger.info("Table 'facebook_rewrites' created or already exists")
        
        conn.commit()
        logger.info("Database initialization completed successfully")
        
    except mysql.connector.Error as err:
        logger.error(f"Database initialization error: {err}")
    
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    init_database() 