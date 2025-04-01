import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Table, Search, Pagination } from '../../../components/ui';
import { categoryService } from '../../../services';
import errorService from '../../../services/errorService';
import { toast } from 'react-toastify';

const CategoryList = () => {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const columns = [
    {
      header: 'ID',
      key: 'id'
    },
    {
      header: 'Tên danh mục',
      key: 'name'
    },
    {
      header: 'Slug',
      key: 'slug'
    },
    {
      header: 'Mô tả',
      key: 'description'
    },
    {
      header: 'Số bài viết',
      key: 'articles_count',
      render: (row) => row.articles_count || 0
    },
    {
      header: 'Thao tác',
      key: 'actions',
      render: (row) => (
        <div className="flex space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => navigate(`/admin/categories/${row.id}`)}
          >
            Chi tiết
          </Button>
          <Button
            variant="danger"
            size="sm"
            onClick={() => handleDelete(row.id)}
          >
            Xóa
          </Button>
        </div>
      )
    }
  ];

  const loadCategories = async () => {
    try {
      setLoading(true);
      const response = await categoryService.getCategories();
      setCategories(response.data || []);
      setError(null);
    } catch (err) {
      setError(errorService.handleApiError(err));
      toast.error('Không thể tải danh sách danh mục');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
      try {
        await categoryService.deleteCategory(id);
        toast.success('Danh mục đã được xóa');
        loadCategories();
      } catch (err) {
        toast.error('Không thể xóa danh mục');
      }
    }
  };

  useEffect(() => {
    loadCategories();
  }, []);

  const filteredCategories = categories.filter(category =>
    category.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    category.slug.toLowerCase().includes(searchTerm.toLowerCase()) ||
    category.description?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Quản lý danh mục</h1>
        <div className="flex space-x-4">
          <Search
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Tìm kiếm danh mục..."
          />
          <Button
            onClick={() => navigate('/admin/categories/new')}
          >
            Thêm danh mục
          </Button>
        </div>
      </div>

      <Table
        columns={columns}
        data={filteredCategories}
        emptyMessage="Không có danh mục nào"
      />

      <div className="flex justify-center">
        <Pagination
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={setCurrentPage}
        />
      </div>
    </div>
  );
};

export default CategoryList; 