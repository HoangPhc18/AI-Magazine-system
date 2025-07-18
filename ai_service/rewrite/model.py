"""
Module cung cấp các lớp mô hình khác nhau cho dịch vụ viết lại nội dung
"""

import os
import json
import time
import requests
from typing import Dict, List, Any, Optional, Union
from abc import ABC, abstractmethod

from langchain.chains import LLMChain
from langchain.chat_models import ChatOpenAI
from langchain.prompts import PromptTemplate
from langchain.schema import HumanMessage, SystemMessage
from langchain.callbacks.streaming_stdout import StreamingStdOutCallbackHandler
from langchain.llms import Ollama

# Import module config
from config import get_config

from .utils import logger, DEFAULT_MODEL_GPT, DEFAULT_MODEL_OLLAMA, DEFAULT_PROMPTS

# Tải cấu hình khi import module
config = get_config()

class BaseRewriteModel(ABC):
    """
    Lớp cơ sở cho tất cả các mô hình viết lại
    """
    
    @abstractmethod
    def rewrite(self, content: str) -> str:
        """
        Viết lại nội dung
        
        Args:
            content (str): Nội dung cần viết lại
            
        Returns:
            str: Nội dung đã viết lại
        """
        pass
        
    @abstractmethod
    def get_model_info(self) -> Dict[str, Any]:
        """
        Lấy thông tin về mô hình
        
        Returns:
            Dict[str, Any]: Thông tin mô hình
        """
        pass

class OpenAIModel(BaseRewriteModel):
    """
    Mô hình viết lại sử dụng OpenAI API thông qua LangChain
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Khởi tạo mô hình OpenAI
        
        Args:
            config (Dict[str, Any]): Cấu hình cho mô hình
        """
        # Tải lại cấu hình để có thông tin mới nhất
        current_config = get_config()
        
        self.config = config
        self.api_key = config.get("api_key") or current_config.get("OPENAI_API_KEY")
        self.model_name = config.get("model", DEFAULT_MODEL_GPT)
        self.temperature = config.get("temperature", 0.7)
        self.max_tokens = config.get("max_tokens", 8000)  # Tăng max_tokens để hỗ trợ văn bản dài 500-1000 từ
        self.stream = config.get("stream", False)
        
        # Khởi tạo mô hình LangChain
        callbacks = [StreamingStdOutCallbackHandler()] if self.stream else None
        
        self.llm = ChatOpenAI(
            openai_api_key=self.api_key,
            model_name=self.model_name,
            temperature=self.temperature,
            max_tokens=self.max_tokens,
            streaming=self.stream,
            callbacks=callbacks
        )
        
        # Tạo system prompt
        self.system_prompt = config.get("system_prompt", DEFAULT_PROMPTS["openai"]["system"])
        self.user_prompt = config.get("user_prompt", DEFAULT_PROMPTS["openai"]["user"])
        
        # Tạo LLMChain với PromptTemplate
        self.template = (
            "Hãy viết lại nội dung sau đây, giữ nguyên ý nghĩa nhưng dùng cách diễn đạt khác:\n\n"
            "{content}\n\n"
            "Nội dung đã viết lại:"
        )
        self.prompt = PromptTemplate(
            template=self.template,
            input_variables=["content"]
        )
        self.chain = LLMChain(llm=self.llm, prompt=self.prompt)
        
        logger.info(f"Đã khởi tạo mô hình OpenAI {self.model_name}")
        
    def rewrite(self, content: str) -> str:
        """
        Viết lại nội dung
        
        Args:
            content (str): Nội dung cần viết lại
            
        Returns:
            str: Nội dung đã viết lại
        """
        try:
            # Lựa chọn phương pháp dựa trên cài đặt
            if self.system_prompt and self.user_prompt:
                # Sử dụng SystemMessage và HumanMessage
                messages = [
                    SystemMessage(content=self.system_prompt),
                    HumanMessage(content=self.user_prompt.format(content=content))
                ]
                response = self.llm(messages)
                return response.content
            else:
                # Sử dụng LLMChain
                response = self.chain.run(content=content)
                return response
                
        except Exception as e:
            logger.error(f"Lỗi khi gọi OpenAI API: {str(e)}")
            raise
            
    def get_model_info(self) -> Dict[str, Any]:
        """
        Lấy thông tin về mô hình
        
        Returns:
            Dict[str, Any]: Thông tin mô hình
        """
        return {
            "provider": "openai",
            "model": self.model_name,
            "temperature": self.temperature,
            "max_tokens": self.max_tokens,
            "stream": self.stream,
            "system_prompt_length": len(self.system_prompt) if self.system_prompt else 0,
            "user_prompt_length": len(self.user_prompt) if self.user_prompt else 0
        }

    def _get_openai_prompt(self, content: str, custom_prompt: Optional[str] = None) -> List[Dict]:
        """Get the prompt for OpenAI"""
        system_message = "Bạn là một nhà báo chuyên nghiệp, có khả năng viết lại nội dung với văn phong mạch lạc và hấp dẫn."
        
        if custom_prompt:
            user_message = f"{custom_prompt}\n\nNội dung cần viết lại:\n{content}"
        else:
            user_message = f"Hãy viết lại bài viết sau đây thành một đoạn văn ngắn gọn khoảng 50-100 từ, tập trung vào thông tin quan trọng nhất:\n\n{content}"
        
        return [
            {"role": "system", "content": system_message},
            {"role": "user", "content": user_message}
        ]

