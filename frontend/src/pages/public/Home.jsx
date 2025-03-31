import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { articleService, categoryService, dateService, errorService } from '../../services';
import { ArticleCard } from '../../components/common';

const Home = () => {
  const [latestArticles, setLatestArticles] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [articlesData, categoriesData] = await Promise.all([
          articleService.getLatest(6),
          categoryService.getAll()
        ]);
        setLatestArticles(articlesData);
        setCategories(categoriesData);
      } catch (err) {
        setError(errorService.handleApiError(err));
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  if (loading) return <div>Đang tải...</div>;
  if (error) return <div>Lỗi: {error}</div>;

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-12 mb-12">
        <h1 className="text-4xl font-bold mb-4">Chào mừng đến với Magazine AI</h1>
        <p className="text-xl mb-8">Khám phá những bài viết chất lượng được tạo bởi AI</p>
        <Link
          to="/articles"
          className="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors"
        >
          Xem tất cả bài viết
        </Link>
      </div>

      {/* Latest Articles */}
      <section className="mb-12">
        <h2 className="text-2xl font-bold mb-6">Bài viết mới nhất</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {latestArticles.map((article) => (
            <ArticleCard
              key={article.id}
              article={article}
              date={dateService.formatDate(article.created_at)}
            />
          ))}
        </div>
        <div className="text-center mt-8">
          <Link
            to="/articles"
            className="text-blue-600 hover:text-blue-800 font-semibold"
          >
            Xem thêm bài viết →
          </Link>
        </div>
      </section>

      {/* Featured Categories */}
      <section>
        <h2 className="text-2xl font-bold mb-6">Danh mục nổi bật</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {categories.map(category => (
            <Link
              key={category.id}
              to={`/categories/${category.id}`}
              className="bg-white rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow"
            >
              <h3 className="font-semibold text-lg mb-2">{category.name}</h3>
              <p className="text-gray-600 text-sm mb-2">{category.description}</p>
              <span className="text-sm text-gray-500">
                {category.articles_count} bài viết
              </span>
            </Link>
          ))}
        </div>
        <div className="text-center mt-8">
          <Link
            to="/categories"
            className="text-blue-600 hover:text-blue-800 font-semibold"
          >
            Xem tất cả danh mục →
          </Link>
        </div>
      </section>
    </div>
  );
};

export default Home; 