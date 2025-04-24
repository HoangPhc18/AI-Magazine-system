import re

def clean_content_for_db(text):
    """
    Clean up content for database storage by removing unnecessary characters
    
    Args:
        text (str): Text to clean
        
    Returns:
        str: Cleaned text
    """
    if not text:
        return ""
    
    # Remove JSON artifacts if they exist
    text = re.sub(r'^\s*[\{\[]|[\}\]]\s*$', '', text)
    
    # Remove quoted strings JSON artifacts
    text = re.sub(r'^\s*["\']|["\']\s*$', '', text)
    
    # Remove any markdown formatting
    text = re.sub(r'```[a-zA-Z]*\n|```', '', text)
    
    # Remove escape characters
    text = re.sub(r'\\(["\\/bfnrt])', r'\1', text)
    
    # Replace escaped unicode
    text = re.sub(r'\\u([0-9a-fA-F]{4})', lambda m: chr(int(m.group(1), 16)), text)
    
    # Remove any triple backticks that might remain
    text = re.sub(r'```', '', text)
    
    # Normalize whitespace
    text = re.sub(r'\s+', ' ', text).strip()
    
    # Add proper paragraph spacing (empty line between paragraphs)
    text = re.sub(r'(\. |\? |\! )([A-ZĐÁÀẢÃẠÂẤẦẨẪẬĂẮẰẲẴẶÉÈẺẼẸÊẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢÚÙỦŨỤƯỨỪỬỮỰÝỲỶỸỴ])', r'.\n\n\2', text)
    
    # Final trim
    return text.strip() 