import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Navbar = () => {
  const { user, logout, isAuthenticated } = useAuth();
  const isAdmin = user?.role === 'admin';

  return (
    <nav className="bg-gray-800 text-white">
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-16">
          <div className="flex items-center space-x-4">
            <Link to="/" className="text-xl font-bold">
              Magazine AI
            </Link>
            {isAuthenticated && isAdmin && (
              <div className="flex space-x-4">
                <Link to="/admin/articles" className="hover:text-gray-300">
                  Articles
                </Link>
                <Link to="/admin/categories" className="hover:text-gray-300">
                  Categories
                </Link>
                <Link to="/admin/settings" className="hover:text-gray-300">
                  Settings
                </Link>
                <Link to="/admin/ai-settings" className="hover:text-gray-300">
                  AI Settings
                </Link>
              </div>
            )}
          </div>
          <div className="flex items-center space-x-4">
            {isAuthenticated ? (
              <>
                <span>{user.name}</span>
                <button
                  onClick={logout}
                  className="bg-red-600 hover:bg-red-700 px-4 py-2 rounded"
                >
                  Logout
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  className="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded"
                >
                  Login
                </Link>
                <Link
                  to="/register"
                  className="bg-green-600 hover:bg-green-700 px-4 py-2 rounded"
                >
                  Register
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