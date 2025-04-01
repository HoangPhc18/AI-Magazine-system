import api from './api';
import errorService from './errorService';

export const userService = {
  getUsers: async (page = 1) => {
    const response = await api.get(`/admin/users?page=${page}`);
    return response.data;
  },

  getUser: async (id) => {
    const response = await api.get(`/admin/users/${id}`);
    return response.data;
  },

  createUser: async (data) => {
    const response = await api.post('/admin/users', data);
    return response.data;
  },

  updateUser: async (id, data) => {
    const response = await api.put(`/admin/users/${id}`, data);
    return response.data;
  },

  deleteUser: async (id) => {
    const response = await api.delete(`/admin/users/${id}`);
    return response.data;
  },

  updateProfile: async (data) => {
    try {
      const response = await api.put('/api/users/profile', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  changePassword: async (data) => {
    try {
      const response = await api.put('/api/users/change-password', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  forgotPassword: async (email) => {
    try {
      const response = await api.post('/api/users/forgot-password', { email });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  resetPassword: async (token, data) => {
    try {
      const response = await api.post(`/api/users/reset-password/${token}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },
};

export default userService; 