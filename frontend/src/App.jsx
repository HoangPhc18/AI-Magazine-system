import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

// Layouts
import PublicLayout from './layouts/PublicLayout';
import AdminLayout from './layouts/AdminLayout';

// Public Pages
import Home from './pages/public/Home';
import Articles from './pages/public/Articles';
import ArticleDetail from './pages/public/ArticleDetail';
import Categories from './pages/public/Categories';
import CategoryDetail from './pages/public/CategoryDetail';
import About from './pages/public/About';
import Contact from './pages/public/Contact';

// Auth Pages
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import ForgotPassword from './pages/auth/ForgotPassword';

// Admin Pages
import Dashboard from './pages/admin/Dashboard';
import ArticleList from './pages/admin/articles/ArticleList';
import ArticleCreate from './pages/admin/articles/ArticleCreate';
import ArticleEdit from './pages/admin/articles/ArticleEdit';
import CategoryList from './pages/admin/categories/CategoryList';
import CategoryCreate from './pages/admin/categories/CategoryCreate';
import CategoryEdit from './pages/admin/categories/CategoryEdit';
import UserList from './pages/admin/users/UserList';
import UserDetail from './pages/admin/users/UserDetail';
import UserCreate from './pages/admin/users/UserCreate';
import Settings from './pages/admin/settings/Settings';

// Components
import PrivateRoute from './components/common/PrivateRoute';

const App = () => {
  return (
    <Router>
      <AuthProvider>
        <ToastContainer />
        <Routes>
          {/* Public Routes */}
          <Route path="/" element={<PublicLayout />}>
            <Route index element={<Home />} />
            <Route path="articles" element={<Articles />} />
            <Route path="articles/:id" element={<ArticleDetail />} />
            <Route path="categories" element={<Categories />} />
            <Route path="categories/:id" element={<CategoryDetail />} />
            <Route path="about" element={<About />} />
            <Route path="contact" element={<Contact />} />
          </Route>

          {/* Auth Routes */}
          <Route path="/auth">
            <Route path="login" element={<Login />} />
            <Route path="register" element={<Register />} />
            <Route path="forgot-password" element={<ForgotPassword />} />
          </Route>

          {/* Redirect /login to /auth/login */}
          <Route path="/login" element={<Navigate to="/auth/login" replace />} />

          {/* Admin Routes */}
          <Route
            path="/admin"
            element={
              <PrivateRoute>
                <AdminLayout />
              </PrivateRoute>
            }
          >
            <Route index element={<Navigate to="/admin/dashboard" replace />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="articles">
              <Route index element={<ArticleList />} />
              <Route path="create" element={<ArticleCreate />} />
              <Route path=":id/edit" element={<ArticleEdit />} />
            </Route>
            <Route path="categories">
              <Route index element={<CategoryList />} />
              <Route path="create" element={<CategoryCreate />} />
              <Route path=":id/edit" element={<CategoryEdit />} />
            </Route>
            <Route path="users">
              <Route index element={<UserList />} />
              <Route path="create" element={<UserCreate />} />
              <Route path=":id" element={<UserDetail />} />
            </Route>
            <Route path="settings" element={<Settings />} />
          </Route>
        </Routes>
      </AuthProvider>
    </Router>
  );
};

export default App;
