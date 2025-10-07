<?php declare(strict_types = 1);

namespace Bug11485;

class Foo {

	public function bar(string $value, bool $strict = true): bool	{

		$flags = $strict ? FILTER_NULL_ON_FAILURE : 0;
		$result = filter_var($value, FILTER_VALIDATE_BOOLEAN, $flags);
		if ($result === null) throw new \Exception("not a boolean");

		$result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if ($result === null) throw new \Exception("not a boolean");

		return $result;
	}
}
