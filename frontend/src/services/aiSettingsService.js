import api from '../config/api';

const aiSettingsService = {
  getSettings: async () => {
    try {
      const response = await api.get('/admin/ai-settings');
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  updateSettings: async (data) => {
    try {
      const response = await api.put('/admin/ai-settings', data);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  testSettings: async (data) => {
    try {
      const response = await api.post('/admin/ai-settings/test', data);
      return response.data;
    } catch (error) {
      throw error;
    }
  }
};

export default aiSettingsService; 