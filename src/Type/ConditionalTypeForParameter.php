<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;

/** @api */
final class ConditionalTypeForParameter implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	private ?TemplateType $parameterTemplateType = null;

	private ?Type $normalizedIf = null;

	private ?Type $normalizedElse = null;

	public function __construct(
		private string $parameterName,
		private Type $target,
		private Type $if,
		private Type $else,
		private bool $negated,
	)
	{
	}

	public function getParameterName(): string
	{
		return $this->parameterName;
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

	public function changeParameterName(string $parameterName): self
	{
		$type = new self(
			$parameterName,
			$this->target,
			$this->if,
			$this->else,
			$this->negated,
		);
		$type->parameterTemplateType = $this->parameterTemplateType;

		return $type;
	}

	/**
	 * Narrows the references to the template type the parameter is declared with along with
	 * the parameter: `@param T $param` makes `($param is X ? A : B)` narrow T to `T & X` in A
	 * and to `T ~ X` in B, the way `(T is X ? A : B)` does (see NarrowedSubjectType). Only
	 * sound when nothing but the parameter binds T.
	 */
	public function narrowTemplateType(TemplateType $templateType): self
	{
		$type = new self(
			$this->parameterName,
			$this->target,
			$this->if,
			$this->else,
			$this->negated,
		);
		$type->parameterTemplateType = $templateType;

		return $type;
	}

	/**
	 * Replaces every ConditionalTypeForParameter inside $type with the ConditionalType on the
	 * subject its parameter resolves to. $getSubjectType is called with the parameter name
	 * including the leading `$`; returning null leaves that conditional unresolved.
	 *
	 * Shared by everything that resolves a declared conditional type against concrete
	 * subjects: ResolvedFunctionVariant (`@return`, `@param`, `@param-out`,
	 * `@param-closure-this`), TypeSpecifier (`@phpstan-assert`) and ConditionalTypeResolver
	 * (`@throws`, `@phpstan-self-out`).
	 *
	 * @param callable(string): ?Type $getSubjectType
	 */
	public static function resolveInType(Type $type, callable $getSubjectType): Type
	{
		if (!$type->hasTemplateOrLateResolvableType()) {
			return $type;
		}

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($getSubjectType): Type {
			if ($type instanceof self) {
				$subjectType = $getSubjectType($type->getParameterName());
				if ($subjectType !== null) {
					// Resolve the branches first, then convert — avoids infinite loop when
					// the subject contains a ConditionalTypeForParameter with a colliding parameter name.
					return $type->resolveWithSubject($subjectType, $getSubjectType);
				}
			}

			return $traverse($type);
		});
	}

	/**
	 * A condition on the same parameter nested in a branch sees the subject as the branch
	 * knows it: `subject & target` where this condition holds, `subject ~ target` where it
	 * does not. `($key is Model ? A : ($key is Arrayable ? B : C))` asks whether the
	 * non-Model part of the argument is Arrayable.
	 *
	 * @param callable(string): ?Type $getSubjectType
	 */
	private function resolveWithSubject(Type $subject, callable $getSubjectType): Type
	{
		$target = self::resolveInType($this->target, $getSubjectType);
		$getNarrowedSubjectType = fn (bool $conditionHolds): callable => fn (string $parameterName): ?Type => $parameterName === $this->parameterName
			? ($conditionHolds ? TypeCombinator::intersect($subject, $target) : TypeCombinator::remove($subject, $target))
			: $getSubjectType($parameterName);

		return new ConditionalType(
			$subject,
			$target,
			self::resolveInType($this->getNormalizedIf(), $getNarrowedSubjectType(!$this->negated)),
			self::resolveInType($this->getNormalizedElse(), $getNarrowedSubjectType($this->negated)),
			$this->negated,
		);
	}

	public function toConditional(Type $subject): Type
	{
		return new ConditionalType(
			$subject,
			$this->target,
			$this->getNormalizedIf(),
			$this->getNormalizedElse(),
			$this->negated,
		);
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
			$this->target->getReferencedClasses(),
			$this->if->getReferencedClasses(),
			$this->else->getReferencedClasses(),
		);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return array_merge(
			$this->target->getReferencedTemplateTypes($positionVariance),
			$this->if->getReferencedTemplateTypes($positionVariance),
			$this->else->getReferencedTemplateTypes($positionVariance),
		);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->parameterName === $type->parameterName
			&& $this->target->equals($type->target)
			&& $this->if->equals($type->if)
			&& $this->else->equals($type->else);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf(
			'(%s %s %s ? %s : %s)',
			$this->parameterName,
			$this->negated ? 'is not' : 'is',
			$this->target->describe($level),
			$this->if->describe($level),
			$this->else->describe($level),
		);
	}

	public function isResolvable(): bool
	{
		return false;
	}

	protected function getResult(): Type
	{
		return TypeCombinator::union($this->getNormalizedIf(), $this->getNormalizedElse());
	}

	public function traverse(callable $cb): Type
	{
		$target = $cb($this->target);
		$if = $cb($this->getNormalizedIf());
		$else = $cb($this->getNormalizedElse());

		if (
			$this->target === $target
			&& $this->getNormalizedIf() === $if
			&& $this->getNormalizedElse() === $else
		) {
			return $this;
		}

		return new self($this->parameterName, $target, $if, $else, $this->negated);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		$target = $cb($this->target, $right->target);
		$if = $cb($this->getNormalizedIf(), $right->getNormalizedIf());
		$else = $cb($this->getNormalizedElse(), $right->getNormalizedElse());

		if (
			$this->target === $target
			&& $this->getNormalizedIf() === $if
			&& $this->getNormalizedElse() === $else
		) {
			return $this;
		}

		return new self($this->parameterName, $target, $if, $else, $this->negated);
	}

	private function getNormalizedIf(): Type
	{
		return $this->normalizedIf ??= $this->parameterTemplateType === null
			? $this->if
			: NarrowedSubjectType::narrowReferences($this->if, $this->parameterTemplateType, $this->target, !$this->negated);
	}

	private function getNormalizedElse(): Type
	{
		return $this->normalizedElse ??= $this->parameterTemplateType === null
			? $this->else
			: NarrowedSubjectType::narrowReferences($this->else, $this->parameterTemplateType, $this->target, $this->negated);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConditionalTypeForParameterNode(
			$this->parameterName,
			$this->target->toPhpDocNode(),
			$this->if->toPhpDocNode(),
			$this->else->toPhpDocNode(),
			$this->negated,
		);
	}

}
