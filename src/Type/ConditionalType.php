<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateType;
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

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
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
		if (!TypeUtils::containsTemplateType($this->subject) && !TypeUtils::containsTemplateType($this->target)) {
			return true;
		}

		$isSuperType = $this->target->isSuperTypeOf($this->subject);

		return $isSuperType->yes() || $isSuperType->no();
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

	private function getNormalizedIf(): Type
	{
		return $this->normalizedIf ??= $this->narrowSubjectIn($this->if, !$this->negated);
	}

	private function getNormalizedElse(): Type
	{
		return $this->normalizedElse ??= $this->narrowSubjectIn($this->else, $this->negated);
	}

	/**
	 * Replaces the references to the subject in a branch with what the branch knows about
	 * it: `subject & target` where the condition holds, `subject ~ target` where it does not
	 * (see NarrowedSubjectType).
	 *
	 * A branch can only reference the subject while it is a template type. Once the subject
	 * resolves to a concrete type, an equal type inside the branch is not a mention of it -
	 * the `mixed` value type of an `array` branch has nothing to do with a `mixed` subject -
	 * so nothing is narrowed here; the references narrowed earlier follow the resolution on
	 * their own. Matching by identity instead would still hit such unrelated types whenever
	 * they happen to share an instance, which interned types always do.
	 */
	private function narrowSubjectIn(Type $branch, bool $conditionHolds): Type
	{
		if (!$this->subject instanceof TemplateType) {
			return $branch;
		}

		return NarrowedSubjectType::narrowReferences($branch, $this->subject, $this->target, $conditionHolds);
	}

}
