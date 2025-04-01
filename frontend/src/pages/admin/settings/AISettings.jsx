import React, { useState, useEffect } from 'react';
import { Button, Input, TextArea } from '../../../components/ui';
import { aiService } from '../../../services';
import { handleApiError } from '../../../services/errorService';
import { toast } from 'react-toastify';

const AISettings = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    api_key: '',
    model: 'gpt-3.5-turbo',
    temperature: 0.7,
    max_tokens: 2000,
    prompt_template: ''
  });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);

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
    try {
      await aiService.updateSettings(formData);
      toast.success('Cài đặt AI đã được cập nhật');
    } catch (error) {
      const errorMessage = handleApiError(error).message;
      setErrors({ submit: errorMessage });
      toast.error(errorMessage);
    } finally {
      setSaving(false);
    }
  };

  const handleTestConnection = async () => {
    setTesting(true);
    try {
      await aiService.testConnection();
      toast.success('Kết nối AI thành công');
    } catch (error) {
      toast.error(handleApiError(error).message);
    } finally {
      setTesting(false);
    }
  };

  const handleResetSettings = async () => {
    if (!window.confirm('Bạn có chắc chắn muốn đặt lại cài đặt AI?')) {
      return;
    }

    try {
      await aiService.resetSettings();
      toast.success('Cài đặt AI đã được đặt lại');
      loadSettings();
    } catch (error) {
      toast.error(handleApiError(error).message);
    }
  };

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="max-w-2xl mx-auto p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Cài đặt AI</h1>
        <div className="flex space-x-2">
          <Button
            variant="outline"
            onClick={handleTestConnection}
            disabled={testing}
          >
            {testing ? 'Đang kiểm tra...' : 'Kiểm tra kết nối'}
          </Button>
          <Button
            variant="danger"
            onClick={handleResetSettings}
          >
            Đặt lại cài đặt
          </Button>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            API Key
          </label>
          <Input
            type="password"
            name="api_key"
            value={formData.api_key}
            onChange={handleChange}
            error={errors.api_key}
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Model
          </label>
          <Input
            name="model"
            value={formData.model}
            onChange={handleChange}
            error={errors.model}
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Temperature
          </label>
          <Input
            type="number"
            name="temperature"
            value={formData.temperature}
            onChange={handleChange}
            error={errors.temperature}
            min="0"
            max="1"
            step="0.1"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Max Tokens
          </label>
          <Input
            type="number"
            name="max_tokens"
            value={formData.max_tokens}
            onChange={handleChange}
            error={errors.max_tokens}
            min="1"
            max="4000"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Prompt Template
          </label>
          <TextArea
            name="prompt_template"
            value={formData.prompt_template}
            onChange={handleChange}
            rows={6}
            error={errors.prompt_template}
            placeholder="Nhập template cho prompt AI..."
          />
        </div>

        {errors.submit && (
          <div className="text-red-500 text-sm">{errors.submit}</div>
        )}

        <div className="flex justify-end">
          <Button
            type="submit"
            disabled={saving}
          >
            {saving ? 'Đang lưu...' : 'Lưu cài đặt'}
          </Button>
        </div>
      </form>

      <div className="mt-8">
        <h2 className="text-xl font-semibold mb-4">Thông tin về cài đặt</h2>
        <div className="bg-gray-50 p-4 rounded-lg space-y-4">
          <div>
            <h3 className="font-medium mb-2">Temperature</h3>
            <p className="text-sm text-gray-600">
              Điều chỉnh độ sáng tạo của AI. Giá trị từ 0 đến 1, trong đó:
            </p>
            <ul className="list-disc list-inside text-sm text-gray-600 mt-1">
              <li>0: Câu trả lời chính xác và nhất quán</li>
              <li>1: Câu trả lời sáng tạo và đa dạng</li>
            </ul>
          </div>

          <div>
            <h3 className="font-medium mb-2">Max Tokens</h3>
            <p className="text-sm text-gray-600">
              Giới hạn độ dài của câu trả lời. Mỗi token tương đương với khoảng 4 ký tự.
            </p>
          </div>

          <div>
            <h3 className="font-medium mb-2">Prompt Template</h3>
            <p className="text-sm text-gray-600">
              Template mẫu cho prompt AI. Có thể sử dụng các biến:
            </p>
            <ul className="list-disc list-inside text-sm text-gray-600 mt-1">
              <li>{'{title}'}: Tiêu đề bài viết</li>
              <li>{'{content}'}: Nội dung bài viết</li>
              <li>{'{category}'}: Danh mục bài viết</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AISettings; 