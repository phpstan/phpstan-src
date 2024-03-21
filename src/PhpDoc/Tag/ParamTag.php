<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/** @api */
class ParamTag implements TypedTag
{

	public function __construct(
		private Type $type,
		private bool $isVariadic,
		private TrinaryLogic $immediatelyInvokedCallable,
	)
	{
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function isVariadic(): bool
	{
		return $this->isVariadic;
	}

	public function isImmediatelyInvokedCallable(): TrinaryLogic
	{
		return $this->immediatelyInvokedCallable;
	}

	/**
	 * @return self
	 */
	public function withType(Type $type): TypedTag
	{
		return new self($type, $this->isVariadic, $this->immediatelyInvokedCallable);
	}

	/**
	 * @return self
	 */
	public function withImmediatelyInvokedCallable(TrinaryLogic $immediatelyInvokedCallable): TypedTag
	{
		return new self($this->type, $this->isVariadic, $immediatelyInvokedCallable);
	}

}
