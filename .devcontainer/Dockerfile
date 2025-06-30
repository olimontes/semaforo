# Use a imagem base oficial do PHP 8.2
FROM php:8.2-cli

ENV TZ=America/Sao_Paulo

# Instala ferramentas, Python, e extensões PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    vim \
    nano \
    # Adicione estas duas linhas para instalar Python
    python3 \
    python3-pip \
    # Dependências para extensões PHP
    libtool \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    pkg-config \
    # Limpa o cache
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    # Instala extensões PHP
    && docker-php-ext-install pcntl sysvsem sysvshm

WORKDIR /usr/src/app
