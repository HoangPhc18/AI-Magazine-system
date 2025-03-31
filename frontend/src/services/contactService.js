import api from '../config/api';

const contactService = {
  sendMessage: async (message) => {
    try {
      const response = await api.post('/contact', message);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  getMessages: async () => {
    try {
      const response = await api.get('/contact');
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  deleteMessage: async (messageId) => {
    try {
      const response = await api.delete(`/contact/${messageId}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },
};

export default contactService; 