import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { categoryService } from '../services/categoryService';
import { articleService } from '../services/articleService';
import toast from 'react-hot-toast';

const Categories = () => {
  const [categories, setCategories] = useState([]);
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [categoriesData, articlesData] = await Promise.all([
          categoryService.getAll(),
          articleService.getAll()
        ]);
        setCategories(categoriesData);
        setArticles(articlesData);
      } catch (error) {
        console.error('Error fetching data:', error);
        toast.error('Có lỗi xảy ra khi tải dữ liệu');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const getArticleCount = (categoryId) => {
    return articles.filter(article => article.category_id === categoryId).length;
  };

  const filteredCategories = categories.filter(category =>
    category.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    category.description.toLowerCase().includes(searchQuery.toLowerCase())
  );

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
        <h1 className="text-3xl font-bold text-gray-900">Danh mục</h1>
        <div className="w-full sm:w-auto">
          <input
            type="text"
            placeholder="Tìm kiếm danh mục..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />
        </div>
      </div>

      {filteredCategories.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500 text-lg">Không tìm thấy danh mục nào</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredCategories.map(category => (
            <Link
              key={category.id}
              to={`/categories/${category.id}`}
              className="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300"
            >
              <div className="aspect-w-16 aspect-h-9">
                <img
                  src={category.cover_image}
                  alt={category.name}
                  className="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300"
                />
              </div>
              <div className="p-4">
                <h2 className="text-xl font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300">
                  {category.name}
                </h2>
                <p className="mt-2 text-gray-600 line-clamp-2">
                  {category.description}
                </p>
                <div className="mt-4 flex items-center justify-between text-sm text-gray-500">
                  <span>{getArticleCount(category.id)} bài viết</span>
                  <span className="text-primary-600">Xem thêm →</span>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
};

export default Categories; 