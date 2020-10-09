<?php

namespace IncorrectCallToFunction;

foo(1);

foo(1, 2, 3);

$array = [
	'foo' => 'bar',
	'bar' => new \stdClass(),
];
$keys = array_keys($array);
bar($keys[0]);

set_error_handler(function ($errno, $errstr, $errfile, $errline): void {
	// ...
});

function (string $hash, string $privateKey): void {
	$keyId = openssl_pkey_get_private($privateKey);
	if ($keyId === false) {
		return;
	}

	$signature = null;
	if (!openssl_sign($hash, $signature, $keyId, OPENSSL_ALGO_SHA256)) {
		return;
	}
	openssl_free_key($keyId);
};
