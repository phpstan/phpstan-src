<?php declare(strict_types = 1);

/**
 * @phpstan-immutable
 * @phpstan-template-contravariant T
 */
class Bug8697
{

	/**
	 * @phpstan-readonly
	 * @phpstan-allow-private-mutation
	 * @phpstan-var mixed
	 */
	public $p1;

	/**
	 * @phpstan-readonly-allow-private-mutation
	 * @phpstan-var mixed
	 * @phpstan-throws LogicException
	 * @phpstan-return void
	 */
	public $p2;

	/**
	 * @phpstan-param mixed $v
	 * @phpstan-param-out 1 $v
	 */
	public function f(&$v)
	{
		$v = 1;
	}

}
