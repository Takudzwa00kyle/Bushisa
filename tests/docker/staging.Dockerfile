# Local disposable compatibility runner only; never an application deployment.
FROM node:20-bookworm AS node
FROM mcr.microsoft.com/playwright:v1.64.0-noble AS browsers
FROM php:8.5.11-cli-bookworm
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=browsers /ms-playwright /ms-playwright
RUN apt-get update && apt-get install -y --no-install-recommends \
    git ca-certificates openssl libonig-dev libcurl4-openssl-dev \
    libnss3 libatk1.0-0 libatk-bridge2.0-0 libx11-6 libxcomposite1 libxdamage1 \
    libxext6 libxfixes3 libxrandr2 libgbm1 libxcb1 libxkbcommon0 libasound2 \
    libpango-1.0-0 libcairo2 libcups2 libatspi2.0-0 \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
WORKDIR /workspace
