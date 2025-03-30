import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { articleService } from '../services/articleService';
import { categoryService } from '../services/categoryService';
import toast from 'react-hot-toast';

const Home = () => {
  const [featuredArticles, setFeaturedArticles] = useState([]);
  const [latestArticles, setLatestArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [articlesData, categoriesData] = await Promise.all([
          articleService.getAll(),
          categoryService.getAll()
        ]);

        // Sort articles by created_at to get latest ones
        const sortedArticles = [...articlesData.data].sort(
          (a, b) => new Date(b.created_at) - new Date(a.created_at)
        );

        setFeaturedArticles(sortedArticles.slice(0, 3));
        setLatestArticles(sortedArticles.slice(3, 9));
        setCategories(categoriesData);
      } catch (error) {
        console.error('Error fetching data:', error);
        toast.error('Có lỗi xảy ra khi tải dữ liệu');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Hero Section */}
      <div className="relative bg-gray-900 rounded-lg overflow-hidden mb-12">
        <div className="absolute inset-0">
          <img
            className="w-full h-full object-cover"
            src="/images/hero-bg.jpg"
            alt="AI Magazine"
          />
          <div className="absolute inset-0 bg-gray-900 opacity-75"></div>
        </div>
        <div className="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
          <h1 className="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
            Khám phá thế giới AI
          </h1>
          <p className="mt-6 text-xl text-gray-300 max-w-3xl">
            Cập nhật những tin tức mới nhất về trí tuệ nhân tạo, học máy và công nghệ tiên tiến
          </p>
          <div className="mt-10">
            <Link
              to="/articles"
              className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
            >
              Xem bài viết
            </Link>
          </div>
        </div>
      </div>

      {/* Featured Articles */}
      <div className="mb-12">
        <h2 className="text-3xl font-bold text-gray-900 mb-8">Bài viết nổi bật</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {featuredArticles.map(article => (
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
                <h3 className="text-xl font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300">
                  {article.title}
                </h3>
                <p className="mt-2 text-gray-600 line-clamp-2">
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
      </div>

      {/* Categories */}
      <div className="mb-12">
        <h2 className="text-3xl font-bold text-gray-900 mb-8">Danh mục</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          {categories.map(category => (
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
                <h3 className="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300">
                  {category.name}
                </h3>
                <p className="mt-1 text-sm text-gray-600 line-clamp-2">
                  {category.description}
                </p>
              </div>
            </Link>
          ))}
        </div>
      </div>

      {/* Latest Articles */}
      <div>
        <h2 className="text-3xl font-bold text-gray-900 mb-8">Bài viết mới nhất</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {latestArticles.map(article => (
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
                <h3 className="text-xl font-semibold text-gray-900 group-hover:text-primary-600 transition-colors duration-300">
                  {article.title}
                </h3>
                <p className="mt-2 text-gray-600 line-clamp-2">
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
      </div>
    </div>
  );
};

export default Home; 