import React from 'react';
import { Link } from 'react-router-dom';

const About = () => {
  return (
    <div className="container mx-auto px-4 py-8">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-3xl font-bold text-gray-900 mb-8">Về Magazine AI</h1>

        <div className="prose max-w-none">
          <p className="text-lg text-gray-600 mb-6">
            Magazine AI là một hệ thống quản lý nội dung thông minh, sử dụng công nghệ AI để tạo và quản lý nội dung chất lượng cao. Hệ thống được thiết kế để giúp các tổ chức và cá nhân dễ dàng tạo, quản lý và phân phối nội dung một cách hiệu quả.
          </p>

          <h2 className="text-2xl font-bold text-gray-900 mt-8 mb-4">Tính năng chính</h2>
          <ul className="list-disc pl-6 mb-6">
            <li>Tự động tạo nội dung với AI</li>
            <li>Quản lý bài viết và danh mục</li>
            <li>Hệ thống duyệt bài thông minh</li>
            <li>Phân tích và thống kê</li>
            <li>Giao diện người dùng thân thiện</li>
            <li>Tối ưu hóa SEO</li>
          </ul>

          <h2 className="text-2xl font-bold text-gray-900 mt-8 mb-4">Công nghệ sử dụng</h2>
          <ul className="list-disc pl-6 mb-6">
            <li>React.js cho Frontend</li>
            <li>Laravel cho Backend</li>
            <li>OpenAI API cho xử lý ngôn ngữ tự nhiên</li>
            <li>Tailwind CSS cho giao diện</li>
            <li>MySQL cho cơ sở dữ liệu</li>
          </ul>

          <h2 className="text-2xl font-bold text-gray-900 mt-8 mb-4">Đội ngũ phát triển</h2>
          <p className="text-gray-600 mb-6">
            Magazine AI được phát triển bởi một đội ngũ chuyên gia giàu kinh nghiệm trong lĩnh vực phát triển phần mềm và AI. Chúng tôi luôn nỗ lực để cải thiện và cập nhật hệ thống nhằm mang đến trải nghiệm tốt nhất cho người dùng.
          </p>

          <h2 className="text-2xl font-bold text-gray-900 mt-8 mb-4">Liên hệ</h2>
          <p className="text-gray-600 mb-6">
            Nếu bạn có bất kỳ câu hỏi hoặc đề xuất nào, vui lòng liên hệ với chúng tôi qua trang{' '}
            <Link to="/contact" className="text-blue-600 hover:text-blue-800">
              Liên hệ
            </Link>.
          </p>

          <div className="mt-8">
            <Link
              to="/articles"
              className="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
            >
              Khám phá bài viết
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default About; 