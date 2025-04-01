import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../components/ui';
import { dashboardService } from '../../services';
import errorService from '../../services/errorService';
import { toast } from 'react-toastify';

const Dashboard = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [stats, setStats] = useState({
    totalUsers: 0,
    totalArticles: 0,
    pendingArticles: 0,
    approvedArticles: 0,
    totalCategories: 0,
    recentArticles: [],
    recentUsers: []
  });

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      setLoading(true);
      const data = await dashboardService.getStats();
      setStats(data);
    } catch (error) {
      setError(errorService.handleApiError(error));
      toast.error(errorService.handleApiError(error));
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div className="text-red-500">{error}</div>;

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Tổng quan</h1>
        <Button onClick={loadStats}>
          Làm mới
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-500 text-sm font-medium">Tổng số người dùng</h3>
          <p className="text-3xl font-bold mt-2">{stats.totalUsers}</p>
          <Link to="/admin/users" className="text-blue-600 text-sm mt-2 inline-block">
            Xem chi tiết →
          </Link>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-500 text-sm font-medium">Tổng số bài viết</h3>
          <p className="text-3xl font-bold mt-2">{stats.totalArticles}</p>
          <Link to="/admin/articles" className="text-blue-600 text-sm mt-2 inline-block">
            Xem chi tiết →
          </Link>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-500 text-sm font-medium">Bài viết chờ duyệt</h3>
          <p className="text-3xl font-bold mt-2">{stats.pending_articles}</p>
          <Link to="/admin/articles/pending" className="text-blue-600 text-sm mt-2 inline-block">
            Xem chi tiết →
          </Link>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-gray-500 text-sm font-medium">Bài viết đã duyệt</h3>
          <p className="text-3xl font-bold mt-2">{stats.approved_articles}</p>
          <Link to="/admin/articles/approved" className="text-blue-600 text-sm mt-2 inline-block">
            Xem chi tiết →
          </Link>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow">
          <div className="p-6 border-b">
            <h2 className="text-lg font-semibold">Bài viết gần đây</h2>
          </div>
          <div className="p-6">
            {stats.recent_articles.length > 0 ? (
              <div className="space-y-4">
                {stats.recent_articles.map(article => (
                  <div key={article.id} className="flex items-center justify-between">
                    <div>
                      <h3 className="font-medium">{article.title}</h3>
                      <p className="text-sm text-gray-500">
                        {new Date(article.created_at).toLocaleDateString()}
                      </p>
                    </div>
                    <Link
                      to={`/admin/articles/${article.id}`}
                      className="text-blue-600 text-sm"
                    >
                      Chi tiết →
                    </Link>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-gray-500">Không có bài viết nào</p>
            )}
          </div>
        </div>

        <div className="bg-white rounded-lg shadow">
          <div className="p-6 border-b">
            <h2 className="text-lg font-semibold">Người dùng mới</h2>
          </div>
          <div className="p-6">
            {stats.recent_users.length > 0 ? (
              <div className="space-y-4">
                {stats.recent_users.map(user => (
                  <div key={user.id} className="flex items-center justify-between">
                    <div>
                      <h3 className="font-medium">{user.name}</h3>
                      <p className="text-sm text-gray-500">{user.email}</p>
                    </div>
                    <Link
                      to={`/admin/users/${user.id}`}
                      className="text-blue-600 text-sm"
                    >
                      Chi tiết →
                    </Link>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-gray-500">Không có người dùng mới</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard; 