class OllamaModel(BaseRewriteModel):
    """
    Mô hình viết lại sử dụng Ollama local thông qua LangChain
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Khởi tạo mô hình Ollama
        
        Args:
            config (Dict[str, Any]): Cấu hình cho mô hình
        """
        # Tải lại cấu hình để có thông tin mới nhất
        current_config = get_config()
        
        self.config = config
        self.model = config.get("model", DEFAULT_MODEL_OLLAMA)
        self.temperature = config.get("temperature", 0.7)
        self.num_ctx = config.get("num_ctx", 4096)
        self.stream = config.get("stream", False)
        self.base_url = config.get("base_url") or current_config.get("OLLAMA_BASE_URL", "http://localhost:11434")
        
        # Khởi tạo mô hình LangChain
        callbacks = [StreamingStdOutCallbackHandler()] if self.stream else None
        
        self.llm = Ollama(
            model=self.model,
            temperature=self.temperature,
            num_ctx=self.num_ctx,
            base_url=self.base_url,
            callbacks=callbacks
        )
        
        # Tạo prompt
        self.system_prompt = config.get("system_prompt", DEFAULT_PROMPTS["ollama"]["system"])
        self.user_prompt = config.get("user_prompt", DEFAULT_PROMPTS["ollama"]["user"])
        
        # Tạo LLMChain với PromptTemplate
        self.template = (
            "### Instruction:\n"
            "Bạn là trợ lý viết lại nội dung. Hãy viết lại đoạn văn bản sau đây bằng cách giữ nguyên ý "
            "nghĩa nhưng thay đổi cách diễn đạt để tạo ra một bản văn mới, độc đáo và tự nhiên.\n\n"
            "### Input:\n{content}\n\n"
            "### Response:\n"
        )
        self.prompt = PromptTemplate(
            template=self.template,
            input_variables=["content"]
        )
        self.chain = LLMChain(llm=self.llm, prompt=self.prompt)
        
        logger.info(f"Đã khởi tạo mô hình Ollama {self.model} từ {self.base_url}")
        
    def rewrite(self, content: str, custom_prompt: Optional[str] = None) -> str:
        """
        Viết lại nội dung
        
        Args:
            content (str): Nội dung cần viết lại
            custom_prompt (str, optional): Prompt tùy chỉnh cho Ollama API
            
        Returns:
            str: Nội dung đã viết lại
        """
        try:
            # Lựa chọn phương pháp dựa trên cài đặt
            if self.system_prompt and self.user_prompt:
                # Sử dụng prompt trực tiếp với Ollama API
                prompt = f"{self.system_prompt}\n\n{self.user_prompt.format(content=content)}"
                
                headers = {"Content-Type": "application/json"}
                data = {
                "model": self.model,
                "prompt": prompt,
                    "temperature": self.temperature,
                    "stream": False
            }
            
                response = requests.post(
                    f"{self.base_url}/api/generate",
                    headers=headers,
                    json=data,
                    timeout=300  # 5 phút timeout cho văn bản dài
                )
            
                if response.status_code == 200:
                    result = response.json()
                    return result.get("response", "")
                else:
                    logger.error(f"Lỗi từ Ollama API: {response.status_code} - {response.text}")
                    raise Exception(f"Ollama API error: {response.status_code}")
            else:
                # Sử dụng LLMChain
                response = self.chain.run(content=content)
                return response
        
        except Exception as e:
            logger.error(f"Lỗi khi gọi Ollama API: {str(e)}")
            raise
            
    def get_model_info(self) -> Dict[str, Any]:
        """
        Lấy thông tin về mô hình
        
        Returns:
            Dict[str, Any]: Thông tin mô hình
        """
        return {
            "provider": "ollama",
            "model": self.model,
            "temperature": self.temperature,
            "num_ctx": self.num_ctx,
            "stream": self.stream,
            "base_url": self.base_url,
            "system_prompt_length": len(self.system_prompt) if self.system_prompt else 0,
            "user_prompt_length": len(self.user_prompt) if self.user_prompt else 0
        }

