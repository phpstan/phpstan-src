<?php

namespace Bug3151;

class Foo
{

	/**
	 * @template T
	 * @param T[] $haystack
	 * @param callable(T): bool $matcher
	 * @return T|null;
	 */
	function search(array $haystack, callable $matcher)
	{
		foreach ($haystack as $item) {
			if (!$matcher($item)) {
				continue;
			}
			return $item;
		}
		return null;
	}

	/**
	 * @param array<array{type: string, foo: string}> $myArray
	 * @return array{type: string, foo: string}
	 */
	function findByType(array $myArray, string $type): array {
		$found = $this->search($myArray, function(array $item) use ($type): bool {
			return $item['type'] === $type;
		});
		if ($found === null) {
			throw new \LogicException();
		}
		return $found;
	}

}
