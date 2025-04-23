# Facebook Post Rewriting Service

This service uses Google Gemini API to transform Facebook posts into well-structured, engaging articles.

## Prerequisites

1. Python 3.8+
2. MySQL database with existing schema
3. Google Gemini API key (provided in .env file)

## Setup

1. Install required packages:
```bash
pip install -r requirements.txt
```

2. Configure environment variables in `.env`:
```
DB_HOST=localhost
DB_USER=your_username
DB_PASSWORD=your_password
DB_NAME=AiMagazineDB

# Gemini configuration
GEMINI_API_KEY=your_gemini_api_key
GEMINI_MODEL=gemini-1.5-flash-latest
```

## Usage

### Start the API Server

```bash
python app.py
```

### Rewrite a Single Post

To manually rewrite a post, send a POST request to `/api/rewrite`:

```bash
curl -X POST http://localhost:5005/api/rewrite \
  -H "Content-Type: application/json" \
  -d '{"text": "Your Facebook post text here", "post_id": 123}'
```

The `post_id` is optional. If provided, the rewritten content will be saved to the database.

Response example:
```json
{
  "success": true,
  "rewritten": {
    "title": "Generated Title",
    "content": "Expanded and improved content..."
  },
  "saved_to_db": true
}
```

### Process Unprocessed Posts in Batch

To automatically rewrite multiple unprocessed posts from the database:

```bash
curl -X POST http://localhost:5005/api/process-batch \
  -H "Content-Type: application/json" \
  -d '{"limit": 10}'
```

This will:
1. Fetch up to 10 unprocessed posts from the `facebook_posts` table
2. Rewrite each post into an article using Google Gemini
3. Save the rewritten content to the `rewritten_articles` table
4. Mark the posts as processed

### Test Script

A test script is included to easily test the API:

```bash
# Test with a custom post
python test_api.py --text "Your Facebook post text here"

# Test with a post ID (will save to database)
python test_api.py --text "Your post text" --post-id 123

# Run batch processing
python test_api.py --batch --limit 5
```

## Health Check

To verify the service is running:
```bash
curl http://localhost:5005/health
``` 