class GeminiModel(BaseRewriteModel):
    """
    Mô hình viết lại sử dụng Google Gemini API
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Khởi tạo mô hình Gemini
        
        Args:
            config (Dict[str, Any]): Cấu hình cho mô hình
        """
        # Tải lại cấu hình để có thông tin mới nhất
        current_config = get_config()
        
        self.config = config
        self.api_key = config.get("api_key") or current_config.get("GEMINI_API_KEY")
        self.model = config.get("model") or current_config.get("GEMINI_MODEL", "gemini-1.5-flash-latest")
        self.temperature = config.get("temperature", 0.7)
        self.max_tokens = config.get("max_tokens", 8000)
        self.stream = config.get("stream", False)
        
        # Tạo prompt
        self.system_prompt = config.get("system_prompt", DEFAULT_PROMPTS["gemini"]["system"])
        self.user_prompt = config.get("user_prompt", DEFAULT_PROMPTS["gemini"]["user"])
        
        # Import ở đây để tránh import lỗi nếu không cài đặt package
        try:
            import google.generativeai as genai
            genai.configure(api_key=self.api_key)
            
            # Tạo model Gemini
            generation_config = {
                "temperature": self.temperature,
                "max_output_tokens": self.max_tokens,
                "top_p": 0.95,
                "top_k": 0,
            }
            
            self.genai = genai
            self.model_instance = genai.GenerativeModel(
                model_name=self.model,
                generation_config=generation_config
            )
            
            logger.info(f"Đã khởi tạo mô hình Google Gemini {self.model}")
            
        except ImportError:
            logger.error("Không thể import google.generativeai. Hãy cài đặt với lệnh: pip install google-generativeai")
            raise ImportError("google-generativeai package is required for GeminiModel")
        
    def rewrite(self, content: str) -> str:
        """
        Viết lại nội dung
        
        Args:
            content (str): Nội dung cần viết lại
            
        Returns:
            str: Nội dung đã viết lại
        """
        try:
            # Tạo prompt đầy đủ
            # Gemini không có system prompt chính thức, nên gộp vào user prompt
            if self.system_prompt and self.user_prompt:
                full_prompt = f"{self.system_prompt}\n\n{self.user_prompt.format(content=content)}"
            else:
                full_prompt = f"""Bạn là trợ lý viết lại nội dung. 
Hãy viết lại bài viết sau đây thành một bài viết có cấu trúc rõ ràng, mạch lạc và hấp dẫn.
Giữ nguyên các thông tin quan trọng, ý nghĩa chính của bài viết gốc nhưng diễn đạt theo cách khác.
Chia thành các đoạn hợp lý, đảm bảo bài viết có độ dài từ 500-1000 từ:

{content}
"""
            
            # Gọi Gemini API
            response = self.model_instance.generate_content(full_prompt)
            
            if hasattr(response, 'text'):
                return response.text
            else:
                # Fallback cho phiên bản API khác
                return response.candidates[0].content.parts[0].text
                
        except Exception as e:
            logger.error(f"Lỗi khi gọi Gemini API: {str(e)}")
            raise
            
    def get_model_info(self) -> Dict[str, Any]:
        """
        Lấy thông tin về mô hình
        
        Returns:
            Dict[str, Any]: Thông tin mô hình
        """
        return {
            "provider": "gemini",
            "model": self.model,
            "temperature": self.temperature,
            "max_tokens": self.max_tokens,
            "system_prompt_length": len(self.system_prompt) if self.system_prompt else 0,
            "user_prompt_length": len(self.user_prompt) if self.user_prompt else 0
        }

def create_model(provider: str, config: Dict[str, Any]) -> BaseRewriteModel:
    """
    Tạo mô hình viết lại dựa trên nhà cung cấp
    
    Args:
        provider (str): Nhà cung cấp mô hình (openai, ollama, gemini)
        config (Dict[str, Any]): Cấu hình cho mô hình
        
    Returns:
        BaseRewriteModel: Mô hình viết lại
        
    Raises:
        ValueError: Nếu nhà cung cấp không được hỗ trợ
    """
    # Tải lại cấu hình để có thông tin mới nhất
    current_config = get_config()
    
    if not provider:
        # Lấy nhà cung cấp mặc định từ cấu hình
        provider = current_config.get("DEFAULT_PROVIDER", "gemini")
    
    provider = provider.lower()
    
    if provider == "openai":
        return OpenAIModel(config)
    elif provider == "ollama":
        return OllamaModel(config)
    elif provider == "gemini":
        return GeminiModel(config)
    else:
        raise ValueError(f"Nhà cung cấp không được hỗ trợ: {provider}")
