import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button, Table, Search, Pagination } from '../../../components/ui';
import { articleService, dateService } from '../../../services';
import errorService from '../../../services/errorService';
import { toast } from 'react-toastify';

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
      header: 'Bài viết gốc',
      key: 'original_article',
      render: (row) => row.original_article?.title || 'N/A'
    },
    {
      header: 'Trạng thái',
      key: 'status',
      render: (row) => (
        <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
          row.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
          row.status === 'approved' ? 'bg-green-100 text-green-800' :
          row.status === 'rejected' ? 'bg-red-100 text-red-800' :
          'bg-gray-100 text-gray-800'
        }`}>
          {row.status}
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
          {row.status === 'pending' && (
            <>
              <Button
                variant="success"
                size="sm"
                onClick={() => handleApprove(row.id)}
              >
                Duyệt
              </Button>
              <Button
                variant="danger"
                size="sm"
                onClick={() => handleReject(row.id)}
              >
                Từ chối
              </Button>
            </>
          )}
        </div>
      )
    }
  ];

  const loadArticles = async () => {
    try {
      setLoading(true);
      const response = await articleService.getRewrittenArticles();
      setArticles(response.data || []);
      setError(null);
    } catch (err) {
      setError(errorService.handleApiError(err));
      toast.error('Không thể tải danh sách bài viết');
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (id) => {
    try {
      await articleService.approveRewrittenArticle(id);
      toast.success('Bài viết đã được duyệt');
      loadArticles();
    } catch (err) {
      toast.error('Không thể duyệt bài viết');
    }
  };

  const handleReject = async (id) => {
    try {
      await articleService.rejectRewrittenArticle(id);
      toast.success('Bài viết đã bị từ chối');
      loadArticles();
    } catch (err) {
      toast.error('Không thể từ chối bài viết');
    }
  };

  useEffect(() => {
    loadArticles();
  }, []);

  const filteredArticles = articles.filter(article =>
    article.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    article.category?.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    article.original_article?.title.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Bài viết đã viết lại</h1>
        <Search
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          placeholder="Tìm kiếm bài viết..."
        />
      </div>

      <Table
        columns={columns}
        data={filteredArticles}
        emptyMessage="Không có bài viết nào đã viết lại"
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

export default RewrittenArticleList; 