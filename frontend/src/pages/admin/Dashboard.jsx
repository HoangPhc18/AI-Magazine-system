import React, { useState, useEffect } from 'react';
import api from '../../services/api';

const Dashboard = () => {
  const [stats, setStats] = useState({
    totalArticles: 0,
    pendingArticles: 0,
    totalUsers: 0,
    categories: [],
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const [articlesRes, usersRes, categoriesRes] = await Promise.all([
        api.get('/admin/articles/rewritten'),
        api.get('/admin/users'),
        api.get('/admin/categories'),
      ]);

      const articles = articlesRes.data.data;
      const pendingArticles = articles.filter(
        (article) => article.status === 'pending'
      );

      setStats({
        totalArticles: articles.length,
        pendingArticles: pendingArticles.length,
        totalUsers: usersRes.data.data.length,
        categories: categoriesRes.data.data,
      });
    } catch (error) {
      console.error('Error fetching stats:', error);
    } finally {
      setLoading(false);
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
      <h1 className="text-2xl font-bold mb-6">Dashboard</h1>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold mb-2">Total Articles</h2>
          <p className="text-3xl font-bold text-blue-600">{stats.totalArticles}</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold mb-2">Pending Articles</h2>
          <p className="text-3xl font-bold text-yellow-600">{stats.pendingArticles}</p>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold mb-2">Total Users</h2>
          <p className="text-3xl font-bold text-green-600">{stats.totalUsers}</p>
        </div>
      </div>
      <div className="mt-8">
        <h2 className="text-xl font-semibold mb-4">Categories</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {stats.categories.map((category) => (
            <div key={category.id} className="bg-white p-4 rounded-lg shadow">
              <h3 className="font-semibold">{category.name}</h3>
              <p className="text-gray-600">{category.description}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Dashboard; 