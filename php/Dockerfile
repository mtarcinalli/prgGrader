FROM php:8.2-apache
# Define o fuso horário como variável de ambiente
ENV TZ=America/Sao_Paulo
# 1. Instala pacotes do sistema, dependências da GPU/compiladores e tzdata
# 2. Configura a timezone no sistema Linux (Debian) e no PHP (date.timezone)
# 3. Limpa o cache do apt para otimizar o tamanho da imagem
RUN apt-get update && apt-get install -y --no-install-recommends \
    tzdata \
    libpq-dev \
    unzip \
    build-essential \
    cxxtest \
    default-jdk \
    bc \
    iputils-ping \
    && docker-php-ext-install pdo pdo_pgsql \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && echo "date.timezone = ${TZ}" > /usr/local/etc/php/conf.d/timezone.ini \
    && rm -rf /var/lib/apt/lists/*

# Configuração e permissões do diretório de uploads
RUN mkdir -p /var/www/uploads \
    && chown www-data:www-data /var/www/uploads \
    && chmod 775 /var/www/uploads