FROM php:8.2-apache

# تثبيت الـ extensions المطلوبة
RUN docker-php-ext-install pdo pdo_mysql mysqli

# تفعيل mod_rewrite لـ Apache
RUN a2enmod rewrite

# نسخ كل ملفات المشروع
COPY . /var/www/html/

# صلاحيات المجلدات
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chmod -R 755 /var/www/html/uploads

# إعداد Apache
RUN echo '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>' > /etc/apache2/conf-enabled/custom.conf

EXPOSE 80