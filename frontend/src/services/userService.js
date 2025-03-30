import api from './api';

export const userService = {
  getAll: async () => {
    try {
      console.log('Fetching users...');
      const response = await api.get('/users');
      console.log('Users API response:', response.data);
      return response.data.data || [];
    } catch (error) {
      console.error('Error in userService.getAll:', error);
      throw error;
    }
  },

  getById: async (id) => {
    try {
      console.log('Fetching user by ID:', id);
      const response = await api.get(`/users/${id}`);
      console.log('User API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error in userService.getById:', error);
      throw error;
    }
  },

  create: async (data) => {
    try {
      console.log('Creating user with data:', data);
      const response = await api.post('/users', data);
      console.log('Create user API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error in userService.create:', error);
      throw error;
    }
  },

  update: async (id, data) => {
    try {
      console.log('Updating user:', id, 'with data:', data);
      const response = await api.put(`/users/${id}`, data);
      console.log('Update user API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error in userService.update:', error);
      throw error;
    }
  },

  delete: async (id) => {
    try {
      console.log('Deleting user:', id);
      const response = await api.delete(`/users/${id}`);
      console.log('Delete user API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error in userService.delete:', error);
      throw error;
    }
  },

  updateRole: async (id, role) => {
    try {
      console.log('Updating user role:', id, 'to:', role);
      const response = await api.put(`/users/${id}/role`, { role });
      console.log('Update role API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error in userService.updateRole:', error);
      throw error;
    }
  }
}; 