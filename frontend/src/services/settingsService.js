import axios from 'axios';
import { API_URL } from '../config';

const settingsService = {
  getSettings: async () => {
    try {
      const response = await axios.get(`${API_URL}/settings`);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  updateSettings: async (formData) => {
    try {
      const response = await axios.put(`${API_URL}/settings`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      return response.data;
    } catch (error) {
      throw error;
    }
  }
};

export default settingsService; 