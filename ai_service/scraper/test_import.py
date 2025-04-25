#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Test script to import all existing JSON files from the output directory into the database.
"""

import os
import glob
import logging
import sys
from datetime import datetime
from main import import_json_file_to_backend, import_all_to_backend, OUTPUT_DIR

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler(f"test_import_{datetime.now().strftime('%Y%m%d')}.log", encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger()

def import_all_json_files():
    """
    Import all JSON files in the output directory to the database
    """
    # Check if output directory exists
    if not os.path.exists(OUTPUT_DIR):
        logger.error(f"Output directory {OUTPUT_DIR} does not exist")
        return
    
    # Find all JSON files
    json_files = glob.glob(os.path.join(OUTPUT_DIR, "*.json"))
    
    if not json_files:
        logger.warning(f"No JSON files found in {OUTPUT_DIR}")
        return
    
    logger.info(f"Found {len(json_files)} JSON files to import")
    
    # Import each file
    success = 0
    failed = 0
    
    for json_file in json_files:
        logger.info(f"Importing {json_file}...")
        try:
            # Import the file
            success_count, failed_count = import_json_file_to_backend(json_file)
            
            if success_count > 0:
                logger.info(f"Successfully imported {success_count} articles from {json_file}")
                success += 1
            else:
                logger.warning(f"No articles imported from {json_file}")
                failed += 1
        except Exception as e:
            logger.error(f"Error importing {json_file}: {str(e)}")
            failed += 1
    
    logger.info(f"Import complete: {success} files succeeded, {failed} files failed")

if __name__ == "__main__":
    logger.info("Starting test import script")
    import_all_json_files()
    logger.info("Finished test import script") 