import api from './api';

export const articleService = {
  getAll: async (params = {}) => {
    try {
      console.log('Fetching articles with params:', params);
      const response = await api.get('/articles', { params });
      console.log('Articles API response:', response.data);
      return {
        data: response.data.data || [],
        meta: response.data.meta || {
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 0
        }
      };
    } catch (error) {
      console.error('Error fetching articles:', error);
      return {
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 0
        }
      };
    }
  },

  getById: async (id) => {
    try {
      console.log('Fetching article with ID:', id);
      const response = await api.get(`/articles/${id}`);
      console.log('Article API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error fetching article:', error);
      throw error;
    }
  },

  create: async (data) => {
    try {
      console.log('Creating article with data:', data);
      const response = await api.post('/articles', data);
      console.log('Create article API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error creating article:', error);
      throw error;
    }
  },

  update: async (id, data) => {
    try {
      console.log('Updating article with ID:', id, 'data:', data);
      const response = await api.put(`/articles/${id}`, data);
      console.log('Update article API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error updating article:', error);
      throw error;
    }
  },

  delete: async (id) => {
    try {
      console.log('Deleting article with ID:', id);
      await api.delete(`/articles/${id}`);
      console.log('Delete article successful');
    } catch (error) {
      console.error('Error deleting article:', error);
      throw error;
    }
  },

  getByCategory: async (categoryId, params = {}) => {
    try {
      console.log('Fetching articles for category:', categoryId, 'with params:', params);
      const response = await api.get(`/categories/${categoryId}/articles`, { params });
      console.log('Category articles API response:', response.data);
      return {
        data: response.data.data || [],
        meta: response.data.meta || {
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 0
        }
      };
    } catch (error) {
      console.error('Error fetching category articles:', error);
      return {
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 0
        }
      };
    }
  }
}; 