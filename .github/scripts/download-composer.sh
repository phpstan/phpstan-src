#!/bin/sh
# Downloads the newest Composer 2 release into ./composer.phar and verifies
# it the way `composer self-update` does: the release's signature
# (composer.phar.sig, RSA over SHA-384) must verify against Composer's "Tags"
# public key, committed next to this script (composer-tags.pub, from
# https://composer.github.io/pubkeys.html — hosted apart from
# getcomposer.org). A compromised or impersonated getcomposer.org cannot
# supply a phar that passes, and there is no version or checksum to bump.
#
# POSIX sh: it also runs in the Alpine build containers. Needs curl and a PHP
# with the openssl extension, which Composer requires anyway.
set -eu

KEY="$(dirname "$0")/composer-tags.pub"
# the download path of the newest 2.x release, e.g. /download/2.10.3/composer.phar
# shellcheck disable=SC2016 # PHP code, not shell expansions
RELEASE=$(curl -fsSL --retry 3 https://getcomposer.org/versions | php -r '
	$path = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR)["2"][0]["path"] ?? "";
	if (preg_match("~^/download/2\.[0-9]+\.[0-9]+/composer\.phar$~", $path) !== 1) {
		fwrite(STDERR, "unexpected Composer 2 download path: $path\n");
		exit(1);
	}
	echo $path;
')
# verified under another name first, so a phar that fails is never left
# where the caller runs it
curl -fsSLo composer.phar.unverified --retry 3 "https://getcomposer.org$RELEASE"
curl -fsSLo composer.phar.sig --retry 3 "https://getcomposer.org$RELEASE.sig"
# shellcheck disable=SC2016 # PHP code, not shell expansions
php -r '
	$signature = json_decode(file_get_contents("composer.phar.sig"), true, 512, JSON_THROW_ON_ERROR)["sha384"] ?? null;
	$decoded = is_string($signature) ? base64_decode($signature, true) : false;
	if ($decoded === false || openssl_verify(file_get_contents("composer.phar.unverified"), $decoded, file_get_contents($argv[1]), OPENSSL_ALGO_SHA384) !== 1) {
		fwrite(STDERR, "the Composer download does not match its signature\n");
		unlink("composer.phar.unverified");
		exit(1);
	}
' "$KEY"
rm composer.phar.sig
mv composer.phar.unverified composer.phar
echo "Composer $(php composer.phar --version --no-ansi 2>/dev/null | sed -n 's/^Composer version \([^ ]*\).*/\1/p'), signature verified"
