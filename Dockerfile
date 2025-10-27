# Use official PHP image
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Copy everything into container
COPY . .

# Install dependencies (Twig)
RUN apt-get update && apt-get install -y unzip git \
  && curl -sS https://getcomposer.org/installer | php \
  && php composer.phar install --no-dev --optimize-autoloader

# Expose port 10000
EXPOSE 10000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
