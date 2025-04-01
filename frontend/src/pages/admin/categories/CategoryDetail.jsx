import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button, Input, TextArea } from '../../../components/ui';
import { categoryService } from '../../../services';
import errorService from '../../../services/errorService';
import { toast } from 'react-toastify';

const CategoryDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [category, setCategory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    description: ''
  });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (id !== 'new') {
      loadCategory();
    } else {
      setLoading(false);
    }
  }, [id]);

  const loadCategory = async () => {
    try {
      setLoading(true);
      const data = await categoryService.getCategory(id);
      setCategory(data);
      setFormData({
        name: data.name,
        slug: data.slug,
        description: data.description
      });
    } catch (error) {
      setError(errorService.handleApiError(error).message);
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
      if (id === 'new') {
        await categoryService.createCategory(formData);
        toast.success('Danh mục đã được tạo');
      } else {
        await categoryService.updateCategory(id, formData);
        toast.success('Danh mục đã được cập nhật');
      }
      navigate('/admin/categories');
    } catch (error) {
      const errorMessage = errorService.handleApiError(error).message;
      setErrors({ submit: errorMessage });
      toast.error(errorMessage);
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="max-w-2xl mx-auto p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">
          {id === 'new' ? 'Thêm danh mục mới' : 'Chỉnh sửa danh mục'}
        </h1>
        <Button
          variant="outline"
          onClick={() => navigate('/admin/categories')}
        >
          Quay lại
        </Button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            Tên danh mục
          </label>
          <Input
            name="name"
            value={formData.name}
            onChange={handleChange}
            error={errors.name}
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Slug
          </label>
          <Input
            name="slug"
            value={formData.slug}
            onChange={handleChange}
            error={errors.slug}
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Mô tả
          </label>
          <TextArea
            name="description"
            value={formData.description}
            onChange={handleChange}
            rows={4}
            error={errors.description}
          />
        </div>

        {errors.submit && (
          <div className="text-red-500 text-sm">{errors.submit}</div>
        )}

        <div className="flex justify-end space-x-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => navigate('/admin/categories')}
          >
            Hủy
          </Button>
          <Button
            type="submit"
            disabled={saving}
          >
            {saving ? 'Đang lưu...' : id === 'new' ? 'Tạo mới' : 'Lưu thay đổi'}
          </Button>
        </div>
      </form>

      {category && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-4">Thông tin bổ sung</h2>
          <div className="bg-gray-50 p-4 rounded-lg">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-500">Số bài viết</p>
                <p className="mt-1">{category.articles_count || 0}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Ngày tạo</p>
                <p className="mt-1">{new Date(category.created_at).toLocaleDateString()}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Cập nhật lúc</p>
                <p className="mt-1">{new Date(category.updated_at).toLocaleDateString()}</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CategoryDetail; 