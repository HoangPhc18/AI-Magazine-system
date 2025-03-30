import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Articles = () => {
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedArticle, setSelectedArticle] = useState(null);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editContent, setEditContent] = useState('');

  useEffect(() => {
    fetchArticles();
  }, []);

  const fetchArticles = async () => {
    try {
      const response = await axios.get('/api/admin/articles/rewritten');
      setArticles(response.data.data);
    } catch (error) {
      console.error('Error fetching articles:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (articleId) => {
    try {
      await axios.post(`/api/admin/articles/rewritten/${articleId}/approve`, {
        status: 'approved',
      });
      fetchArticles();
    } catch (error) {
      console.error('Error approving article:', error);
    }
  };

  const handleReject = async (articleId) => {
    try {
      await axios.post(`/api/admin/articles/rewritten/${articleId}/approve`, {
        status: 'rejected',
      });
      fetchArticles();
    } catch (error) {
      console.error('Error rejecting article:', error);
    }
  };

  const handleEdit = (article) => {
    setSelectedArticle(article);
    setEditContent(article.rewritten_content);
    setIsEditModalOpen(true);
  };

  const handleSaveEdit = async () => {
    try {
      await axios.put(`/api/admin/articles/rewritten/${selectedArticle.id}`, {
        rewritten_content: editContent,
      });
      setIsEditModalOpen(false);
      fetchArticles();
    } catch (error) {
      console.error('Error updating article:', error);
    }
  };

  const handleDelete = async (articleId) => {
    if (window.confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
      try {
        await axios.delete(`/api/admin/articles/rewritten/${articleId}`);
        fetchArticles();
      } catch (error) {
        console.error('Error deleting article:', error);
      }
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Quản lý bài viết</h1>
        <button className="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
          Thêm bài viết mới
        </button>
      </div>

      {/* Articles Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tiêu đề
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Trạng thái
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ngày tạo
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {articles.map((article) => (
              <tr key={article.id}>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm font-medium text-gray-900">
                    {article.originalArticle.title}
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span
                    className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                      article.status === 'approved'
                        ? 'bg-green-100 text-green-800'
                        : article.status === 'rejected'
                        ? 'bg-red-100 text-red-800'
                        : 'bg-yellow-100 text-yellow-800'
                    }`}
                  >
                    {article.status === 'approved'
                      ? 'Đã duyệt'
                      : article.status === 'rejected'
                      ? 'Từ chối'
                      : 'Chờ duyệt'}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {new Date(article.created_at).toLocaleDateString()}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    onClick={() => handleEdit(article)}
                    className="text-blue-600 hover:text-blue-900 mr-4"
                  >
                    Sửa
                  </button>
                  {article.status === 'pending' && (
                    <>
                      <button
                        onClick={() => handleApprove(article.id)}
                        className="text-green-600 hover:text-green-900 mr-4"
                      >
                        Duyệt
                      </button>
                      <button
                        onClick={() => handleReject(article.id)}
                        className="text-red-600 hover:text-red-900 mr-4"
                      >
                        Từ chối
                      </button>
                    </>
                  )}
                  <button
                    onClick={() => handleDelete(article.id)}
                    className="text-red-600 hover:text-red-900"
                  >
                    Xóa
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Edit Modal */}
      {isEditModalOpen && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
          <div className="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
            <div className="mt-3">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                Chỉnh sửa bài viết
              </h3>
              <textarea
                value={editContent}
                onChange={(e) => setEditContent(e.target.value)}
                className="w-full h-96 p-4 border rounded-lg"
              />
            </div>
            <div className="mt-4 flex justify-end space-x-3">
              <button
                onClick={() => setIsEditModalOpen(false)}
                className="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
              >
                Hủy
              </button>
              <button
                onClick={handleSaveEdit}
                className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
              >
                Lưu
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Articles; 