import axios from './axios';

export const authService = {
  login: async (credentials) => {
    const response = await axios.post('/api/auth/login', credentials);
    return response.data;
  },

  register: async (userData) => {
    const response = await axios.post('/api/auth/register', userData);
    return response.data;
  },

  logout: () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },

  getCurrentUser: () => {
    return JSON.parse(localStorage.getItem('user'));
  },

  isAuthenticated: () => {
    return !!localStorage.getItem('token');
  },
}; 