import api from './api';

export const aiSettingsService = {
  getSettings: async () => {
    try {
      console.log('Fetching AI settings...');
      const response = await api.get('/ai-settings');
      console.log('AI Settings API response:', response.data);
      return response.data.data || {
        api_key: '',
        model: 'gpt-3.5-turbo',
        temperature: 0.7,
        max_tokens: 1000,
        top_p: 1,
        frequency_penalty: 0,
        presence_penalty: 0
      };
    } catch (error) {
      console.error('Error in aiSettingsService.getSettings:', error);
      throw error;
    }
  },

  updateSettings: async (data) => {
    try {
      console.log('Updating AI settings with data:', data);
      const response = await api.put('/ai-settings', data);
      console.log('Update AI settings API response:', response.data);
      return response.data.data;
    } catch (error) {
      console.error('Error in aiSettingsService.updateSettings:', error);
      throw error;
    }
  }
}; 