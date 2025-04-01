import React, { useState } from 'react';
import { Outlet, Link, useLocation } from 'react-router-dom';
import { Button } from '../components/ui';

const AdminLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const location = useLocation();

  const menuItems = [
    {
      path: '/admin',
      label: 'Tổng quan',
      icon: '📊'
    },
    {
      path: '/admin/users',
      label: 'Quản lý người dùng',
      icon: '👥'
    },
    {
      path: '/admin/categories',
      label: 'Quản lý danh mục',
      icon: '📁'
    },
    {
      path: '/admin/articles/rewritten',
      label: 'Bài viết chờ duyệt',
      icon: '📝'
    },
    {
      path: '/admin/articles/approved',
      label: 'Bài viết đã duyệt',
      icon: '✅'
    },
    {
      path: '/admin/settings/ai',
      label: 'Cài đặt AI',
      icon: '🤖'
    }
  ];

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Sidebar */}
      <div
        className={`fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-200 ease-in-out ${
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        } lg:translate-x-0`}
      >
        <div className="flex items-center justify-between h-16 px-4 border-b">
          <h1 className="text-xl font-bold">Admin Panel</h1>
          <Button
            variant="ghost"
            onClick={() => setSidebarOpen(false)}
            className="lg:hidden"
          >
            ✕
          </Button>
        </div>

        <nav className="p-4">
          <ul className="space-y-2">
            {menuItems.map(item => (
              <li key={item.path}>
                <Link
                  to={item.path}
                  className={`flex items-center px-4 py-2 rounded-lg transition-colors ${
                    location.pathname === item.path
                      ? 'bg-blue-50 text-blue-600'
                      : 'text-gray-600 hover:bg-gray-50'
                  }`}
                >
                  <span className="mr-3">{item.icon}</span>
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      </div>

      {/* Main content */}
      <div className="lg:pl-64">
        {/* Header */}
        <header className="bg-white shadow-sm">
          <div className="flex items-center justify-between h-16 px-4">
            <Button
              variant="ghost"
              onClick={() => setSidebarOpen(true)}
              className="lg:hidden"
            >
              ☰
            </Button>
            <div className="flex items-center space-x-4">
              <Button variant="outline">Đăng xuất</Button>
            </div>
          </div>
        </header>

        {/* Page content */}
        <main className="p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default AdminLayout; 