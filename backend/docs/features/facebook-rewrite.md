# Facebook Rewrite Feature

## Overview

The Facebook Rewrite feature allows the system to scrape content from Facebook posts and rewrite them into proper articles using AI. This feature preserves the source information, including the original Facebook post URL.

## Core Components

### Models

- `FacebookPost`: Stores the original Facebook post content, source URL, and processing status.
- `Article`: Used to store original source information including the source URL.
- `RewrittenArticle`: Stores the rewritten article created from a Facebook post.
- `ApprovedArticle`: The final published article after approval.

## Process Flow

1. **Facebook Post Scraping**: The system scrapes content from Facebook posts and stores them in the `facebook_posts` table.
2. **Rewriting Content**: When rewriting a Facebook post, the system:
   - Creates an `Article` record with the source URL from the `facebook_posts` table
   - Creates a `RewrittenArticle` that references the original Article
3. **Approval Process**: When a rewritten article is approved, it's moved to the `approved_articles` table:
   - The source URL is preserved in the transition
   - The final published article maintains a link to the original content source

## Source URL Handling

The Facebook rewrite feature preserves the source URL throughout the article lifecycle:

1. When a Facebook post is scraped, the original post URL is stored in the `source_url` column of the `facebook_posts` table.

2. During Facebook post rewrite conversion to a RewrittenArticle:
   - An Article record is created with the source_url from the Facebook post
   - The source_name is always set to 'Facebook' for clarity and simplicity
   - The RewrittenArticle links to this original Article via original_article_id

3. When approving a RewrittenArticle and moving it to ApprovedArticle:
   - The source_url is transferred from the original Article to the ApprovedArticle
   - This preserves the source attribution throughout the content lifecycle

4. UI displays:
   - Source URLs are displayed in both RewrittenArticle and ApprovedArticle index views
   - Facebook sources are displayed simply as 'Facebook' rather than showing the full URL
   - Clickable links are provided to navigate to the original Facebook post
   - The Facebook logo icon is displayed for better visual identification of Facebook sources

This ensures proper attribution of content and allows administrators to quickly access the original source post on Facebook when needed, while keeping the interface clean and easy to understand.

## API Endpoints

### Facebook Rewrite API
- **POST** `/api/rewrite`: Rewrites a single Facebook post
- **POST** `/api/process-batch`: Processes a batch of unprocessed Facebook posts

### Backend API
- **POST** `/api/admin/facebook/create-article`: Creates a rewritten article from a Facebook post
- **POST** `/api/admin/facebook/process-batch`: Processes a batch of Facebook posts

## Admin Interface

Facebook posts can be managed through the admin interface:
- View all scraped Facebook posts
- Manually rewrite a selected post
- View processing status
- Access the original Facebook posts via source URLs 