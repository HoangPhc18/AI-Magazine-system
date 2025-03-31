import axios from 'axios';
import validationService from './validationService';
import errorService from './errorService';

const fileService = {
  uploadFile: async (file) => {
    try {
      const formData = new FormData();
      formData.append('file', file);

      const response = await axios.post('/api/files/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      return response.data;
    } catch (error) {
      throw errorService.handleFileError(error);
    }
  },

  deleteFile: async (fileId) => {
    try {
      await axios.delete(`/api/files/${fileId}`);
      return true;
    } catch (error) {
      throw errorService.handleFileError(error);
    }
  },

  validateFile: async (file, options = {}) => {
    const {
      maxSize = 5 * 1024 * 1024, // 5MB
      allowedTypes = ['image/jpeg', 'image/png', 'image/gif'],
      minWidth = 800,
      minHeight = 600
    } = options;

    // Kiểm tra loại file
    if (!validationService.validateFileType(file, allowedTypes)) {
      throw new Error('Loại file không được hỗ trợ');
    }

    // Kiểm tra kích thước file
    if (!validationService.validateFileSize(file, maxSize)) {
      throw new Error('File quá lớn');
    }

    // Kiểm tra kích thước ảnh
    const isValidDimensions = await validationService.validateImageDimensions(file, minWidth, minHeight);
    if (!isValidDimensions) {
      throw new Error('Kích thước ảnh không đạt yêu cầu');
    }

    return true;
  }
};

export default fileService;

export const getFileUrl = (fileId) => {
  return `/api/files/${fileId}`;
};

export const getFilePreview = (file) => {
  if (file.type.startsWith('image/')) {
    return URL.createObjectURL(file);
  }
  return null;
};

export const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

export const getFileExtension = (filename) => {
  return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 1).toLowerCase();
};

export const isImageFile = (file) => {
  return file.type.startsWith('image/');
};

export const isPDFFile = (file) => {
  return file.type === 'application/pdf';
};

export const isDocumentFile = (file) => {
  return file.type.startsWith('application/') && !isPDFFile(file);
};

export const isVideoFile = (file) => {
  return file.type.startsWith('video/');
};

export const isAudioFile = (file) => {
  return file.type.startsWith('audio/');
}; 