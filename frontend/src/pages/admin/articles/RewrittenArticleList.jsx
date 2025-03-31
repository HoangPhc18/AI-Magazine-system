import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Table, Search, Pagination } from '../../../components/common';
import { articleService, dateService } from '../../../services';
import { handleApiError } from '../../../services/errorService';

const RewrittenArticleList = () => {
  const navigate = useNavigate();
  const [articles, setArticles] = useState([]);
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
      header: 'Tiêu đề',
      key: 'title'
    },
    {
      header: 'Danh mục',
      key: 'category',
      render: (row) => row.category?.name || 'Chưa phân loại'
    },
    {
      header: 'Tác giả',
      key: 'author',
      render: (row) => row.user?.name || 'N/A'
    },
    {
      header: 'Trạng thái',
      key: 'status',
      render: (row) => (
        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
          row.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
          row.status === 'approved' ? 'bg-green-100 text-green-800' :
          'bg-red-100 text-red-800'
        }`}>
          {row.status === 'pending' ? 'Chờ duyệt' :
           row.status === 'approved' ? 'Đã duyệt' :
           'Từ chối'}
        </span>
      )
    },
    {
      header: 'Ngày tạo',
      key: 'created_at',
      render: (row) => dateService.formatDate(row.created_at)
    },
    {
      header: 'Thao tác',
      key: 'actions',
      render: (row) => (
        <div className="flex space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => navigate(`/admin/articles/rewritten/${row.id}`)}
          >
            Chi tiết
          </Button>
          <Button
            variant="danger"
            size="sm"
            onClick={() => handleDeleteArticle(row.id)}
          >
            Xóa
          </Button>
        </div>
      )
    }
  ];

  const loadArticles = async () => {
    try {
      setLoading(true);
      const response = await articleService.getRewrittenArticles({
        page: currentPage,
        search: searchTerm
      });
      setArticles(response.data);
      setTotalPages(response.last_page);
    } catch (error) {
      setError(handleApiError(error).message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadArticles();
  }, [currentPage, searchTerm]);

  const handleSearch = (value) => {
    setSearchTerm(value);
    setCurrentPage(1);
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  const handleDeleteArticle = async (id) => {
    if (window.confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
      try {
        await articleService.deleteRewrittenArticle(id);
        loadArticles();
      } catch (error) {
        setError(handleApiError(error).message);
      }
    }
  };

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Quản lý bài viết đã viết lại</h1>
        <Button onClick={() => navigate('/admin/articles/rewritten/create')}>
          Thêm bài viết mới
        </Button>
      </div>

      <div className="mb-6">
        <Search
          value={searchTerm}
          onChange={(e) => handleSearch(e.target.value)}
          placeholder="Tìm kiếm bài viết..."
        />
      </div>

      {error && (
        <div className="mb-4 p-4 bg-red-50 text-red-700 rounded-md">
          {error}
        </div>
      )}

      <Table
        columns={columns}
        data={articles}
        loading={loading}
        emptyMessage="Không có bài viết nào"
      />

      <div className="mt-6">
        <Pagination
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={handlePageChange}
        />
      </div>
    </div>
  );
};

export default RewrittenArticleList; 