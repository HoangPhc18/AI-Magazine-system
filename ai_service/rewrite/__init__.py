"""
Dịch vụ viết lại nội dung bài viết bằng AI.

Module này cung cấp các hàm và lớp để viết lại nội dung bài viết
sử dụng các mô hình AI khác nhau như OpenAI API, Google Gemini API hoặc Ollama local.
"""

from .rewrite import RewriteService, rewrite_content
from .model import create_model, BaseRewriteModel, OpenAIModel, OllamaModel, GeminiModel
from .utils import load_config, save_config, format_prompt, check_api_keys, is_ollama_available

__all__ = [
    'RewriteService',
    'rewrite_content',
    'create_model',
    'BaseRewriteModel',
    'OpenAIModel',
    'OllamaModel',
    'GeminiModel',
    'load_config',
    'save_config',
    'format_prompt',
    'check_api_keys',
    'is_ollama_available'
]
