import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Button, Table, Search, Pagination } from '../../../components/ui';
import { userService } from '../../../services';
import errorService from '../../../services/errorService';
import { toast } from 'react-toastify';

const UserList = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    loadUsers();
  }, [currentPage]);

  const loadUsers = async () => {
    try {
      setLoading(true);
      const response = await userService.getUsers(currentPage);
      setUsers(response.data);
      setTotalPages(response.last_page);
    } catch (error) {
      setError(errorService.handleApiError(error));
      toast.error(errorService.handleApiError(error));
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (userId) => {
    if (window.confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
      try {
        await userService.deleteUser(userId);
        toast.success('Xóa người dùng thành công');
        loadUsers();
      } catch (error) {
        toast.error(errorService.handleApiError(error));
      }
    }
  };

  const columns = [
    {
      header: 'ID',
      key: 'id'
    },
    {
      header: 'Tên',
      key: 'name'
    },
    {
      header: 'Email',
      key: 'email'
    },
    {
      header: 'Vai trò',
      key: 'role',
      render: (user) => (
        <span className={`px-2 py-1 rounded-full text-xs ${
          user.role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
        }`}>
          {user.role === 'admin' ? 'Admin' : 'User'}
        </span>
      )
    },
    {
      header: 'Trạng thái',
      key: 'status',
      render: (user) => (
        <span className={`px-2 py-1 rounded-full text-xs ${
          user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`}>
          {user.status === 'active' ? 'Hoạt động' : 'Đã khóa'}
        </span>
      )
    },
    {
      header: 'Thao tác',
      render: (user) => (
        <div className="flex space-x-2">
          <Link
            to={`/admin/users/${user.id}`}
            className="text-blue-600 hover:text-blue-800"
          >
            Chi tiết
          </Link>
          <button
            onClick={() => handleDelete(user.id)}
            className="text-red-600 hover:text-red-800"
          >
            Xóa
          </button>
        </div>
      )
    }
  ];

  const filteredUsers = users.filter(user =>
    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Quản lý người dùng</h1>
        <Link to="/admin/users/new">
          <Button>Thêm người dùng</Button>
        </Link>
      </div>

      <div className="mb-6">
        <Search
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          placeholder="Tìm kiếm người dùng..."
        />
      </div>

      <Table
        columns={columns}
        data={filteredUsers}
        emptyMessage="Không tìm thấy người dùng nào"
      />

      <div className="mt-6 flex justify-center">
        <Pagination
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={setCurrentPage}
        />
      </div>
    </div>
  );
};

export default UserList; 