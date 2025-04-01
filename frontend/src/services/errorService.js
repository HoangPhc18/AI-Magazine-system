import { toast } from 'react-toastify';

const errorService = {
  handleApiError: (error) => {
    if (error.response) {
      // Server trả về lỗi với status code
      const status = error.response.status;
      const message = error.response.data?.message || 'Đã xảy ra lỗi';

      toast.error(message);

      switch (status) {
        case 400:
          return `Lỗi yêu cầu: ${message}`;
        case 401:
          return 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
        case 403:
          return 'Bạn không có quyền thực hiện thao tác này.';
        case 404:
          return 'Không tìm thấy tài nguyên được yêu cầu.';
        case 409:
          return 'Dữ liệu đã tồn tại.';
        case 422:
          return `Lỗi xác thực: ${message}`;
        case 500:
          return 'Lỗi máy chủ. Vui lòng thử lại sau.';
        default:
          return `Lỗi không xác định (${status}): ${message}`;
      }
    } else if (error.request) {
      // Không nhận được phản hồi từ server
      const message = 'Không thể kết nối đến server';
      toast.error(message);
      return message;
    } else {
      // Lỗi khi tạo request
      const message = error.message || 'Có lỗi xảy ra';
      toast.error(message);
      return message;
    }
  },

  handleValidationError: (error) => {
    if (error.response?.data?.errors) {
      // Lỗi validation từ server
      const errors = error.response.data.errors;
      return Object.values(errors).flat().join(', ');
    }
    return error.message || 'Lỗi xác thực dữ liệu.';
  },

  handleNetworkError: (error) => {
    if (!navigator.onLine) {
      return 'Không có kết nối internet. Vui lòng kiểm tra kết nối.';
    }
    return 'Lỗi kết nối. Vui lòng thử lại.';
  },

  handleAuthError: (error) => {
    if (error.response?.status === 401) {
      return 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
    }
    return 'Lỗi xác thực. Vui lòng kiểm tra thông tin đăng nhập.';
  },

  handleFileError: (error) => {
    if (error.response?.status === 413) {
      return 'File quá lớn. Vui lòng chọn file nhỏ hơn.';
    }
    if (error.response?.status === 415) {
      return 'Định dạng file không được hỗ trợ.';
    }
    return 'Lỗi khi tải file. Vui lòng thử lại.';
  }
};

export default errorService; 