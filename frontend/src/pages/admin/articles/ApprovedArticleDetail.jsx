import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button, Input, TextArea, Select } from '../../../components/ui';
import { articleService, categoryService } from '../../../services';
import { handleApiError } from '../../../services/errorService';
import { formatDate } from '../../../services/dateService';

const ApprovedArticleDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [article, setArticle] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [categories, setCategories] = useState([]);
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    category_id: '',
    status: 'published'
  });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    loadArticle();
    loadCategories();
  }, [id]);

  const loadArticle = async () => {
    try {
      setLoading(true);
      const data = await articleService.getApprovedArticle(id);
      setArticle(data);
      setFormData({
        title: data.title,
        content: data.content,
        category_id: data.category_id,
        status: data.status
      });
    } catch (error) {
      setError(handleApiError(error).message);
    } finally {
      setLoading(false);
    }
  };

  const loadCategories = async () => {
    try {
      const response = await categoryService.getCategories();
      setCategories(response.data);
    } catch (error) {
      console.error('Error loading categories:', error);
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
      await articleService.updateApprovedArticle(id, formData);
      navigate('/admin/articles/approved');
    } catch (error) {
      const errorData = handleApiError(error);
      setErrors(errorData.errors || {});
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async () => {
    try {
      const newStatus = article.status === 'published' ? 'draft' : 'published';
      await articleService.updateArticleStatus(id, newStatus);
      loadArticle();
    } catch (error) {
      setError(handleApiError(error).message);
    }
  };

  if (loading) {
    return <div>Đang tải...</div>;
  }

  if (error) {
    return <div className="text-red-600">{error}</div>;
  }

  if (!article) {
    return <div>Không tìm thấy bài viết</div>;
  }

  const categoryOptions = categories.map(category => ({
    value: category.id,
    label: category.name
  }));

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Chi tiết bài viết</h1>
        <div className="flex space-x-2">
          <Button
            variant={article.status === 'published' ? 'warning' : 'success'}
            onClick={handleToggleStatus}
          >
            {article.status === 'published' ? 'Chuyển sang nháp' : 'Xuất bản'}
          </Button>
          <Button
            variant="outline"
            onClick={() => navigate('/admin/articles/approved')}
          >
            Quay lại
          </Button>
        </div>
      </div>

      <div className="bg-white shadow rounded-lg p-6">
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <Input
              label="Tiêu đề"
              name="title"
              value={formData.title}
              onChange={handleChange}
              error={errors.title}
              required
            />
          </div>

          <div>
            <Select
              label="Danh mục"
              name="category_id"
              value={formData.category_id}
              onChange={handleChange}
              error={errors.category_id}
              options={categoryOptions}
              required
            />
          </div>

          <div>
            <TextArea
              label="Nội dung"
              name="content"
              value={formData.content}
              onChange={handleChange}
              error={errors.content}
              required
              rows={10}
            />
          </div>

          <div className="flex justify-end space-x-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate('/admin/articles/approved')}
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
              <p className="text-sm text-gray-500">Tác giả</p>
              <p className="mt-1">{article.user?.name || 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Trạng thái</p>
              <p className="mt-1">
                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                  article.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                }`}>
                  {article.status === 'published' ? 'Đã xuất bản' : 'Nháp'}
                </span>
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Lượt xem</p>
              <p className="mt-1">{article.views || 0}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Ngày duyệt</p>
              <p className="mt-1">{formatDate(article.approved_at)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Ngày tạo</p>
              <p className="mt-1">{formatDate(article.created_at)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Cập nhật lúc</p>
              <p className="mt-1">{formatDate(article.updated_at)}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ApprovedArticleDetail; 