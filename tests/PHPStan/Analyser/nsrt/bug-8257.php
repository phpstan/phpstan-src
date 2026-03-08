<?php declare(strict_types = 1);

namespace Bug8257;

use function PHPStan\Testing\assertType;

interface TreeMapper
{
	/**
	 * @template T of object
	 *
	 * @param string|class-string<T> $signature
	 * @param mixed $source
	 * @return (
	 *     $signature is class-string<T>
	 *         ? T
	 *         : mixed
	 * )
	 */
	public function map(string $signature, $source);
}

/** @var TreeMapper $tm */
$tm;

class A {}

assertType('Bug8257\A', $tm->map(...[A::class, []]));
