# Imagem oficial da Microsoft com PHP 8.2 + ambiente Codespaces completo
FROM mcr.microsoft.com/devcontainers/php:0-8.2

# Instala extensões PHP necessárias (processos, semáforos, memória compartilhada)
RUN install-php-extensions pcntl sysvshm sysvsem

# Define o fuso horário
ENV TZ=America/Sao_Paulo
