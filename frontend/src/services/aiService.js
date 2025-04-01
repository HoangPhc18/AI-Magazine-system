import axios from 'axios';
import { API_URL } from '../config';

const aiService = {
  async getSettings() {
    try {
      const response = await axios.get(`${API_URL}/admin/settings/ai`);
      return response.data.data;
    } catch (error) {
      throw error;
    }
  },

  async updateSettings(settings) {
    try {
      const response = await axios.put(`${API_URL}/admin/settings/ai`, settings);
      return response.data.data;
    } catch (error) {
      throw error;
    }
  },

  async testConnection() {
    try {
      const response = await axios.post(`${API_URL}/admin/settings/ai/test`);
      return response.data.data;
    } catch (error) {
      throw error;
    }
  },

  async resetSettings() {
    try {
      const response = await axios.post(`${API_URL}/admin/settings/ai/reset`);
      return response.data.data;
    } catch (error) {
      throw error;
    }
  }
};

export default aiService; 