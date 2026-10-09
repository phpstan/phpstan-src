#!/usr/bin/env bash
# Shared by the Windows turbo jobs in phar.yml: exports the extension version
# (the short SHA of the last commit touching turbo-ext/src, the Makefile's
# computation) and fetches the php-devel-pack matching the installed PHP
# (MATRIX_TS: nts/zts, MATRIX_VS: its toolset infix) into C:\php-devel and the
# php-sdk-binary-tools at PHP_SDK_COMMIT into C:\php-sdk. For a prerelease
# (PHP_PRERELEASE, the minor's entry find-php-windows-prereleases.sh resolved
# for this run) the pack and its checksum come from that entry.
set -euo pipefail

SHA=$(git log -1 --format=%H -- turbo-ext/src | cut -c1-7)
echo "extension version: $SHA"
echo "PHPSTANTURBO_VERSION=$SHA" >> "$GITHUB_ENV"

if [ -n "${PHP_PRERELEASE:-}" ] && [ "$PHP_PRERELEASE" != "null" ]; then
	# shellcheck disable=SC2016 # PHP code, not shell expansions
	read -r PACK SHA256 < <(php -r '
		$entry = json_decode(getenv("PHP_PRERELEASE"), true, 512, JSON_THROW_ON_ERROR);
		$devel = $entry[getenv("MATRIX_TS") === "zts" ? "ts" : "nts"]["devel"];
		echo $devel["path"], " ", $devel["sha256"], "\n";
	')
	echo "devel pack: $PACK"
	curl -fsSLo devel-pack.zip "https://downloads.php.net/~windows/qa/$PACK" \
		|| curl -fsSLo devel-pack.zip "https://downloads.php.net/~windows/qa/archives/$PACK"
	echo "$SHA256  devel-pack.zip" | sha256sum -c -
else
	FULL=$(php -r 'echo PHP_VERSION;')
	# thread-safe devel packs carry no infix, NTS ones carry -nts
	INFIX=$([ "$MATRIX_TS" = "zts" ] && echo "" || echo "-nts")
	PACK="php-devel-pack-$FULL$INFIX-Win32-$MATRIX_VS-x64.zip"
	echo "devel pack: $PACK"
	curl -fsSLo devel-pack.zip "https://windows.php.net/downloads/releases/$PACK" \
		|| curl -fsSLo devel-pack.zip "https://windows.php.net/downloads/releases/archives/$PACK"
fi
unzip -q devel-pack.zip -d /c/php-devel

git clone -q https://github.com/php/php-sdk-binary-tools.git /c/php-sdk
git -C /c/php-sdk checkout -q "$PHP_SDK_COMMIT"
