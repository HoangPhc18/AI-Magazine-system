import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { articleService } from '../services/articleService';
import { categoryService } from '../services/categoryService';
import toast from 'react-hot-toast';

const Articles = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [articles, setArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState('latest');
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [articlesRes, categoriesData] = await Promise.all([
          articleService.getAll({
            page: searchParams.get('page') || 1,
            category: selectedCategory,
            search: searchQuery,
            sort: sortBy
          }),
          categoryService.getAll()
        ]);
        setArticles(articlesRes.data);
        setCategories(categoriesData);
        setPagination(articlesRes.meta);
      } catch (error) {
        console.error('Error fetching data:', error);
        toast.error('Có lỗi xảy ra khi tải dữ liệu');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [searchParams, selectedCategory, searchQuery, sortBy]);

  const handlePageChange = (page) => {
    setSearchParams(prev => {
      prev.set('page', page);
      return prev;
    });
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setSearchParams(prev => {
      prev.delete('page'); // Reset to first page when filter changes
      if (value) {
        prev.set(name, value);
      } else {
        prev.delete(name);
      }
      return prev;
    });
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row justify-between items-center mb-8 space-y-4 sm:space-y-0">
        <h1 className="text-3xl font-bold text-gray-900">Bài viết</h1>
        <div className="flex space-x-4">
          <input
            type="text"
            name="search"
            placeholder="Tìm kiếm bài viết..."
            value={searchQuery}
            onChange={handleFilterChange}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
          <select
            name="category"
            value={selectedCategory}
            onChange={handleFilterChange}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">Tất cả danh mục</option>
            {categories.map(category => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
          <select
            name="sort"
            value={sortBy}
            onChange={handleFilterChange}
            className="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="latest">Mới nhất</option>
            <option value="oldest">Cũ nhất</option>
            <option value="title">Theo tên</option>
          </select>
        </div>
      </div>

      {articles.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500 text-lg">Không tìm thấy bài viết nào</p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {articles.map(article => (
              <Link
                key={article.id}
                to={`/articles/${article.id}`}
                className="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300"
              >
                <div className="aspect-w-16 aspect-h-9">
                  <img
                    src={article.cover_image}
                    alt={article.title}
                    className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300"
                  />
                </div>
                <div className="p-4">
                  <h2 className="text-xl font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300">
                    {article.title}
                  </h2>
                  <p className="mt-2 text-gray-600 line-clamp-3">
                    {article.summary}
                  </p>
                  <div className="mt-4 flex items-center justify-between text-sm text-gray-500">
                    <span>{article.author_name}</span>
                    <span>{new Date(article.created_at).toLocaleDateString('vi-VN')}</span>
                  </div>
                </div>
              </Link>
            ))}
          </div>

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="mt-8 flex justify-center">
              <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                {/* Previous Page */}
                <button
                  onClick={() => handlePageChange(pagination.current_page - 1)}
                  disabled={pagination.current_page === 1}
                  className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span className="sr-only">Trang trước</span>
                  <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clipRule="evenodd" />
                  </svg>
                </button>

                {/* Page Numbers */}
                {[...Array(pagination.last_page)].map((_, index) => (
                  <button
                    key={index + 1}
                    onClick={() => handlePageChange(index + 1)}
                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                      pagination.current_page === index + 1
                        ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                    }`}
                  >
                    {index + 1}
                  </button>
                ))}

                {/* Next Page */}
                <button
                  onClick={() => handlePageChange(pagination.current_page + 1)}
                  disabled={pagination.current_page === pagination.last_page}
                  className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span className="sr-only">Trang sau</span>
                  <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default Articles; 