import api from './api';
import errorService from './errorService';

export const categoryService = {
  // Lấy danh sách danh mục
  getAll: async (params = {}) => {
    try {
      const response = await api.get('/categories', { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Lấy thông tin chi tiết danh mục
  getById: async (id) => {
    try {
      const response = await api.get(`/categories/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Tạo danh mục mới
  create: async (data) => {
    try {
      const response = await api.post('/categories', data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Cập nhật danh mục
  update: async (id, data) => {
    try {
      const response = await api.put(`/categories/${id}`, data);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Xóa danh mục
  delete: async (id) => {
    try {
      const response = await api.delete(`/categories/${id}`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Chuyển đổi trạng thái danh mục
  toggleCategoryStatus: async (id) => {
    try {
      const response = await api.put(`/admin/categories/${id}/toggle-status`);
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  // Public categories
  getPublicCategories: async () => {
    try {
      const response = await api.get('/categories');
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getArticles: async (id, params = {}) => {
    try {
      const response = await api.get(`/categories/${id}/articles`, { params });
      return response.data;
    } catch (error) {
      throw errorService.handleApiError(error);
    }
  },

  getCategories: async (page = 1) => {
    const response = await api.get(`/admin/categories?page=${page}`);
    return response.data;
  },

  getCategory: async (id) => {
    const response = await api.get(`/admin/categories/${id}`);
    return response.data;
  },

  createCategory: async (data) => {
    const response = await api.post('/admin/categories', data);
    return response.data;
  },

  updateCategory: async (id, data) => {
    const response = await api.put(`/admin/categories/${id}`, data);
    return response.data;
  },

  deleteCategory: async (id) => {
    const response = await api.delete(`/admin/categories/${id}`);
    return response.data;
  }
};

export default categoryService; 