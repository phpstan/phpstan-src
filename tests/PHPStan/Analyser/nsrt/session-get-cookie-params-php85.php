<?php // lint >= 8.5

namespace SessionGetCookieParamsPhp85;

use function PHPStan\Testing\assertType;

function test(): void
{
	assertType("array{lifetime: int<0, max>, path: non-falsy-string, domain: string, secure: bool, httponly: bool, samesite: 'Lax'|'lax'|'None'|'none'|'Strict'|'strict', partitioned: bool}", session_get_cookie_params());
	assertType('true', array_key_exists('partitioned', session_get_cookie_params()));
}
