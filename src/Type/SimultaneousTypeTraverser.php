<?php declare(strict_types = 1);

namespace PHPStan\Type;

final class SimultaneousTypeTraverser
{

	/** @var callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type */
	private $cb;

	/**
	 * @param callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type $cb
	 */
	public static function map(Type $left, Type $right, callable $cb): Type
	{
		$self = new self($cb);

		return $self->mapInternal($left, $right);
	}

	/** @param callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type $cb */
	private function __construct(callable $cb)
	{
		$this->cb = $cb;
	}

	/** @internal */
	public function mapInternal(Type $left, Type $right): Type
	{
		return ($this->cb)($left, $right, [$this, 'traverseInternal']);
	}

	/** @internal */
	public function traverseInternal(Type $left, Type $right): Type
	{
		return $left->traverseSimultaneously($right, [$this, 'mapInternal']);
	}

}
