<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

/** @api */
final class AssertTag implements TypedTag
{

	public function __construct(private Type $type, private Expr $parameter, private bool $negated)
	{
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getParameter(): Expr
	{
		return $this->parameter;
	}

	public function isNegated(): bool
	{
		return $this->negated;
	}

	/**
	 * @return static
	 */
	public function withType(Type $type): TypedTag
	{
		return new self($type, $this->parameter, $this->negated);
	}

	public function withParameter(Expr $parameter): self
	{
		return new self($this->type, $parameter, $this->negated);
	}

}
