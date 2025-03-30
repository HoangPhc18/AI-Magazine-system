import api from './api';

export const categoryService = {
  getAll: async () => {
    try {
      console.log('Fetching categories');
      const response = await api.get('/categories');
      console.log('Categories API response:', response.data);
      return response.data.data || [];
    } catch (error) {
      console.error('Error fetching categories:', error);
      return [];
    }
  },

  getById: async (id) => {
    try {
      console.log('Fetching category with ID:', id);
      const response = await api.get(`/categories/${id}`);
      console.log('Category API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error fetching category:', error);
      throw error;
    }
  },

  create: async (data) => {
    try {
      console.log('Creating category with data:', data);
      const response = await api.post('/categories', data);
      console.log('Create category API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error creating category:', error);
      throw error;
    }
  },

  update: async (id, data) => {
    try {
      console.log('Updating category with ID:', id, 'data:', data);
      const response = await api.put(`/categories/${id}`, data);
      console.log('Update category API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error updating category:', error);
      throw error;
    }
  },

  delete: async (id) => {
    try {
      console.log('Deleting category with ID:', id);
      await api.delete(`/categories/${id}`);
      console.log('Delete category successful');
    } catch (error) {
      console.error('Error deleting category:', error);
      throw error;
    }
  }
}; 