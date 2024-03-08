<?php

use function PHPStan\Testing\assertType;

file_get_contents('https://example.org');
assertType('list<string>', $http_response_header);
