FROM mcr.microsoft.com/vscode/devcontainers/php:8.1

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

# PHP memory limit
RUN echo "memory_limit=768M" > /usr/local/etc/php/php.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
