<?php // lint >= 8.1

namespace CallFirstClassCallables;

class Foo
{

	/**
	 * @param mixed $mixed
	 */
	public function doFoo($mixed)
	{
		$f = $this->doBar(...);
		$f($mixed);

		$g = \Closure::fromCallable([$this, 'doBar']);
		$g($mixed);
	}

	/**
	 * @template T of object
	 * @param T $object
	 * @return T
	 */
	public function doBar($object)
	{
		return $object;
	}

}

/**
 * @param resource $context
 * @param (\Closure(): int)|(\Closure(int, int): int) $union
 */
function doBaz($context, \Closure $union): void
{
	$mtRand = mt_rand(...);
	$mtRand();
	$mtRand(1, 2);
	$mtRand(1);
	$mtRand(1, 2, 3);

	$setOption = stream_context_set_option(...);
	$setOption($context, ['http' => ['method' => 'POST']]);
	$setOption($context, 'http', 'method', 'POST');
	$setOption($context, 'http', 'method');

	$union();
	$union(1);

	$strtok = strtok(...);
	$strtok(string: 'a b', token: ' ');
	$strtok(str: 'a b');
}

function namedArgs(int $a, string $b, bool ...$c): void
{
}

/**
 * @param list<int> $ints
 */
function storedClosures(array $ints): void
{
	$f = namedArgs(...);
	$f(b: 'x', a: 1);
	$f(1, 'x', true, false);
	$f(a: 1);
	$f(1, a: 2, b: 'x');
	$f(1, 'x', d: true);
	$f(b: 1, a: 'x');

	$sort = sort(...);
	$sort($ints);
	$sort([3, 1, 2]);
}
