import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Button, Card } from '../../components/common';
import { articleService, dateService, errorService } from '../../services';
import { toast } from 'react-toastify';

const ArticleDetail = () => {
  const { id } = useParams();
  const [article, setArticle] = useState(null);
  const [relatedArticles, setRelatedArticles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchArticle = async () => {
      try {
        const response = await articleService.getArticleById(id);
        setArticle(response.data);
      } catch (err) {
        const errorMessage = errorService.handleApiError(err);
        setError(errorMessage);
        toast.error(errorMessage);
      } finally {
        setLoading(false);
      }
    };

    fetchArticle();
    loadRelatedArticles();
  }, [id]);

  const loadRelatedArticles = async () => {
    try {
      const response = await articleService.getRelatedArticles(id, 3);
      setRelatedArticles(response.data);
    } catch (error) {
      console.error('Error loading related articles:', error);
    }
  };

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

  if (!article) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-gray-500 text-center">
          <p className="text-xl font-semibold mb-2">Article not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="max-w-4xl mx-auto">
        {/* Article Header */}
        <header className="mb-8">
          <h1 className="text-4xl font-bold mb-4">{article.title}</h1>
          <div className="flex items-center text-gray-600">
            <span>{dateService.formatDate(article.created_at)}</span>
            <span className="mx-2">•</span>
            <span>{article.views} lượt xem</span>
          </div>
          {article.category && (
            <Link
              to={`/categories/${article.category.id}`}
              className="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full hover:bg-blue-200 transition-colors"
            >
              {article.category.name}
            </Link>
          )}
        </header>

        {/* Article Image */}
        {article.cover_image && (
          <div className="mb-8">
            <img
              src={article.cover_image}
              alt={article.title}
              className="w-full h-96 object-cover rounded-lg"
            />
          </div>
        )}

        {/* Article Content */}
        <div className="prose max-w-none mb-12">
          <div dangerouslySetInnerHTML={{ __html: article.content }} />
        </div>

        {/* Related Articles */}
        {relatedArticles.length > 0 && (
          <div className="border-t pt-8">
            <h2 className="text-2xl font-bold mb-6">Bài viết liên quan</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {relatedArticles.map(relatedArticle => (
                <Link
                  key={relatedArticle.id}
                  to={`/articles/${relatedArticle.id}`}
                  className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
                >
                  {relatedArticle.image && (
                    <img
                      src={relatedArticle.image}
                      alt={relatedArticle.title}
                      className="w-full h-48 object-cover"
                    />
                  )}
                  <div className="p-4">
                    <h3 className="font-semibold text-lg mb-2">{relatedArticle.title}</h3>
                    <p className="text-gray-600 text-sm mb-2">{relatedArticle.excerpt}</p>
                    <div className="flex items-center text-sm text-gray-500">
                      <span>{dateService.formatDate(relatedArticle.created_at)}</span>
                      <span className="mx-2">•</span>
                      <span>{relatedArticle.views} lượt xem</span>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ArticleDetail; 