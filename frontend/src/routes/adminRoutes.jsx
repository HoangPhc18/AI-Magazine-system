import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import AdminLayout from '../layouts/AdminLayout';
import Dashboard from '../pages/admin/Dashboard';
import UserList from '../pages/admin/users/UserList';
import UserDetail from '../pages/admin/users/UserDetail';
import CategoryList from '../pages/admin/categories/CategoryList';
import CategoryDetail from '../pages/admin/categories/CategoryDetail';
import RewrittenArticleList from '../pages/admin/articles/RewrittenArticleList';
import RewrittenArticleDetail from '../pages/admin/articles/RewrittenArticleDetail';
import ApprovedArticleList from '../pages/admin/articles/ApprovedArticleList';
import ApprovedArticleDetail from '../pages/admin/articles/ApprovedArticleDetail';
import AISettings from '../pages/admin/settings/AISettings';

const AdminRoutes = () => {
  return (
    <Routes>
      <Route path="/" element={<AdminLayout />}>
        <Route index element={<Dashboard />} />
        
        {/* Quản lý người dùng */}
        <Route path="users">
          <Route index element={<UserList />} />
          <Route path="new" element={<UserDetail />} />
          <Route path=":id" element={<UserDetail />} />
        </Route>

        {/* Quản lý danh mục */}
        <Route path="categories">
          <Route index element={<CategoryList />} />
          <Route path="new" element={<CategoryDetail />} />
          <Route path=":id" element={<CategoryDetail />} />
        </Route>

        {/* Quản lý bài viết đã viết lại */}
        <Route path="articles/rewritten">
          <Route index element={<RewrittenArticleList />} />
          <Route path=":id" element={<RewrittenArticleDetail />} />
        </Route>

        {/* Quản lý bài viết đã duyệt */}
        <Route path="articles/approved">
          <Route index element={<ApprovedArticleList />} />
          <Route path=":id" element={<ApprovedArticleDetail />} />
        </Route>

        {/* Cài đặt AI */}
        <Route path="settings/ai" element={<AISettings />} />

        {/* Chuyển hướng các route không tồn tại về dashboard */}
        <Route path="*" element={<Navigate to="/admin" replace />} />
      </Route>
    </Routes>
  );
};

export default AdminRoutes; 