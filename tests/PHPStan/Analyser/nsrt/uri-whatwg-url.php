<?php // lint >= 8.5

namespace UriWhatWgUrl;

use Uri\WhatWg\Url;
use function PHPStan\Testing\assertType;

function errors(Url $url): void
{
	Url::parse('https://example.com/', null, $errors);
	assertType('list<Uri\WhatWg\UrlValidationError>', $errors);

	new Url('https://example.com/', null, $softErrors);
	assertType('list<Uri\WhatWg\UrlValidationError>', $softErrors);

	$url->resolve('/', $resolveErrors);
	assertType('list<Uri\WhatWg\UrlValidationError>', $resolveErrors);
}
