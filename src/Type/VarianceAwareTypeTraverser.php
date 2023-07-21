<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;

/**
 * @phpstan-type Callback callable(Type $type, TemplateTypeVariance $variance, callable(Type, TemplateTypeVariance): Type $traverse): Type
 */
class VarianceAwareTypeTraverser
{

	/** @var Callback */
	private $cb;

	/**
	 * @param Callback $cb
	 */
	public static function map(Type $type, TemplateTypeVariance $variance, callable $cb): Type
	{
		$self = new self($cb);

		return $self->mapInternal($type, $variance);
	}

	/**
	 * @param Callback $cb
	 */
	private function __construct(callable $cb)
	{
		$this->cb = $cb;
	}

	/** @internal */
	public function mapInternal(Type $type, TemplateTypeVariance $variance): Type
	{
		return ($this->cb)($type, $variance, [$this, 'traverseInternal']);
	}

	/** @internal */
	public function traverseInternal(Type $type, TemplateTypeVariance $variance): Type
	{
		return $type->traverseWithVariance($variance, [$this, 'mapInternal']);
	}

}
