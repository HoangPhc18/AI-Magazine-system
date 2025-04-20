FROM python:3.10-slim

# Set up working directory
WORKDIR /app

# Create directory for logs
RUN mkdir -p /app/logs

# Install dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy the rest of the application
COPY . .

# Expose port
EXPOSE 5005

# Start the application
CMD ["python", "app.py"] 