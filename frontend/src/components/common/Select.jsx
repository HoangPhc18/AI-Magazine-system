import React from 'react';

const Select = ({ 
  name,
  value,
  onChange,
  options = [],
  placeholder = 'Chọn...',
  disabled = false,
  className = '',
  ...props 
}) => {
  const baseClasses = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm';
  const disabledClasses = disabled ? 'bg-gray-100 cursor-not-allowed' : '';
  const errorClasses = props.error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '';

  return (
    <select
      name={name}
      value={value}
      onChange={onChange}
      disabled={disabled}
      className={`${baseClasses} ${disabledClasses} ${errorClasses} ${className}`}
      {...props}
    >
      <option value="">{placeholder}</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  );
};

export default Select; 