<?php

namespace UnusedConstructorParametersResolvedDynamic;

class VariableVariable
{

	public function __construct($secret, $other)
	{
		// the dynamic name resolves to 'secret' during the walk; the syntactic
		// check could not see it (the assignment happens inside the body) and
		// treated every parameter as used
		$name = 'secret';
		echo $$name;
	}

}

class DynamicCompact
{

	/** @var array<string, mixed> */
	private $data;

	/**
	 * @param string[] $keys
	 */
	public function __construct($x, array $keys)
	{
		// a compact() argument that is not statically known can name any
		// variable - the syntactic check reported $x here
		$this->data = compact($keys);
	}

}

class OverwrittenParameter
{

	/** @var int */
	private $sum;

	public function __construct($x, $used)
	{
		// the incoming value is overwritten before it is ever read - the
		// parameter is unused even though the name appears in the body
		$x = 1;
		$this->sum = $x + $used;
	}

}

class ParameterOverwrittenInOneBranch
{

	/** @var int */
	private $value;

	public function __construct($x)
	{
		if (rand(0, 1) === 0) {
			$x = 1;
		}
		$this->value = $x;
	}

}

class OverwrittenParameterObservedByFuncGetArgs
{

	/** @var array<mixed> */
	private $args;

	public function __construct($x)
	{
		$x = 1;
		$this->args = [func_get_args(), $x];
	}

}
