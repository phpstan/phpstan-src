# [Choice] PHP version: 7, 7.4, 7.3
ARG VARIANT=7
FROM mcr.microsoft.com/vscode/devcontainers/php:${VARIANT}

# [Option] Install Node.js
ARG INSTALL_NODE="true"
ARG NODE_VERSION="lts/*"
RUN if [ "${INSTALL_NODE}" = "true" ]; then su vscode -c "source /usr/local/share/nvm/nvm.sh && nvm install ${NODE_VERSION} 2>&1"; fi

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

# PHP memory limit
RUN echo "memory_limit=768M" > /usr/local/etc/php/php.ini

# Composer v2
RUN EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)" \
	&& php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
	&& ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
	&& if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then >&2 echo 'ERROR: Invalid installer checksum'; rm composer-setup.php; exit 1; fi \
	&& php composer-setup.php --version=2.0.0-RC1 \
	&& php -r "unlink('composer-setup.php');" \
	&& mv composer.phar /usr/local/bin/composer
