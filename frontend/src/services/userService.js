import axios from 'axios';
import errorService from './errorService';

const userService = {
  getAll: async (params = {}) => {
    try {
      const response = await axios.get('/api/users', { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getById: async (id) => {
    try {
      const response = await axios.get(`/api/users/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  create: async (data) => {
    try {
      const response = await axios.post('/api/users', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  update: async (id, data) => {
    try {
      const response = await axios.put(`/api/users/${id}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  delete: async (id) => {
    try {
      await axios.delete(`/api/users/${id}`);
      return true;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  updateProfile: async (data) => {
    try {
      const response = await axios.put('/api/users/profile', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  changePassword: async (data) => {
    try {
      const response = await axios.put('/api/users/change-password', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  forgotPassword: async (email) => {
    try {
      const response = await axios.post('/api/users/forgot-password', { email });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  resetPassword: async (token, data) => {
    try {
      const response = await axios.post(`/api/users/reset-password/${token}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },
};

export default userService; 