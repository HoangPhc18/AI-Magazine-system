import React, { useState, useEffect } from 'react';
import axios from 'axios';

const AISettings = () => {
  const [settings, setSettings] = useState({
    api_key: '',
    model: 'gpt-3.5-turbo',
    temperature: 0.7,
    max_tokens: 2000,
    top_p: 1,
    frequency_penalty: 0,
    presence_penalty: 0,
    stop: '',
    system_prompt: '',
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const response = await axios.get('/api/admin/ai-settings');
      setSettings(response.data.data);
    } catch (error) {
      console.error('Error fetching AI settings:', error);
      setMessage({
        type: 'error',
        text: 'Không thể tải cài đặt AI',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type } = e.target;
    setSettings((prev) => ({
      ...prev,
      [name]: type === 'number' ? parseFloat(value) : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setMessage({ type: '', text: '' });

    try {
      await axios.put('/api/admin/ai-settings', settings);
      setMessage({
        type: 'success',
        text: 'Cài đặt AI đã được cập nhật thành công',
      });
    } catch (error) {
      console.error('Error saving AI settings:', error);
      setMessage({
        type: 'error',
        text: 'Không thể cập nhật cài đặt AI',
      });
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-2xl font-bold mb-6">Cài đặt AI</h1>

        {message.text && (
          <div
            className={`p-4 rounded-lg mb-6 ${
              message.type === 'success'
                ? 'bg-green-100 text-green-700'
                : 'bg-red-100 text-red-700'
            }`}
          >
            {message.text}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">Cấu hình API</h2>
            <div className="space-y-4">
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
                  <option value="gpt-4-turbo">GPT-4 Turbo</option>
                </select>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">Tham số tạo văn bản</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Temperature
                </label>
                <input
                  type="number"
                  name="temperature"
                  value={settings.temperature}
                  onChange={handleChange}
                  min="0"
                  max="2"
                  step="0.1"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
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
                  Top P
                </label>
                <input
                  type="number"
                  name="top_p"
                  value={settings.top_p}
                  onChange={handleChange}
                  min="0"
                  max="1"
                  step="0.1"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Frequency Penalty
                </label>
                <input
                  type="number"
                  name="frequency_penalty"
                  value={settings.frequency_penalty}
                  onChange={handleChange}
                  min="-2"
                  max="2"
                  step="0.1"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Presence Penalty
                </label>
                <input
                  type="number"
                  name="presence_penalty"
                  value={settings.presence_penalty}
                  onChange={handleChange}
                  min="-2"
                  max="2"
                  step="0.1"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  Stop Sequences
                </label>
                <input
                  type="text"
                  name="stop"
                  value={settings.stop}
                  onChange={handleChange}
                  placeholder="Phân tách bằng dấu phẩy"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">System Prompt</h2>
            <div>
              <textarea
                name="system_prompt"
                value={settings.system_prompt}
                onChange={handleChange}
                rows="6"
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Nhập system prompt mặc định..."
              />
            </div>
          </div>

          <div className="flex justify-end">
            <button
              type="submit"
              disabled={saving}
              className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50"
            >
              {saving ? 'Đang lưu...' : 'Lưu cài đặt'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AISettings; 