import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { userService, errorService } from '../../../services';
import { toast } from 'react-toastify';
import { formatDate } from '../../../services/dateService';
import Button from '../../../components/common/Button';
import Input from '../../../components/common/Input';
import Select from '../../../components/common/Select';

const UserDetail = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    role: 'user',
    is_active: true
  });
  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  const roleOptions = [
    { value: 'user', label: 'Người dùng' },
    { value: 'admin', label: 'Quản trị viên' }
  ];

  useEffect(() => {
    fetchUser();
  }, [id]);

  const fetchUser = async () => {
    try {
      const response = await userService.getById(id);
      const user = response.data;
      setUser(user);
      setFormData({
        name: user.name,
        email: user.email,
        role: user.role,
        is_active: user.is_active
      });
    } catch (err) {
      toast.error(errorService.handleApiError(err));
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
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
      await userService.update(id, formData);
      toast.success('User updated successfully');
      navigate('/admin/users');
    } catch (err) {
      toast.error(errorService.handleApiError(err));
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async () => {
    try {
      await userService.toggleUserStatus(id);
      fetchUser();
    } catch (error) {
      setError(errorService.handleApiError(error));
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-red-500 text-center">
          <p className="text-xl font-semibold mb-2">Error</p>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-gray-500 text-center">
          <p className="text-xl font-semibold mb-2">User not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Chi tiết người dùng</h1>
        <div className="flex space-x-2">
          <Button
            variant={user.is_active ? 'danger' : 'success'}
            onClick={handleToggleStatus}
          >
            {user.is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản'}
          </Button>
          <Button
            variant="outline"
            onClick={() => navigate('/admin/users')}
          >
            Quay lại
          </Button>
        </div>
      </div>

      <div className="bg-white shadow rounded-lg p-6">
        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <Input
              label="Họ tên"
              name="name"
              value={formData.name}
              onChange={handleChange}
              error={errors.name}
              required
            />
          </div>

          <div>
            <Input
              label="Email"
              name="email"
              type="email"
              value={formData.email}
              onChange={handleChange}
              error={errors.email}
              required
            />
          </div>

          <div>
            <Select
              label="Vai trò"
              name="role"
              value={formData.role}
              onChange={handleChange}
              error={errors.role}
              options={roleOptions}
              required
            />
          </div>

          <div className="flex items-center">
            <input
              id="is_active"
              name="is_active"
              type="checkbox"
              checked={formData.is_active}
              onChange={handleChange}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
              Active
            </label>
          </div>

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
              <p className="text-sm text-gray-500">Ngày tạo</p>
              <p className="mt-1">{formatDate(user.created_at)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Cập nhật lúc</p>
              <p className="mt-1">{formatDate(user.updated_at)}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UserDetail; 