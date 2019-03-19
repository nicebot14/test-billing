FROM php:7.2.10-fpm

ARG UID
ARG GID

ENV TIMEZONE=UTC

# устанавливаем пакеты которые нам понадобятся для пыхи, команд ниже и расширений пыхи, без них будут ошибки сыпаться
# каждый пакет проверен опытным путем "попробуй удалить и посмотреть соберется ли образ", лишнего точно нет =)
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    wget \
    ca-certificates \
    libicu-dev \
    libpq-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    zip \
    unzip \
    gnupg \
    cron \
    autoconf \
    automake \
    libtool \
    pkg-config \
    optipng \
    jpegoptim \
    cron \
    vim \
    procps

RUN apt-get -y autoremove && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ставим полезные расширения пыхи, включая редис, xdebug, gd и другие
RUN pecl install xdebug-2.6.1 && \
    pecl install redis && \
    rm -rf /tmp/pear && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/ && \
    docker-php-ext-configure intl && \
    docker-php-ext-enable xdebug redis && \
    docker-php-ext-install -j$(nproc) \
    gd \
    bcmath \
    opcache \
    pdo \
    pdo_pgsql \
    zip \
    mbstring \
    iconv \
    intl \
    fileinfo

# ставим композер
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    chmod +x /usr/local/bin/composer

# есть бага такая, в этой директоории композер хранит свои мета-данные, а она не под www-data, в итоге мы под www-data юзерам не можем юзать композер
# т.к. он ложится. Эта строка фиксит эту проблему
RUN mkdir /var/www/.composer && chown www-data:www-data /var/www/.composer

# задаем таймзону, на всякий случай
RUN ln -snf /usr/share/zoneinfo/$TIMEZONE /etc/localtime && echo $TIMEZONE > /etc/timezone

# задаем текущего юзера хоста на www-data чтобы всякое непонятное с доступами не происходило
RUN usermod -u $UID www-data
RUN groupmod -g $GID www-data

WORKDIR /var/www/backend

EXPOSE 80

CMD bash -c "composer install && chown -R www-data:www-data vendor && php bin/console server:run 0.0.0.0:80"
