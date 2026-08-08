<?php declare(strict_types = 1);

namespace Bug15061;

/**
 * Every isset() subject narrows the array to hasOffset(), and intersecting n of them
 * used to cost 2^n TypeCombinator::intersect() calls: each hasOffset() contributes the
 * same `array|ArrayAccess` default base type, and those n identical unions were
 * distributed over each other one at a time.
 *
 * @phpstan-type FooEntity array{
 *     a?: string,
 *     b?: string,
 *     c?: string,
 *     d?: string,
 *     e?: string,
 *     f?: string,
 *     g?: string,
 *     h?: string,
 *     i?: string,
 *     j?: string,
 *     k?: string,
 *     l?: string,
 *     m?: string,
 *     n?: string,
 *     o?: string,
 *     p?: string,
 *     q?: string,
 *     r?: string,
 *     s?: string,
 *     t?: string,
 *     u?: string,
 *     v?: string,
 *     w?: string,
 *     x?: string,
 *     y?: string,
 *     z?: string,
 * }
 */
final class TestClass
{

	public function __invoke(): void
	{
		/** @var array<string, FooEntity> $entities */
		$entities = [];

		foreach ($entities as $entity) {
			$ok = isset(
				$entity['a'],
				$entity['b'],
				$entity['c'],
				$entity['d'],
				$entity['e'],
				$entity['f'],
				$entity['g'],
				$entity['h'],
				$entity['i'],
				$entity['j'],
				$entity['k'],
				$entity['l'],
				$entity['m'],
				$entity['n'],
				$entity['o'],
				$entity['p'],
				$entity['q'],
				$entity['r'],
				$entity['s'],
				$entity['t'],
				$entity['u'],
				$entity['v'],
				$entity['w'],
				$entity['x'],
				$entity['y'],
				$entity['z'],
			);

			if (!$ok) {
				continue;
			}
		}
	}

}
