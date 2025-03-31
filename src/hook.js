import axios from 'axios';

export const fetchData = async (url) => {
  try {
    const response = await axios.get(url);
    return response.data;
  } catch (error) {
    if (error.code === 'ECONNREFUSED') {
      console.error('Network Error: Unable to connect to the server. Please check if the backend server is running.');
    } else {
      console.error('Error fetching data:', error.message);
    }
    throw error;
  }
};
