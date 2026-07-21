FROM php:8.2-apache

# MySQLi और cURL extensions इंस्टॉल करें (ताकि Firebase/MySQL कनेक्ट हो सके)
RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install mysqli curl \
    && a2enmod rewrite

# अपनी फाइलें Apache वेब सर्वर फोल्डर में कॉपी करें
COPY . /var/www/html/

# Port 80 Open करें
EXPOSE 80
