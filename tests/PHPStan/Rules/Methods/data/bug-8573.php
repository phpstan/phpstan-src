<?php declare(strict_types = 1);

class A
{
	final public function __construct() { }

	/**
	 * @param array<string, mixed> $data
	 */
	public static function __set_state(array $data): static
	{
		$obj = new static();

		return $obj;
	}
}

class B extends A
{
	public static function __set_state(array $data): static
	{
		$obj = parent::__set_state($data);

		return $obj;
	}
}
