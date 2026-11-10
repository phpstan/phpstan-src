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
}
