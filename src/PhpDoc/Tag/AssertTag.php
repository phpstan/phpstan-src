<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

/** @api */
final class AssertTag implements TypedTag
{

	public const NULL = '';
	public const IF_TRUE = 'true';
	public const IF_FALSE = 'false';

	/**
	 * @param self::NULL|self::IF_TRUE|self::IF_FALSE $if
	 */
	public function __construct(private string $if, private Type $type, private Expr $parameter, private bool $negated)
	{
	}

	/**
	 * @return self::NULL|self::IF_TRUE|self::IF_FALSE
	 */
	public function getIf(): string
	{
		return $this->if;
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
		return new self($this->if, $type, $this->parameter, $this->negated);
	}

	public function withParameter(Expr $parameter): self
	{
		return new self($this->if, $this->type, $parameter, $this->negated);
	}

}
