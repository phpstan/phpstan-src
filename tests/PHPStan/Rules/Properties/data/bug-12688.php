<?php // lint >= 8.1

namespace Bug12688;

/**
 * @template T = mixed
 */
interface I {}

/**
 * @implements I<mixed>
 */
enum E implements I
{
	case E;
}

/**
 * @template T
 */
final class TemplateWithoutDefaultWorks
{
	/**
	 * @var I<T>
	 */
	public readonly I $i;

	/**
	 * @param I<T> $i
	 */
	public function __construct(I $i = E::E)
	{
		$this->i = $i;
	}
}

/**
 * @template T = mixed
 */
final class TemplateWithDefaultDoesNotWork
{
	/**
	 * @var I<T>
	 */
	public readonly I $i;

	/**
	 * @param I<T> $i
	 */
	public function __construct(I $i = E::E)
	{
		$this->i = $i;
	}
}
