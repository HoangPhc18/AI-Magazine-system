import axios from 'axios';
import errorService from './errorService';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

const api = axios.create({
  baseURL: `${API_URL}/api`,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 10000, // 10 seconds
});

// Add token to header if it exists
const token = localStorage.getItem('token');
if (token) {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Add request interceptor to add token to all requests
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error('Request Error:', error);
    return Promise.reject(error);
  }
);

// Add response interceptor to handle errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.code === 'ERR_NETWORK') {
      console.error('Network Error: Unable to connect to the server. Please check if the backend server is running.');
    } else if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/auth/login';
    } else if (error.response?.status === 403) {
      console.error('Access Denied: You do not have permission to access this resource.');
    } else if (error.response?.status === 404) {
      console.error('Resource Not Found: The requested resource does not exist.');
    } else if (error.response?.status === 500) {
      console.error('Server Error: An unexpected error occurred on the server.');
    } else {
      console.error('API Error:', error.message);
    }
    return Promise.reject(error);
  }
);

export default api; 