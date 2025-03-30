FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements file
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy project files
COPY . .

# Create and activate virtual environment
RUN python -m venv venv
ENV PATH="/app/venv/bin:$PATH"

# Expose port 8000
EXPOSE 8000

# Start the service
CMD ["python", "main.py"] 