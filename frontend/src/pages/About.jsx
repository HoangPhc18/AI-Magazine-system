import React from 'react';
import { Link } from 'react-router-dom';

const About = () => {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Hero Section */}
      <div className="text-center mb-16">
        <h1 className="text-4xl font-extrabold text-gray-900 sm:text-5xl">
          Về chúng tôi
        </h1>
        <p className="mt-3 max-w-2xl mx-auto text-xl text-gray-500 sm:mt-4">
          Nền tảng chia sẻ kiến thức về AI và công nghệ
        </p>
      </div>

      {/* Mission Section */}
      <div className="bg-white rounded-lg shadow-lg overflow-hidden mb-16">
        <div className="relative pb-48">
          <img
            className="absolute h-full w-full object-cover"
            src="/images/about-mission.jpg"
            alt="Our Mission"
          />
        </div>
        <div className="relative px-6 py-8">
          <h2 className="text-3xl font-bold text-gray-900 mb-4">
            Sứ mệnh của chúng tôi
          </h2>
          <p className="text-lg text-gray-600">
            Chúng tôi cam kết mang đến những thông tin chất lượng và cập nhật
            nhất về trí tuệ nhân tạo và công nghệ. Mục tiêu của chúng tôi là
            giúp mọi người hiểu rõ hơn về tác động của AI trong cuộc sống hiện
            đại và tương lai.
          </p>
        </div>
      </div>

      {/* Features Grid */}
      <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3 mb-16">
        <div className="bg-white rounded-lg shadow-lg p-6">
          <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <svg
              className="w-6 h-6 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
              />
            </svg>
          </div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">
            Nội dung chất lượng
          </h3>
          <p className="text-gray-600">
            Tất cả bài viết đều được kiểm duyệt kỹ lưỡng và cập nhật thường
            xuyên để đảm bảo tính chính xác và hữu ích.
          </p>
        </div>

        <div className="bg-white rounded-lg shadow-lg p-6">
          <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <svg
              className="w-6 h-6 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
          </div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">
            Cộng đồng sôi động
          </h3>
          <p className="text-gray-600">
            Tham gia thảo luận, chia sẻ ý kiến và kết nối với những người có
            cùng đam mê về AI và công nghệ.
          </p>
        </div>

        <div className="bg-white rounded-lg shadow-lg p-6">
          <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <svg
              className="w-6 h-6 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 10V3L4 14h7v7l9-11h-7z"
              />
            </svg>
          </div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">
            Cập nhật nhanh chóng
          </h3>
          <p className="text-gray-600">
            Luôn cập nhật những tin tức mới nhất về AI và công nghệ từ khắp
            nơi trên thế giới.
          </p>
        </div>
      </div>

      {/* Team Section */}
      <div className="bg-white rounded-lg shadow-lg p-8 mb-16">
        <h2 className="text-3xl font-bold text-gray-900 mb-8 text-center">
          Đội ngũ của chúng tôi
        </h2>
        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
          <div className="text-center">
            <img
              className="w-32 h-32 rounded-full mx-auto mb-4"
              src="/images/team/member1.jpg"
              alt="Team member"
            />
            <h3 className="text-lg font-semibold text-gray-900">Nguyễn Văn A</h3>
            <p className="text-gray-600">Tổng biên tập</p>
          </div>
          <div className="text-center">
            <img
              className="w-32 h-32 rounded-full mx-auto mb-4"
              src="/images/team/member2.jpg"
              alt="Team member"
            />
            <h3 className="text-lg font-semibold text-gray-900">Trần Thị B</h3>
            <p className="text-gray-600">Biên tập viên</p>
          </div>
          <div className="text-center">
            <img
              className="w-32 h-32 rounded-full mx-auto mb-4"
              src="/images/team/member3.jpg"
              alt="Team member"
            />
            <h3 className="text-lg font-semibold text-gray-900">Lê Văn C</h3>
            <p className="text-gray-600">Chuyên gia AI</p>
          </div>
          <div className="text-center">
            <img
              className="w-32 h-32 rounded-full mx-auto mb-4"
              src="/images/team/member4.jpg"
              alt="Team member"
            />
            <h3 className="text-lg font-semibold text-gray-900">Phạm Thị D</h3>
            <p className="text-gray-600">Biên tập viên</p>
          </div>
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-blue-700 rounded-lg shadow-lg p-8 text-center">
        <h2 className="text-3xl font-bold text-white mb-4">
          Tham gia cùng chúng tôi
        </h2>
        <p className="text-xl text-blue-100 mb-8">
          Đăng ký để nhận những bài viết mới nhất về AI và công nghệ
        </p>
        <div className="max-w-md mx-auto">
          <form className="flex">
            <input
              type="email"
              placeholder="Email của bạn"
              className="flex-1 rounded-l-md border-0 px-4 py-2 text-base text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-700"
            />
            <button
              type="submit"
              className="inline-flex items-center px-6 py-2 border border-transparent text-base font-medium rounded-r-md text-blue-600 bg-white hover:bg-blue-50"
            >
              Đăng ký
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};

export default About; 