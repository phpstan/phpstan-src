<?php declare(strict_types = 1);

namespace HttpResponseHeader;

use function PHPStan\Testing\assertType;

class Foo
{

	public function foo(): void
	{
		file_get_contents('https://example.com');
		assertType('non-empty-list<non-falsy-string>', $http_response_header);
	}

}
