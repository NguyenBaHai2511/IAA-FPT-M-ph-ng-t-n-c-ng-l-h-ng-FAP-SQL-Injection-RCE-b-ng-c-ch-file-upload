FROM php:7.4-apache

# Install sqlite extension and enable useful apache mods
RUN apt-get update \
    && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# === THÊM DÒNG NÀY ĐỂ KÍCH HOẠT HÀM BỊ VÔ HIỆU HÓA ===
# Tệp này sẽ ghi đè 'disable_functions' trong php.ini
RUN echo "disable_functions =" > /usr/local/etc/php/conf.d/zz-allow-exec.ini

# --- (Các dòng còn lại giữ nguyên) ---
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]