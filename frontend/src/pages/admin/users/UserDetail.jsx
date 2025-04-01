import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Button, Input, Select } from '../../../components/ui';
import { userService } from '../../../services';
import errorService from '../../../services/errorService';
import { toast } from 'react-toastify';

const UserDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    role: 'user',
    status: 'active'
  });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (id !== 'new') {
      loadUser();
    } else {
      setLoading(false);
    }
  }, [id]);

  const loadUser = async () => {
    try {
      setLoading(true);
      const data = await userService.getUser(id);
      setUser(data);
      setFormData({
        name: data.name,
        email: data.email,
        role: data.role,
        status: data.status
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
        await userService.createUser(formData);
        toast.success('Người dùng đã được tạo');
      } else {
        await userService.updateUser(id, formData);
        toast.success('Người dùng đã được cập nhật');
      }
      navigate('/admin/users');
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
          {id === 'new' ? 'Thêm người dùng mới' : 'Chỉnh sửa người dùng'}
        </h1>
        <Button
          variant="outline"
          onClick={() => navigate('/admin/users')}
        >
          Quay lại
        </Button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium text-gray-700">
            Họ tên
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
            Email
          </label>
          <Input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            error={errors.email}
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">
            Vai trò
          </label>
          <Select
            name="role"
            value={formData.role}
            onChange={handleChange}
            error={errors.role}
            required
          >
            <option value="user">Người dùng</option>
            <option value="admin">Quản trị viên</option>
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
            required
          >
            <option value="active">Hoạt động</option>
            <option value="inactive">Đã khóa</option>
          </Select>
        </div>

        {errors.submit && (
          <div className="text-red-500 text-sm">{errors.submit}</div>
        )}

        <div className="flex justify-end space-x-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => navigate('/admin/users')}
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

      {user && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-4">Thông tin bổ sung</h2>
          <div className="bg-gray-50 p-4 rounded-lg">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-500">Ngày tạo</p>
                <p className="mt-1">{new Date(user.created_at).toLocaleDateString()}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Cập nhật lúc</p>
                <p className="mt-1">{new Date(user.updated_at).toLocaleDateString()}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Số bài viết</p>
                <p className="mt-1">{user.articles_count || 0}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Số bài viết đã duyệt</p>
                <p className="mt-1">{user.approved_articles_count || 0}</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default UserDetail; 