import api from '../config/api';

const aiService = {
  rewriteArticle: async (articleId) => {
    try {
      const response = await api.post(`/ai/rewrite/${articleId}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  getAISettings: async () => {
    try {
      const response = await api.get('/ai/settings');
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  updateAISettings: async (settings) => {
    try {
      const response = await api.put('/ai/settings', settings);
      return response.data;
    } catch (error) {
      throw error;
    }
  },
};

export default aiService; 