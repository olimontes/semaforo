FROM php:8.2-cli

# Instala extensões e ferramentas
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    vim \
    nano \
    libtool \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    pkg-config \
    && docker-php-ext-install pcntl sysvsem sysvshm

# Ajuste de timezone e permissão (opcional)
ENV TZ=America/Sao_Paulo
