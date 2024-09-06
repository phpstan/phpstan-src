<?php declare(strict_types = 1);

namespace CurlInitReturnTypeExtensionTestPhp7;

use function PHPStan\Testing\assertType;

function (string $unknownString) {
	assertType('(resource|false)', curl_init());
	assertType('(resource|false)', curl_init('https://phpstan.org'));
	assertType('resource|false', curl_init($unknownString));
	assertType('(resource|false)', curl_init(null));
	assertType('(resource|false)', curl_init(''));
	assertType('resource|false', curl_init(':'));
	assertType('resource|false', curl_init('file://host/text.txt'));
	assertType('resource|false', curl_init('FIle://host/text.txt'));
	assertType('(resource|false)', curl_init('host/text.txt'));
	assertType('false', curl_init("\0"));
	assertType('false', curl_init("https://phpstan.org\0"));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = null;
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'https://phpstan.org/try';
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = "\0";
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = "https://phpstan.org\0";
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = $unknownString;
	assertType('resource|false', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = ':';
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'file://host/text.txt';
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'FIle://host/text.txt';
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'host/text.txt';
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = 'file://host/text.txt';
	if (rand(0,1)) $url = null;
	assertType('(resource|false)', curl_init($url));

	$url = 'https://phpstan.org';
	if (rand(0,1)) $url = null;
	if (rand(0,1)) $url = $unknownString;
	assertType('resource|false', curl_init($url));
};
