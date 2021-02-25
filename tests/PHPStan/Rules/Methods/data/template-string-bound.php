<?php

namespace TemplateStringBound;

/** @template T of string */
class Foo
{

	/** @var T */
	private $value;

	/**
	 * @param T $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * @return T
	 */
	public function getValue(): string
	{
		return $this->value;
	}

}

/** @template T of int */
class Bar
{

	/** @var T */
	private $value;

	/**
	 * @param T $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * @return T
	 */
	public function getValue(): int
	{
		return $this->value;
	}

}
