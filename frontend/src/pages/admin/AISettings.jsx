import React, { useState, useEffect } from 'react';
import { aiSettingsService } from '../../services/aiSettingsService';
import toast from 'react-hot-toast';

const AISettings = () => {
  const [settings, setSettings] = useState({
    api_key: '',
    model: 'gpt-3.5-turbo',
    temperature: 0.7,
    max_tokens: 1000,
    top_p: 1,
    frequency_penalty: 0,
    presence_penalty: 0
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      const data = await aiSettingsService.getSettings();
      setSettings(data);
    } catch (error) {
      console.error('Error fetching AI settings:', error);
      setError('Không thể tải cài đặt AI');
      toast.error('Không thể tải cài đặt AI');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type } = e.target;
    setSettings(prev => ({
      ...prev,
      [name]: type === 'number' ? parseFloat(value) : value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await aiSettingsService.updateSettings(settings);
      toast.success('Cập nhật cài đặt AI thành công');
    } catch (error) {
      console.error('Error updating AI settings:', error);
      toast.error('Không thể cập nhật cài đặt AI');
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center text-red-600 p-4">
        {error}
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h1 className="text-2xl font-bold mb-6">Cài đặt AI</h1>
      
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            API Key
          </label>
          <input
            type="password"
            name="api_key"
            value={settings.api_key}
            onChange={handleChange}
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Model
          </label>
          <select
            name="model"
            value={settings.model}
            onChange={handleChange}
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
            <option value="gpt-4">GPT-4</option>
            <option value="gpt-4-turbo-preview">GPT-4 Turbo</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Temperature ({settings.temperature})
          </label>
          <input
            type="range"
            name="temperature"
            min="0"
            max="2"
            step="0.1"
            value={settings.temperature}
            onChange={handleChange}
            className="mt-1 block w-full"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Max Tokens
          </label>
          <input
            type="number"
            name="max_tokens"
            value={settings.max_tokens}
            onChange={handleChange}
            min="1"
            max="4000"
            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Top P ({settings.top_p})
          </label>
          <input
            type="range"
            name="top_p"
            min="0"
            max="1"
            step="0.1"
            value={settings.top_p}
            onChange={handleChange}
            className="mt-1 block w-full"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Frequency Penalty ({settings.frequency_penalty})
          </label>
          <input
            type="range"
            name="frequency_penalty"
            min="-2"
            max="2"
            step="0.1"
            value={settings.frequency_penalty}
            onChange={handleChange}
            className="mt-1 block w-full"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Presence Penalty ({settings.presence_penalty})
          </label>
          <input
            type="range"
            name="presence_penalty"
            min="-2"
            max="2"
            step="0.1"
            value={settings.presence_penalty}
            onChange={handleChange}
            className="mt-1 block w-full"
          />
        </div>

        <div className="flex justify-end">
          <button
            type="submit"
            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            Lưu cài đặt
          </button>
        </div>
      </form>
    </div>
  );
};

export default AISettings; 