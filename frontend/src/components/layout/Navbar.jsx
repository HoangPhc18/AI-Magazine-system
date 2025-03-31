import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

const Navbar = () => {
  const { user, logout } = useAuth();

  return (
    <nav className="bg-white shadow">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-center h-16">
          <Link to="/" className="text-xl font-bold text-blue-600">
            Magazine AI
          </Link>

          <div className="flex items-center space-x-4">
            <Link to="/articles" className="text-gray-600 hover:text-blue-600">
              Bài viết
            </Link>
            <Link to="/categories" className="text-gray-600 hover:text-blue-600">
              Danh mục
            </Link>
            <Link to="/about" className="text-gray-600 hover:text-blue-600">
              Giới thiệu
            </Link>
            <Link to="/contact" className="text-gray-600 hover:text-blue-600">
              Liên hệ
            </Link>
          </div>

          <div className="flex items-center space-x-4">
            {user ? (
              <>
                <Link
                  to="/admin/dashboard"
                  className="text-gray-600 hover:text-blue-600"
                >
                  Quản trị
                </Link>
                <button
                  onClick={logout}
                  className="text-gray-600 hover:text-blue-600"
                >
                  Đăng xuất
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/auth/login"
                  className="text-gray-600 hover:text-blue-600"
                >
                  Đăng nhập
                </Link>
                <Link
                  to="/auth/register"
                  className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                >
                  Đăng ký
                </Link>
              </>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar; 