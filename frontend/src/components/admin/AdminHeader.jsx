import React from 'react';
import { useLocation } from 'react-router-dom';

const AdminHeader = () => {
  const location = useLocation();
  const path = location.pathname.split('/').pop();

  const getTitle = () => {
    switch (path) {
      case 'admin':
        return 'Dashboard';
      case 'articles':
        return 'Articles Management';
      case 'categories':
        return 'Categories Management';
      case 'users':
        return 'Users Management';
      case 'settings':
        return 'Settings';
      default:
        return 'Admin Panel';
    }
  };

  return (
    <header className="bg-white shadow-sm">
      <div className="px-6 py-4">
        <h1 className="text-2xl font-semibold text-gray-900">{getTitle()}</h1>
      </div>
    </header>
  );
};

export default AdminHeader; 