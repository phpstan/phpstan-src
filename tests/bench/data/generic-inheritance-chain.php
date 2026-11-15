<?php declare(strict_types = 1);

/*
 * instructions: 216.0M
 * cycles: 299.4M
 * memory: 2.492MiB
 */

namespace BenchGenericInheritanceChain;

/**
 * @template T
 */
interface Container
{

	/** @return T */
	public function get();

}

/**
 * @template T
 * @implements Container<T>
 */
class Level0 implements Container
{

	/** @var T */
	private $value;

	/** @param T $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/** @return T */
	public function get()
	{
		return $this->value;
	}

}

/**
 * @template T
 * @extends Level0<T>
 */
class Level1 extends Level0
{
}

/**
 * @template T
 * @extends Level1<T>
 */
class Level2 extends Level1
{
}

/**
 * @template T
 * @extends Level2<T>
 */
class Level3 extends Level2
{
}

/**
 * @template T
 * @extends Level3<T>
 */
class Level4 extends Level3
{
}

/**
 * @template T
 * @extends Level4<T>
 */
class Level5 extends Level4
{
}

/**
 * @template T
 * @extends Level5<T>
 */
class Level6 extends Level5
{
}

/**
 * @template T
 * @extends Level6<T>
 */
class Level7 extends Level6
{
}

/**
 * @template T
 * @extends Level7<T>
 */
class Level8 extends Level7
{
}

/**
 * @template T
 * @extends Level8<T>
 */
class Level9 extends Level8
{
}

/**
 * @template T
 * @extends Level9<T>
 */
class Level10 extends Level9
{
}

/**
 * @template T
 * @extends Level10<T>
 */
class Level11 extends Level10
{
}

/**
 * @template T
 * @extends Level11<T>
 */
class Level12 extends Level11
{
}

/**
 * @template T
 * @extends Level12<T>
 */
class Level13 extends Level12
{
}

/**
 * Fourteen generic classes, each extending the one before with its template
 * type. ClassAncestorsRule cross-checks the interfaces of every class and
 * reached each ancestor once from every class below it, which was
 * exponential in the depth of the hierarchy, and ClassReflection resolved a
 * generic parent again on every getParentClass() call, so every walk up the
 * hierarchy resolved each ancestor anew.
 */
function run(): int
{
	$value = new Level13(1);

	return $value->get();
}
