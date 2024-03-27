<?php

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

file_get_contents('https://example.org');
assertType('list<string>', $http_response_header);
assertNativeType('array<int, string>', $http_response_header);
