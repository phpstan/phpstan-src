<?php

namespace Bug10771;

/**
 * @template T of Template
 *
 * @extends B<T>
 */
class A extends B
{
	/**
	 * @return class-string<T>
	 */
	public function getClassString(): string
	{
		return static::ENTITY;
	}
}

/**
 * @template T of Template
 */
class B
{
	/** @var class-string<T> */
	public const ENTITY = Template::class;
}

class Template
{

}

/**
 * @template TA of Template
 *
 * @extends Bb<TA>
 */
class Aa extends Bb
{
	/**
	 * @return class-string<TA>
	 */
	public function getClassString(): string
	{
		return static::ENTITY;
	}
}

/**
 * @template TB of Template
 */
class Bb
{
	/** @var class-string<TB> */
	public const ENTITY = Template::class;
}
