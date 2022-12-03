<?php

namespace PDOQuote;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo()
	{
		$database = new \PDO('dsn');
		$quote_string = $database->quote('string');
		assertType('string|false', $quote_string);
	}

}
