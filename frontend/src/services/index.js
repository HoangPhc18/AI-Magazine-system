import axios from 'axios';
import articleService from './articleService';
import categoryService from './categoryService';
import userService from './userService';
import fileService from './fileService';
import dateService from './dateService';
import errorService from './errorService';
import validationService from './validationService';
import contactService from './contactService';
import authService from './authService';
import settingsService from './settingsService';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Configure axios defaults
axios.defaults.baseURL = API_URL;
axios.defaults.headers.common['Accept'] = 'application/json';

// Add request interceptor for authentication
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor for error handling
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export {
  articleService,
  categoryService,
  userService,
  fileService,
  dateService,
  errorService,
  validationService,
  contactService,
  authService,
  settingsService
}; 