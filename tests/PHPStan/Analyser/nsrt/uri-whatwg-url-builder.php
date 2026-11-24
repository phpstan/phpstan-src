<?php // lint >= 8.6

namespace UriWhatWgUrlBuilder;

use Uri\WhatWg\UrlBuilder;
use function PHPStan\Testing\assertType;

function errors(UrlBuilder $builder): void
{
	$builder->build(null, $softErrors);
	assertType('list<Uri\WhatWg\UrlValidationError>', $softErrors);
}
