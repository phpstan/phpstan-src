<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;

/** @api */
final class ConditionalType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(
		private Type $subject,
		private Type $target,
		private Type $if,
		private Type $else,
		private bool $negated,
	)
	{
	}

	public function getSubject(): Type
	{
		return $this->subject;
	}

	public function getTarget(): Type
	{
		return $this->target;
	}

	public function getIf(): Type
	{
		return $this->if;
	}

	public function getElse(): Type
	{
		return $this->else;
	}

	public function isNegated(): bool
	{
		return $this->negated;
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->if->isSuperTypeOf($type->if)
				->and($this->else->isSuperTypeOf($type->else));
		}

		return $this->isSuperTypeOfDefault($type);
	}

	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->subject->getReferencedClasses(),
			$this->target->getReferencedClasses(),
			$this->if->getReferencedClasses(),
			$this->else->getReferencedClasses(),
		);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return array_merge(
			$this->subject->getReferencedTemplateTypes($positionVariance),
			$this->target->getReferencedTemplateTypes($positionVariance),
			$this->if->getReferencedTemplateTypes($positionVariance),
			$this->else->getReferencedTemplateTypes($positionVariance),
		);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->subject->equals($type->subject)
			&& $this->target->equals($type->target)
			&& $this->if->equals($type->if)
			&& $this->else->equals($type->else);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'(%s %s %s ? %s : %s)',
			$this->subject->describe($level),
			$this->negated ? 'is not' : 'is',
			$this->target->describe($level),
			$this->if->describe($level),
			$this->else->describe($level),
		);
	}

	public function isResolvable(): bool
	{
		return !TypeUtils::containsTemplateType($this->subject) && !TypeUtils::containsTemplateType($this->target);
	}

	protected function getResult(): Type
	{
		$isSuperType = $this->target->isSuperTypeOf($this->subject);

		$intersectedType = TypeCombinator::intersect($this->subject, $this->target);
		$removedType = TypeCombinator::remove($this->subject, $this->target);

		$yesType = fn () => TypeTraverser::map(
			!$this->negated ? $this->if : $this->else,
			fn (Type $type, callable $traverse) => $type === $this->subject ? (!$this->negated ? $intersectedType : $removedType) : $traverse($type),
		);
		$noType = fn () => TypeTraverser::map(
			!$this->negated ? $this->else : $this->if,
			fn (Type $type, callable $traverse) => $type === $this->subject ? (!$this->negated ? $removedType : $intersectedType) : $traverse($type),
		);

		if ($isSuperType->yes()) {
			return $yesType();
		}

		if ($isSuperType->no()) {
			return $noType();
		}

		return TypeCombinator::union($yesType(), $noType());
	}

	public function traverse(callable $cb): Type
	{
		$subject = $cb($this->subject);
		$target = $cb($this->target);
		$if = $cb($this->if);
		$else = $cb($this->else);

		if ($this->subject === $subject && $this->target === $target && $this->if === $if && $this->else === $else) {
			return $this;
		}

		return new self($subject, $target, $if, $else, $this->negated);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$subject = $cb($this->subject, $right->subject);
		$target = $cb($this->target, $right->target);
		$if = $cb($this->if, $right->if);
		$else = $cb($this->else, $right->else);

		if ($this->subject === $subject && $this->target === $target && $this->if === $if && $this->else === $else) {
			return $this;
		}

		return new self($subject, $target, $if, $else, $this->negated);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConditionalTypeNode(
			$this->subject->toPhpDocNode(),
			$this->target->toPhpDocNode(),
			$this->if->toPhpDocNode(),
			$this->else->toPhpDocNode(),
			$this->negated,
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['subject'],
			$properties['target'],
			$properties['if'],
			$properties['else'],
			$properties['negated'],
		);
	}

}
