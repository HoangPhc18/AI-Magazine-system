import React, { useState } from 'react';
import toast from 'react-hot-toast';

const Settings = () => {
  const [settings, setSettings] = useState({
    siteName: 'AI Magazine',
    siteDescription: 'Nơi chia sẻ kiến thức về trí tuệ nhân tạo và công nghệ',
    contactEmail: 'contact@aimagazine.com',
    contactPhone: '(84) 123-456-789',
    address: '123 Đường ABC, Quận 1, TP.HCM',
    facebook: 'https://facebook.com/aimagazine',
    twitter: 'https://twitter.com/aimagazine',
    linkedin: 'https://linkedin.com/company/aimagazine'
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setSettings(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      // TODO: Implement API call to save settings
      toast.success('Cập nhật cài đặt thành công!');
    } catch (error) {
      toast.error('Có lỗi xảy ra khi cập nhật cài đặt!');
    }
  };

  return (
    <div className="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div className="px-4 py-6 sm:px-0">
        <h1 className="text-2xl font-semibold text-gray-900">Cài đặt chung</h1>
        <form onSubmit={handleSubmit} className="mt-6 space-y-6">
          <div>
            <label htmlFor="siteName" className="block text-sm font-medium text-gray-700">
              Tên trang web
            </label>
            <input
              type="text"
              name="siteName"
              id="siteName"
              value={settings.siteName}
              onChange={handleChange}
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            />
          </div>

          <div>
            <label htmlFor="siteDescription" className="block text-sm font-medium text-gray-700">
              Mô tả trang web
            </label>
            <textarea
              name="siteDescription"
              id="siteDescription"
              rows={3}
              value={settings.siteDescription}
              onChange={handleChange}
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            />
          </div>

          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label htmlFor="contactEmail" className="block text-sm font-medium text-gray-700">
                Email liên hệ
              </label>
              <input
                type="email"
                name="contactEmail"
                id="contactEmail"
                value={settings.contactEmail}
                onChange={handleChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <div>
              <label htmlFor="contactPhone" className="block text-sm font-medium text-gray-700">
                Số điện thoại
              </label>
              <input
                type="tel"
                name="contactPhone"
                id="contactPhone"
                value={settings.contactPhone}
                onChange={handleChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
          </div>

          <div>
            <label htmlFor="address" className="block text-sm font-medium text-gray-700">
              Địa chỉ
            </label>
            <input
              type="text"
              name="address"
              id="address"
              value={settings.address}
              onChange={handleChange}
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            />
          </div>

          <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
              <label htmlFor="facebook" className="block text-sm font-medium text-gray-700">
                Facebook URL
              </label>
              <input
                type="url"
                name="facebook"
                id="facebook"
                value={settings.facebook}
                onChange={handleChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <div>
              <label htmlFor="twitter" className="block text-sm font-medium text-gray-700">
                Twitter URL
              </label>
              <input
                type="url"
                name="twitter"
                id="twitter"
                value={settings.twitter}
                onChange={handleChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <div>
              <label htmlFor="linkedin" className="block text-sm font-medium text-gray-700">
                LinkedIn URL
              </label>
              <input
                type="url"
                name="linkedin"
                id="linkedin"
                value={settings.linkedin}
                onChange={handleChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
          </div>

          <div className="flex justify-end">
            <button
              type="submit"
              className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              Lưu cài đặt
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Settings; 