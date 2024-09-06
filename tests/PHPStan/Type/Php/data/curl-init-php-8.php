<?php declare(strict_types = 1);

namespace CurlInitReturnTypeExtensionTestPhp8;

use function PHPStan\Testing\assertType;

function (string $unknownString) {
	assertType('(CurlHandle|false)', curl_init());
	assertType('(CurlHandle|false)', curl_init('https://phpstan.org'));
	assertType('CurlHandle|false', curl_init($unknownString));
	assertType('(CurlHandle|false)', curl_init(null));
	assertType('(CurlHandle|false)', curl_init(''));
	assertType('(CurlHandle|false)', curl_init(':'));
	assertType('(CurlHandle|false)', curl_init('file://host/text.txt'));
	assertType('(CurlHandle|false)', curl_init('FIle://host/text.txt'));
	assertType('(CurlHandle|false)', curl_init('host/text.txt'));
	assertType('*NEVER*', curl_init("\0"));
	assertType('*NEVER*', curl_init("https://phpstan.org\0"));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = null;
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'https://phpstan.org/try';
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = "\0";
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = "https://phpstan.org\0";
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = $unknownString;
	assertType('CurlHandle|false', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url .= $unknownString;
	assertType('CurlHandle|false', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = ':';
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'file://host/text.txt';
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'FIle://host/text.txt';
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'host/text.txt';
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'file://host/text.txt';
	if (rand(0,1)) $url = null;
	assertType('(CurlHandle|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = null;
	if (rand(0,1)) $url = $unknownString;
	assertType('CurlHandle|false', curl_init($url));
};
