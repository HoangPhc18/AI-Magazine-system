export const mockArticles = [
  {
    id: 1,
    title: 'AI và Tương lai của Công nghệ',
    slug: 'ai-va-tuong-lai-cong-nghe',
    excerpt: 'Khám phá những tiến bộ mới nhất trong lĩnh vực AI và tác động của nó đến tương lai công nghệ.',
    content: 'Nội dung chi tiết về AI và tương lai công nghệ...',
    image: 'https://images.unsplash.com/photo-1677442136019-21780ecad995',
    category: {
      id: 1,
      name: 'Công nghệ AI',
      slug: 'cong-nghe-ai'
    },
    author: {
      id: 1,
      name: 'Nguyễn Văn A',
      avatar: 'https://i.pravatar.cc/150?img=1'
    },
    createdAt: '2024-02-20T10:00:00Z',
    updatedAt: '2024-02-20T10:00:00Z',
    views: 150,
    likes: 45
  },
  {
    id: 2,
    title: 'Machine Learning trong Thực tế',
    slug: 'machine-learning-trong-thuc-te',
    excerpt: 'Cách doanh nghiệp đang áp dụng Machine Learning để cải thiện hiệu quả hoạt động.',
    content: 'Nội dung chi tiết về Machine Learning...',
    image: 'https://images.unsplash.com/photo-1677442136019-21780ecad995',
    category: {
      id: 1,
      name: 'Công nghệ AI',
      slug: 'cong-nghe-ai'
    },
    author: {
      id: 2,
      name: 'Trần Thị B',
      avatar: 'https://i.pravatar.cc/150?img=2'
    },
    createdAt: '2024-02-19T15:30:00Z',
    updatedAt: '2024-02-19T15:30:00Z',
    views: 120,
    likes: 38
  }
];

export const mockCategories = [
  {
    id: 1,
    name: 'Công nghệ AI',
    slug: 'cong-nghe-ai',
    description: 'Các bài viết về công nghệ AI và ứng dụng',
    image: 'https://images.unsplash.com/photo-1677442136019-21780ecad995',
    articleCount: 2
  },
  {
    id: 2,
    name: 'Deep Learning',
    slug: 'deep-learning',
    description: 'Kiến thức về Deep Learning và Neural Networks',
    image: 'https://images.unsplash.com/photo-1677442136019-21780ecad995',
    articleCount: 0
  }
];

export const mockUsers = [
  {
    id: 1,
    name: 'Nguyễn Văn A',
    email: 'nguyenvana@example.com',
    avatar: 'https://i.pravatar.cc/150?img=1',
    role: 'admin'
  },
  {
    id: 2,
    name: 'Trần Thị B',
    email: 'tranthib@example.com',
    avatar: 'https://i.pravatar.cc/150?img=2',
    role: 'editor'
  }
]; 