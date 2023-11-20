<?php declare(strict_types=1); // lint >= 8.0

namespace Bug10171;

setcookie("name", "value", 0, "/", secure: true, httponly: true);
setcookie('name', expires_or_options: ['samesite' => 'lax']);

setrawcookie("name", "value", 0, "/", secure: true, httponly: true);
setrawcookie('name', expires_or_options: ['samesite' => 'lax']);

// Wrong
setcookie('name', samesite: 'lax');
setcookie(
	'aaa',
	'bbb',
	10,
	'/',
	'example.com',
	true,
	false,
	'lax',
	1,
);

setrawcookie('name', samesite: 'lax');
setrawcookie(
	'aaa',
	'bbb',
	10,
	'/',
	'example.com',
	true,
	false,
	'lax',
	1,
);
