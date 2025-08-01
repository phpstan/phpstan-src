<?php declare(strict_types = 1);

namespace Bug7772;

function session_start(
	int $lifetime,
	?string $path = null,
	?string $domain = null,
	?bool $secure = null,
	?bool $httponly = true
): void {
	\session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
	\session_start();
}
