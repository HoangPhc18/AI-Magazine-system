#!/bin/bash

# Script to run the scraper and ensure articles are imported to the database

echo "Starting scraper with auto-import..."

# Step 0: Setup directories
python setup_dirs.py

# Step 1: Run the scraper to collect articles
python main.py --all --auto-send

# Step 2: Import any remaining JSON files that might not have been imported
python test_import.py

echo "Process completed!" 