import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import axios from 'axios';

const ArticleDetail = () => {
  const { id } = useParams();
  const [article, setArticle] = useState(null);
  const [relatedArticles, setRelatedArticles] = useState([]);
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchData();
  }, [id]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [articleRes, relatedRes, commentsRes] = await Promise.all([
        axios.get(`/api/articles/${id}`),
        axios.get(`/api/articles/${id}/related`),
        axios.get(`/api/articles/${id}/comments`),
      ]);

      setArticle(articleRes.data.data);
      setRelatedArticles(relatedRes.data.data);
      setComments(commentsRes.data.data);
    } catch (error) {
      console.error('Error fetching data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitComment = async (e) => {
    e.preventDefault();
    if (!comment.trim()) return;

    try {
      setSubmitting(true);
      await axios.post(`/api/articles/${id}/comments`, {
        content: comment,
      });
      setComment('');
      fetchData(); // Refresh comments
    } catch (error) {
      console.error('Error posting comment:', error);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (!article) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900">
            Không tìm thấy bài viết
          </h1>
          <p className="mt-2 text-gray-600">
            Bài viết bạn đang tìm kiếm không tồn tại hoặc đã bị xóa.
          </p>
          <Link
            to="/articles"
            className="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
          >
            Quay lại danh sách bài viết
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Article Header */}
      <div className="mb-8">
        <div className="flex items-center space-x-4">
          <Link
            to={`/categories/${article.category?.id}`}
            className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200"
          >
            {article.category?.name}
          </Link>
          <span className="text-sm text-gray-500">
            {new Date(article.created_at).toLocaleDateString()}
          </span>
        </div>
        <h1 className="mt-4 text-4xl font-extrabold text-gray-900">
          {article.title}
        </h1>
        <div className="mt-4 flex items-center">
          <img
            className="h-10 w-10 rounded-full"
            src={article.author?.avatar || '/default-avatar.png'}
            alt=""
          />
          <div className="ml-3">
            <p className="text-sm font-medium text-gray-900">
              {article.author?.name}
            </p>
            <p className="text-sm text-gray-500">Tác giả</p>
          </div>
        </div>
      </div>

      {/* Featured Image */}
      <div className="relative pb-48 mb-8">
        <img
          className="absolute h-full w-full object-cover rounded-lg"
          src={article.image || '/default-article.jpg'}
          alt={article.title}
        />
      </div>

      {/* Article Content */}
      <div className="prose prose-lg max-w-none">
        <div dangerouslySetInnerHTML={{ __html: article.content }} />
      </div>

      {/* Tags */}
      {article.tags && article.tags.length > 0 && (
        <div className="mt-8">
          <h3 className="text-sm font-medium text-gray-500">Tags</h3>
          <div className="mt-2 flex flex-wrap gap-2">
            {article.tags.map((tag) => (
              <Link
                key={tag.id}
                to={`/tags/${tag.id}`}
                className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200"
              >
                {tag.name}
              </Link>
            ))}
          </div>
        </div>
      )}

      {/* Comments Section */}
      <div className="mt-12">
        <h3 className="text-2xl font-bold text-gray-900">
          Bình luận ({comments.length})
        </h3>
        <form onSubmit={handleSubmitComment} className="mt-6">
          <div>
            <label htmlFor="comment" className="sr-only">
              Bình luận của bạn
            </label>
            <textarea
              id="comment"
              rows={3}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              className="shadow-sm block w-full focus:ring-blue-500 focus:border-blue-500 sm:text-sm border-gray-300 rounded-md"
              placeholder="Viết bình luận của bạn..."
            />
          </div>
          <div className="mt-3">
            <button
              type="submit"
              disabled={submitting}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {submitting ? 'Đang gửi...' : 'Gửi bình luận'}
            </button>
          </div>
        </form>

        <div className="mt-8 space-y-6">
          {comments.map((comment) => (
            <div key={comment.id} className="flex space-x-3">
              <div className="flex-shrink-0">
                <img
                  className="h-10 w-10 rounded-full"
                  src={comment.user?.avatar || '/default-avatar.png'}
                  alt=""
                />
              </div>
              <div className="flex-grow">
                <div className="text-sm">
                  <span className="font-medium text-gray-900">
                    {comment.user?.name}
                  </span>
                  <span className="text-gray-500 ml-2">
                    {new Date(comment.created_at).toLocaleDateString()}
                  </span>
                </div>
                <div className="mt-2 text-sm text-gray-700">
                  {comment.content}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Related Articles */}
      {relatedArticles.length > 0 && (
        <div className="mt-12">
          <h3 className="text-2xl font-bold text-gray-900">
            Bài viết liên quan
          </h3>
          <div className="mt-6 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            {relatedArticles.map((relatedArticle) => (
              <article
                key={relatedArticle.id}
                className="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
              >
                <div className="relative pb-48">
                  <img
                    className="absolute h-full w-full object-cover"
                    src={relatedArticle.image || '/default-article.jpg'}
                    alt={relatedArticle.title}
                  />
                </div>
                <div className="p-6">
                  <div className="flex items-center">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {relatedArticle.category?.name}
                    </span>
                  </div>
                  <h4 className="mt-2 text-lg font-semibold text-gray-900">
                    <Link
                      to={`/articles/${relatedArticle.id}`}
                      className="hover:text-blue-600"
                    >
                      {relatedArticle.title}
                    </Link>
                  </h4>
                  <p className="mt-2 text-gray-600 line-clamp-2">
                    {relatedArticle.excerpt}
                  </p>
                </div>
              </article>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default ArticleDetail; 