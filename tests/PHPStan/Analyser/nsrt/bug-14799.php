<?php declare(strict_types = 1);

namespace Bug14799;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, mixed> $extras
	 */
	public function build(?string $d, array $extras): void
	{
		$out = [];
		$out['a'] = 'foo';
		$out['b'] = 'bar';
		$out['c'] = 'baz';

		if (null !== $d) {
			$out['d'] = $d;
		}

		assertType("array{a: 'foo', b: 'bar', c: 'baz', d?: string}", $out);

		$out += $extras;

		assertType("array{a: 'foo', b: 'bar', c: 'baz', d?: string, ...<string, mixed>}", $out);
	}

	/**
	 * @param array<int, bool> $extras
	 * @param array{a: int, ...<string, float>} $unsealed
	 */
	public function mergeWithUnsealedLeft(array $unsealed, array $extras): void
	{
		$result = $unsealed + $extras;
		assertType('array{a: int, ...<int|string, bool|float>}', $result);
	}

	/**
	 * @param array<string, mixed> $extras
	 */
	public function binaryOp(array $extras): void
	{
		$out = ['a' => 1, 'b' => 2];
		assertType('array{a: 1, b: 2, ...<string, mixed>}', $out + $extras);
	}

	/**
	 * @param array<int, string> $extras
	 */
	public function listLeft(array $extras): void
	{
		$out = [10, 'x'];
		assertType('array{10, \'x\', ...<int, string>}', $out + $extras);
	}
}
