<?php // lint >= 7.4

namespace Bug11275;

/**
 * @no-named-arguments
 */
final class A
{
	/**
	 * @var list<B>
	 */
	private array $b;

	public function __construct(B ...$b)
	{
		$this->b = $b;
	}
}

final class B
{
}

final class C
{
	/**
	 * @var list<B>
	 */
	private array $b;

	/**
	 * @no-named-arguments
	 */
	public function __construct(B ...$b)
	{
		$this->b = $b;
	}
}

final class D
{
	/**
	 * @var list<B>
	 */
	private array $b;

	public function __construct(B ...$b)
	{
		$this->b = $b;
	}
}
