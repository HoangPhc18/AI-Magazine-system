import axios from 'axios';
import { API_URL } from '../config';
import errorService from './errorService';

const userService = {
  getUsers: async (page = 1) => {
    const response = await axios.get(`${API_URL}/api/admin/users`, {
      params: { page },
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });
    return response.data;
  },

  getUser: async (id) => {
    const response = await axios.get(`${API_URL}/api/admin/users/${id}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });
    return response.data;
  },

  createUser: async (userData) => {
    console.log('Creating user with data:', userData); // Debug log
    try {
      const response = await axios.post(`${API_URL}/api/admin/users`, userData, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error creating user:', error.response?.data || error); // Debug log
      throw error;
    }
  },

  updateUser: async (id, userData) => {
    const response = await axios.put(`${API_URL}/api/admin/users/${id}`, userData, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });
    return response.data;
  },

  deleteUser: async (id) => {
    const response = await axios.delete(`${API_URL}/api/admin/users/${id}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });
    return response.data;
  },

  updateUserStatus: async (id, isActive) => {
    const response = await axios.patch(`${API_URL}/api/admin/users/${id}/status`, {
      is_active: isActive
    }, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    });
    return response.data;
  },

  updateProfile: async (data) => {
    try {
      const response = await axios.put(`${API_URL}/api/users/profile`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  changePassword: async (data) => {
    try {
      const response = await axios.put(`${API_URL}/api/users/change-password`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  forgotPassword: async (email) => {
    try {
      const response = await axios.post(`${API_URL}/api/users/forgot-password`, { email });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  resetPassword: async (token, data) => {
    try {
      const response = await axios.post(`${API_URL}/api/users/reset-password/${token}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },
};

export default userService; 