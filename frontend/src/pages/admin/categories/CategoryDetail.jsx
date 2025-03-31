import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button, Input, TextArea } from '../../../components/ui';
import { categoryService } from '../../../services';
import { handleApiError } from '../../../services/errorService';
import { formatDate } from '../../../services/dateService';

const CategoryDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [category, setCategory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    slug: ''
  });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    loadCategory();
  }, [id]);

  const loadCategory = async () => {
    try {
      setLoading(true);
      const data = await categoryService.getCategory(id);
      setCategory(data);
      setFormData({
        name: data.name,
        description: data.description,
        slug: data.slug
      });
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
      await categoryService.updateCategory(id, formData);
      navigate('/admin/categories');
    } catch (error) {
      const errorData = handleApiError(error);
      setErrors(errorData.errors || {});
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div>Đang tải...</div>;
  }

  if (error) {
    return <div className="text-red-600">{error}</div>;
  }

  if (!category) {
    return <div>Không tìm thấy danh mục</div>;
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Chi tiết danh mục</h1>
        <Button
          variant="outline"
          onClick={() => navigate('/admin/categories')}
        >
          Quay lại
        </Button>
      </div>

      <div className="bg-white shadow rounded-lg p-6">
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <Input
              label="Tên danh mục"
              name="name"
              value={formData.name}
              onChange={handleChange}
              error={errors.name}
              required
            />
          </div>

          <div>
            <Input
              label="Slug"
              name="slug"
              value={formData.slug}
              onChange={handleChange}
              error={errors.slug}
              required
            />
          </div>

          <div>
            <TextArea
              label="Mô tả"
              name="description"
              value={formData.description}
              onChange={handleChange}
              error={errors.description}
              rows={4}
            />
          </div>

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
              loading={saving}
            >
              Lưu thay đổi
            </Button>
          </div>
        </form>

        <div className="mt-8 pt-8 border-t">
          <h2 className="text-lg font-medium text-gray-900 mb-4">Thông tin bổ sung</h2>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-500">Số bài viết</p>
              <p className="mt-1">{category.articles_count || 0}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Ngày tạo</p>
              <p className="mt-1">{formatDate(category.created_at)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Cập nhật lúc</p>
              <p className="mt-1">{formatDate(category.updated_at)}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CategoryDetail; 