import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import AdminLayout from '../layouts/AdminLayout';
import Dashboard from '../pages/admin/Dashboard';
import UserList from '../pages/admin/users/UserList';
import UserForm from '../pages/admin/users/UserForm';
import CategoryList from '../pages/admin/categories/CategoryList';
import CategoryForm from '../pages/admin/categories/CategoryForm';
import RewrittenArticleList from '../pages/admin/articles/RewrittenArticleList';
import RewrittenArticleDetail from '../pages/admin/articles/RewrittenArticleDetail';
import ApprovedArticleList from '../pages/admin/articles/ApprovedArticleList';
import ApprovedArticleDetail from '../pages/admin/articles/ApprovedArticleDetail';
import AISettings from '../pages/admin/settings/AISettings';

const AdminRoutes = () => {
  return (
    <AdminLayout>
      <Routes>
        <Route path="/" element={<Dashboard />} />
        
        {/* User Management Routes */}
        <Route path="/users" element={<UserList />} />
        <Route path="/users/create" element={<UserForm />} />
        <Route path="/users/:id/edit" element={<UserForm />} />
        
        {/* Category Management Routes */}
        <Route path="/categories" element={<CategoryList />} />
        <Route path="/categories/create" element={<CategoryForm />} />
        <Route path="/categories/:id/edit" element={<CategoryForm />} />
        
        {/* Article Management Routes */}
        <Route path="/articles/rewritten" element={<RewrittenArticleList />} />
        <Route path="/articles/rewritten/:id" element={<RewrittenArticleDetail />} />
        <Route path="/articles/approved" element={<ApprovedArticleList />} />
        <Route path="/articles/approved/:id" element={<ApprovedArticleDetail />} />
        
        {/* AI Settings Route */}
        <Route path="/settings/ai" element={<AISettings />} />
        
        {/* Redirect to dashboard for unknown routes */}
        <Route path="*" element={<Navigate to="/admin" replace />} />
      </Routes>
    </AdminLayout>
  );
};

export default AdminRoutes; 