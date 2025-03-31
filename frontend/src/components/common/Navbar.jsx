import React from 'react';
import { Link } from 'react-router-dom';

const Navbar = () => {
  return (
    <nav className="bg-white shadow-lg">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-center h-16">
          <Link to="/" className="text-xl font-bold text-blue-600">
            Magazine AI
          </Link>
          <div className="flex space-x-4">
            <Link to="/articles" className="text-gray-600 hover:text-blue-600">
              Articles
            </Link>
            <Link to="/categories" className="text-gray-600 hover:text-blue-600">
              Categories
            </Link>
            <Link to="/about" className="text-gray-600 hover:text-blue-600">
              About
            </Link>
            <Link to="/contact" className="text-gray-600 hover:text-blue-600">
              Contact
            </Link>
          </div>
          <div className="flex space-x-4">
            <Link to="/auth/login" className="text-gray-600 hover:text-blue-600">
              Login
            </Link>
            <Link to="/auth/register" className="text-gray-600 hover:text-blue-600">
              Register
            </Link>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar; 