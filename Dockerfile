FROM php:7.4.1
WORKDIR /app/
RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
        wget \
        zip \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:1.9.0 /usr/bin/composer /usr/bin/composer

ADD . /app/

