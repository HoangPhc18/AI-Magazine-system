import React from 'react';
import { Link } from 'react-router-dom';

const ArticleCard = ({ article, date }) => {
  return (
    <Link
      to={`/articles/${article.id}`}
      className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
    >
      {article.image && (
        <img
          src={article.image}
          alt={article.title}
          className="w-full h-48 object-cover"
        />
      )}
      <div className="p-4">
        <h3 className="font-semibold text-lg mb-2">{article.title}</h3>
        <p className="text-gray-600 text-sm mb-2">{article.excerpt}</p>
        <div className="flex items-center text-sm text-gray-500">
          <span>{date}</span>
          <span className="mx-2">•</span>
          <span>{article.views} lượt xem</span>
        </div>
      </div>
    </Link>
  );
};

export default ArticleCard; 