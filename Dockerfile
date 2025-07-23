FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Create logs directory
RUN mkdir -p /app/logs && chmod 755 /app/logs

# Expose port
EXPOSE 5000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:5000/health-check.php || exit 1

# Start PHP server
CMD ["php", "-S", "0.0.0.0:5000", "-t", "."]