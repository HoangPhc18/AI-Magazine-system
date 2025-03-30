import React, { createContext, useContext, useState, useEffect } from 'react';
import { mockUsers } from '../utils/mockData';

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const token = localStorage.getItem('token');
      if (token) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));
        // In a real app, this would be an API call
        const mockUser = mockUsers[0]; // Use first user as mock logged in user
        setUser(mockUser);
      }
    } catch (error) {
      console.error('Auth check failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    try {
      // Simulate API delay
      await new Promise(resolve => setTimeout(resolve, 500));
      // In a real app, this would be an API call
      const mockUser = mockUsers.find(u => u.email === email);
      if (!mockUser) {
        throw new Error('Invalid credentials');
      }
      const token = 'mock-jwt-token';
      localStorage.setItem('token', token);
      setUser(mockUser);
      return mockUser;
    } catch (error) {
      throw error.message;
    }
  };

  const logout = () => {
    localStorage.removeItem('token');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}; 