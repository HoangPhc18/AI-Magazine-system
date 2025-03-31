import React, { useState, useEffect } from 'react';
import { Button, Input, TextArea, Select } from '../../../components/ui';
import { aiService } from '../../../services';
import { handleApiError } from '../../../services/errorService';

const AISettings = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    api_key: '',
    model: 'gpt-3.5-turbo',
    temperature: 0.7,
    max_tokens: 2000,
    top_p: 1,
    frequency_penalty: 0,
    presence_penalty: 0,
    system_prompt: '',
    user_prompt: ''
  });
  const [errors, setErrors] = useState({});

  const modelOptions = [
    { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo' },
    { value: 'gpt-4', label: 'GPT-4' },
    { value: 'gpt-4-turbo-preview', label: 'GPT-4 Turbo' }
  ];

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setLoading(true);
      const data = await aiService.getSettings();
      setFormData(data);
    } catch (error) {
      setError(handleApiError(error).message);
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error when user types
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});

    try {
      await aiService.updateSettings(formData);
      setError(null);
    } catch (error) {
      const errorData = handleApiError(error);
      setErrors(errorData.errors || {});
    } finally {
      setSaving(false);
    }
  };

  const handleTestConnection = async () => {
    try {
      setLoading(true);
      await aiService.testConnection();
      alert('Kết nối thành công!');
    } catch (error) {
      alert(handleApiError(error).message);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div>Đang tải...</div>;
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Cài đặt AI</h1>
        <Button
          variant="outline"
          onClick={handleTestConnection}
          loading={loading}
        >
          Kiểm tra kết nối
        </Button>
      </div>

      {error && (
        <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-md">
          {error}
        </div>
      )}

      <div className="bg-white shadow rounded-lg p-6">
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <Input
              label="API Key"
              name="api_key"
              type="password"
              value={formData.api_key}
              onChange={handleChange}
              error={errors.api_key}
              required
            />
          </div>

          <div>
            <Select
              label="Model"
              name="model"
              value={formData.model}
              onChange={handleChange}
              error={errors.model}
              options={modelOptions}
              required
            />
          </div>

          <div>
            <Input
              label="Temperature"
              name="temperature"
              type="number"
              min="0"
              max="2"
              step="0.1"
              value={formData.temperature}
              onChange={handleChange}
              error={errors.temperature}
              required
            />
          </div>

          <div>
            <Input
              label="Max Tokens"
              name="max_tokens"
              type="number"
              min="1"
              max="4000"
              value={formData.max_tokens}
              onChange={handleChange}
              error={errors.max_tokens}
              required
            />
          </div>

          <div>
            <Input
              label="Top P"
              name="top_p"
              type="number"
              min="0"
              max="1"
              step="0.1"
              value={formData.top_p}
              onChange={handleChange}
              error={errors.top_p}
              required
            />
          </div>

          <div>
            <Input
              label="Frequency Penalty"
              name="frequency_penalty"
              type="number"
              min="-2"
              max="2"
              step="0.1"
              value={formData.frequency_penalty}
              onChange={handleChange}
              error={errors.frequency_penalty}
              required
            />
          </div>

          <div>
            <Input
              label="Presence Penalty"
              name="presence_penalty"
              type="number"
              min="-2"
              max="2"
              step="0.1"
              value={formData.presence_penalty}
              onChange={handleChange}
              error={errors.presence_penalty}
              required
            />
          </div>

          <div>
            <TextArea
              label="System Prompt"
              name="system_prompt"
              value={formData.system_prompt}
              onChange={handleChange}
              error={errors.system_prompt}
              rows={4}
            />
          </div>

          <div>
            <TextArea
              label="User Prompt"
              name="user_prompt"
              value={formData.user_prompt}
              onChange={handleChange}
              error={errors.user_prompt}
              rows={4}
            />
          </div>

          <div className="flex justify-end">
            <Button
              type="submit"
              loading={saving}
            >
              Lưu cài đặt
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AISettings; 