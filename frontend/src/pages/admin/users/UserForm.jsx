import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Button, Input, Select, Switch, Form, message } from 'antd';
import { userService } from '../../../services';

const UserForm = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [initialValues, setInitialValues] = useState(null);

  useEffect(() => {
    if (id) {
      loadUser();
    }
  }, [id]);

  const loadUser = async () => {
    try {
      setLoading(true);
      const response = await userService.getUser(id);
      setInitialValues(response.data);
      form.setFieldsValue(response.data);
    } catch (error) {
      message.error('Không thể tải thông tin người dùng');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (values) => {
    try {
      setLoading(true);
      if (id) {
        await userService.updateUser(id, values);
        message.success('Cập nhật người dùng thành công');
      } else {
        await userService.createUser(values);
        message.success('Thêm người dùng thành công');
      }
      navigate('/admin/users');
    } catch (error) {
      message.error(error.response?.data?.message || 'Có lỗi xảy ra');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">
        {id ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới'}
      </h1>
      
      <Form
        form={form}
        layout="vertical"
        onFinish={handleSubmit}
        initialValues={initialValues}
      >
        <Form.Item
          name="name"
          label="Họ tên"
          rules={[{ required: true, message: 'Vui lòng nhập họ tên' }]}
        >
          <Input />
        </Form.Item>

        <Form.Item
          name="email"
          label="Email"
          rules={[
            { required: true, message: 'Vui lòng nhập email' },
            { type: 'email', message: 'Email không hợp lệ' }
          ]}
        >
          <Input />
        </Form.Item>

        {!id && (
          <Form.Item
            name="password"
            label="Mật khẩu"
            rules={[
              { required: true, message: 'Vui lòng nhập mật khẩu' },
              { min: 6, message: 'Mật khẩu phải có ít nhất 6 ký tự' }
            ]}
          >
            <Input.Password />
          </Form.Item>
        )}

        <Form.Item
          name="role"
          label="Vai trò"
          rules={[{ required: true, message: 'Vui lòng chọn vai trò' }]}
        >
          <Select>
            <Select.Option value="admin">Quản trị viên</Select.Option>
            <Select.Option value="editor">Biên tập viên</Select.Option>
            <Select.Option value="user">Người dùng</Select.Option>
          </Select>
        </Form.Item>

        <Form.Item
          name="is_active"
          label="Trạng thái"
          valuePropName="checked"
        >
          <Switch />
        </Form.Item>

        <Form.Item>
          <Button type="primary" htmlType="submit" loading={loading}>
            {id ? 'Cập nhật' : 'Thêm mới'}
          </Button>
          <Button 
            className="ml-2" 
            onClick={() => navigate('/admin/users')}
          >
            Hủy
          </Button>
        </Form.Item>
      </Form>
    </div>
  );
};

export default UserForm; 