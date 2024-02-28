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

	private ?Type $normalizedIf = null;

	private ?Type $normalizedElse = null;

	private ?Type $subjectWithTargetIntersectedType = null;

	private ?Type $subjectWithTargetRemovedType = null;

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

		if ($isSuperType->yes()) {
			return !$this->negated ? $this->getNormalizedIf() : $this->getNormalizedElse();
		}

		if ($isSuperType->no()) {
			return !$this->negated ? $this->getNormalizedElse() : $this->getNormalizedIf();
		}

		return TypeCombinator::union(
			$this->getNormalizedIf(),
			$this->getNormalizedElse(),
		);
	}

	public function traverse(callable $cb): Type
	{
		$subject = $cb($this->subject);
		$target = $cb($this->target);
		$if = $cb($this->getNormalizedIf());
		$else = $cb($this->getNormalizedElse());

		if (
			$this->subject === $subject
			&& $this->target === $target
			&& $this->getNormalizedIf() === $if
			&& $this->getNormalizedElse() === $else
		) {
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
		$if = $cb($this->getNormalizedIf(), $right->getNormalizedIf());
		$else = $cb($this->getNormalizedElse(), $right->getNormalizedElse());

		if (
			$this->subject === $subject
			&& $this->target === $target
			&& $this->getNormalizedIf() === $if
			&& $this->getNormalizedElse() === $else
		) {
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

	private function getNormalizedIf(): Type
	{
		return $this->normalizedIf ??= TypeTraverser::map(
			$this->if,
			fn (Type $type, callable $traverse) => $type === $this->subject
				? (!$this->negated ? $this->getSubjectWithTargetIntersectedType() : $this->getSubjectWithTargetRemovedType())
				: $traverse($type),
		);
	}

	private function getNormalizedElse(): Type
	{
		return $this->normalizedElse ??= TypeTraverser::map(
			$this->else,
			fn (Type $type, callable $traverse) => $type === $this->subject
				? (!$this->negated ? $this->getSubjectWithTargetRemovedType() : $this->getSubjectWithTargetIntersectedType())
				: $traverse($type),
		);
	}

	private function getSubjectWithTargetIntersectedType(): Type
	{
		return $this->subjectWithTargetIntersectedType ??= TypeCombinator::intersect($this->subject, $this->target);
	}

	private function getSubjectWithTargetRemovedType(): Type
	{
		return $this->subjectWithTargetRemovedType ??= TypeCombinator::remove($this->subject, $this->target);
	}

}
