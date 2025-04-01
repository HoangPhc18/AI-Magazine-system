import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button, Input, TextArea, Select } from '../../../components/ui';
import { articleService, categoryService } from '../../../services';
import errorService from '../../../services/errorService';
import { formatDate } from '../../../services/dateService';
import { toast } from 'react-toastify';

const RewrittenArticleDetail = () => {
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
    status: 'pending'
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
      const data = await articleService.getRewrittenArticle(id);
      setArticle(data);
      setFormData({
        title: data.title,
        content: data.content,
        category_id: data.category_id,
        status: data.status
      });
    } catch (error) {
      setError(errorService.handleApiError(error).message);
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
    try {
      await articleService.updateRewrittenArticle(id, formData);
      toast.success('Bài viết đã được cập nhật');
      navigate('/admin/articles/rewritten');
    } catch (error) {
      const errorMessage = errorService.handleApiError(error).message;
      setErrors({ submit: errorMessage });
      toast.error(errorMessage);
    } finally {
      setSaving(false);
    }
  };

  const handleApprove = async () => {
    try {
      await articleService.approveRewrittenArticle(id);
      toast.success('Bài viết đã được duyệt');
      navigate('/admin/articles/rewritten');
    } catch (error) {
      toast.error('Không thể duyệt bài viết');
    }
  };

  const handleReject = async () => {
    try {
      await articleService.rejectRewrittenArticle(id);
      toast.success('Bài viết đã bị từ chối');
      navigate('/admin/articles/rewritten');
    } catch (error) {
      toast.error('Không thể từ chối bài viết');
    }
  };

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;
  if (!article) return <div>Không tìm thấy bài viết</div>;

  return (
    <div className="max-w-4xl mx-auto p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Chi tiết bài viết đã viết lại</h1>
        <div className="flex space-x-2">
          {article.status === 'pending' && (
            <>
              <Button
                variant="success"
                onClick={handleApprove}
              >
                Duyệt
              </Button>
              <Button
                variant="danger"
                onClick={handleReject}
              >
                Từ chối
              </Button>
            </>
          )}
          <Button
            variant="outline"
            onClick={() => navigate('/admin/articles/rewritten')}
          >
            Quay lại
          </Button>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            Tiêu đề
          </label>
          <Input
            name="title"
            value={formData.title}
            onChange={handleChange}
            error={errors.title}
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Nội dung
          </label>
          <TextArea
            name="content"
            value={formData.content}
            onChange={handleChange}
            rows={10}
            error={errors.content}
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Danh mục
          </label>
          <Select
            name="category_id"
            value={formData.category_id}
            onChange={handleChange}
            error={errors.category_id}
          >
            <option value="">Chọn danh mục</option>
            {categories.map(category => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </Select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Trạng thái
          </label>
          <Select
            name="status"
            value={formData.status}
            onChange={handleChange}
            error={errors.status}
          >
            <option value="pending">Chờ duyệt</option>
            <option value="approved">Đã duyệt</option>
            <option value="rejected">Từ chối</option>
          </Select>
        </div>

        {errors.submit && (
          <div className="text-red-500 text-sm">{errors.submit}</div>
        )}

        <div className="flex justify-end space-x-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => navigate('/admin/articles/rewritten')}
          >
            Hủy
          </Button>
          <Button
            type="submit"
            disabled={saving}
          >
            {saving ? 'Đang lưu...' : 'Lưu thay đổi'}
          </Button>
        </div>
      </form>

      <div className="mt-8">
        <h2 className="text-xl font-semibold mb-4">Bài viết gốc</h2>
        <div className="bg-gray-50 p-4 rounded-lg">
          <h3 className="font-medium">{article.original_article?.title}</h3>
          <p className="text-gray-600 mt-2">{article.original_article?.content}</p>
        </div>
      </div>
    </div>
  );
};

export default RewrittenArticleDetail; 