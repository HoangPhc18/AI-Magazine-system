const validationService = {
  validateFileType: (file, allowedTypes) => {
    return allowedTypes.includes(file.type);
  },

  validateFileSize: (file, maxSize) => {
    return file.size <= maxSize;
  },

  validateImageDimensions: async (file, minWidth, minHeight) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        resolve(img.width >= minWidth && img.height >= minHeight);
      };
      img.onerror = () => resolve(false);
      img.src = URL.createObjectURL(file);
    });
  },

  validateEmail: (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  validatePassword: (password) => {
    // Ít nhất 8 ký tự, 1 chữ hoa, 1 chữ thường, 1 số
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    return passwordRegex.test(password);
  },

  validateUsername: (username) => {
    // Ít nhất 3 ký tự, chỉ chữ cái, số và dấu gạch dưới
    const usernameRegex = /^[a-zA-Z0-9_]{3,}$/;
    return usernameRegex.test(username);
  },

  validatePhoneNumber: (phone) => {
    // Định dạng số điện thoại Việt Nam
    const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
    return phoneRegex.test(phone);
  },

  validateURL: (url) => {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  },

  validateRequired: (value) => {
    return value !== null && value !== undefined && value !== '';
  },

  validateMinLength: (value, min) => {
    return value.length >= min;
  },

  validateMaxLength: (value, max) => {
    return value.length <= max;
  },

  validateNumber: (value) => {
    return !isNaN(value) && !isNaN(parseFloat(value));
  },

  validateMinValue: (value, min) => {
    return parseFloat(value) >= min;
  },

  validateMaxValue: (value, max) => {
    return parseFloat(value) <= max;
  },

  validateDate: (date) => {
    const d = new Date(date);
    return d instanceof Date && !isNaN(d);
  },

  validateDateRange: (startDate, endDate) => {
    const start = new Date(startDate);
    const end = new Date(endDate);
    return start <= end;
  },

  validateFileExtension: (filename, allowedExtensions) => {
    const extension = filename.split('.').pop().toLowerCase();
    return allowedExtensions.includes(extension);
  },

  validateImageAspectRatio: async (file, minRatio, maxRatio) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const ratio = img.width / img.height;
        resolve(ratio >= minRatio && ratio <= maxRatio);
      };
      img.onerror = () => resolve(false);
      img.src = URL.createObjectURL(file);
    });
  },

  validateFileMimeType: (file, allowedMimeTypes) => {
    return allowedMimeTypes.includes(file.type);
  },

  validateFileSizeRange: (file, minSize, maxSize) => {
    return file.size >= minSize && file.size <= maxSize;
  },

  validateImageResolution: async (file, minResolution) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const resolution = img.width * img.height;
        resolve(resolution >= minResolution);
      };
      img.onerror = () => resolve(false);
      img.src = URL.createObjectURL(file);
    });
  },

  validateImageFormat: (file) => {
    const allowedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return allowedFormats.includes(file.type);
  },

  validateImageQuality: async (file, minQuality = 0.8) => {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        const dataUrl = canvas.toDataURL(file.type, minQuality);
        resolve(dataUrl.length > 0);
      };
      img.onerror = () => resolve(false);
      img.src = URL.createObjectURL(file);
    });
  }
};

export default validationService; 