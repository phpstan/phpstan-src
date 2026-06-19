<?php declare(strict_types = 1);

namespace Bug11281;

use function PHPStan\Testing\assertType;

function hello2(string $values): void
{
	$values = json_decode($values);
	$hasError = false;
	try {
		$values = array_map(static function ($item) {
			return Hello::fromObject($item);
		}, $values);
		assertType('array<' . Hello::class . '>', $values);
	} catch (\Throwable) {
		$hasError = true;
	}
	if (!$hasError) {
		// The successful try-branch proves $values is array<Hello>; the
		// pre-assignment mixed must not make the merged type collapse to mixed.
		assertType('array<' . Hello::class . '>', $values);
	}
}

final class Hello
{

	public function __construct(public int $a)
	{
	}

	public static function fromObject(\stdClass $object): self
	{
		return new self(...(array) $object);
	}

}
