import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button, Card } from '../../components/common';
import { categoryService, articleService, dateService, errorService } from '../../services';
import { toast } from 'react-toastify';

const CategoryDetail = () => {
  const { id } = useParams();
  const [category, setCategory] = useState(null);
  const [articles, setArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchCategory = async () => {
      try {
        const response = await categoryService.getCategoryById(id);
        setCategory(response.data);
      } catch (err) {
        const errorMessage = errorService.handleApiError(err);
        setError(errorMessage);
        toast.error(errorMessage);
      } finally {
        setLoading(false);
      }
    };

    const fetchArticles = async () => {
      try {
        const response = await articleService.getArticlesByCategory(id);
        setArticles(response.data);
      } catch (err) {
        const errorMessage = errorService.handleApiError(err);
        toast.error(errorMessage);
      }
    };

    fetchCategory();
    fetchArticles();
  }, [id]);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-red-500 text-center">
          <p className="text-xl font-semibold mb-2">Error</p>
          <p>{error}</p>
        </div>
      </div>
    );
  }

  if (!category) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-gray-500 text-center">
          <p className="text-xl font-semibold mb-2">Category not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-4">{category.name}</h1>
        <p className="text-gray-600">{category.description}</p>
        <div className="flex items-center text-sm text-gray-500 mt-2">
          <span>{dateService.formatDate(category.created_at)}</span>
          <span className="mx-2">•</span>
          <span>{category.article_count} articles</span>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {articles.map((article) => (
          <Card key={article.id} className="hover:shadow-lg transition-shadow">
            <Link to={`/articles/${article.id}`}>
              {article.cover_image && (
                <img
                  src={article.cover_image}
                  alt={article.title}
                  className="w-full h-48 object-cover"
                />
              )}
              <div className="p-4">
                <h2 className="text-xl font-semibold mb-2">{article.title}</h2>
                <p className="text-gray-600 mb-4">{article.excerpt}</p>
                <div className="flex items-center text-sm text-gray-500">
                  <span>{dateService.formatDate(article.created_at)}</span>
                  <span className="mx-2">•</span>
                  <span>{article.views} lượt xem</span>
                </div>
              </div>
            </Link>
          </Card>
        ))}
      </div>

      {articles.length === 0 && (
        <div className="text-center py-12">
          <p className="text-gray-500">No articles found in this category</p>
        </div>
      )}
    </div>
  );
};

export default CategoryDetail; 