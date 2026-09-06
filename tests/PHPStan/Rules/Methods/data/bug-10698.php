<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10698;

/** @template T */
class Foo
{

	/** @param T $subject */
	public function __construct(private $subject)
	{
	}

	/** @return T */
	public function getSubject()
	{
		return $this->subject;
	}

}

abstract class Bar
{

	/**
	 * @template T
	 * @param Foo<T> $foo
	 * @return T
	 */
	public static function qux(Foo $foo)
	{
		return $foo->getSubject();
	}

}

function test(?string $str): void
{
	Bar::qux(new Foo($str));
}
