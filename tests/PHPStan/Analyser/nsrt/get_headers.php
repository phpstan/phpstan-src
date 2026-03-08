<?php declare(strict_types = 1);

namespace Bug13835;

use function PHPStan\Testing\assertType;

assertType('list<string>|false', get_headers('http://example.com'));
assertType('array<int|string, list<string>|string>|false', get_headers('http://example.com', true));
assertType('list<string>|false', get_headers('http://example.com', false));
