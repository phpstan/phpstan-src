<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeStrategy;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;

/**
 * A reference to the template-type subject of a ConditionalType inside one of its
 * branches, narrowed by what the branch knows: `subject & target` where the condition
 * holds, `subject ~ target` where it does not.
 *
 * The narrowing is computed from the template type right away, and this type behaves
 * exactly like that narrowed template type as long as the subject stays unresolved.
 * The narrowing is recomputed from whatever the subject resolves to: it is
 * `(stdClass|false) ~ false` that gives stdClass for the else branch of
 * `(T is false ? never : T)` - resolving `T~false` on its own only hands back the type
 * T resolved to - and `S ~ U` with a template type as the target cannot be narrowed
 * at all before both resolve. The resolvers of template types therefore traverse into
 * it instead of substituting it.
 */
final class NarrowedSubjectType implements TemplateType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	private function __construct(
		private TemplateType $subject,
		private Type $target,
		private bool $conditionHolds,
		private TemplateType $narrowed,
	)
	{
	}

	public static function create(TemplateType $subject, Type $target, bool $conditionHolds): Type
	{
		$narrowed = self::narrow($subject, $target, $conditionHolds);
		if (!$narrowed instanceof TemplateType || $subject->isArgument()) {
			// nothing to recompute once the template type is gone from the narrowing,
			// and seen from inside its own function it never resolves
			return $narrowed;
		}

		return new self($subject, $target, $conditionHolds, $narrowed);
	}

	private static function narrow(Type $subject, Type $target, bool $conditionHolds): Type
	{
		if ($conditionHolds) {
			return TypeCombinator::intersect($subject, $target);
		}

		return TypeCombinator::remove($subject, $target);
	}

	public function getName(): string
	{
		return $this->narrowed->getName();
	}

	public function getScope(): TemplateTypeScope
	{
		return $this->narrowed->getScope();
	}

	public function getBound(): Type
	{
		return $this->narrowed->getBound();
	}

	public function getDefault(): ?Type
	{
		return $this->narrowed->getDefault();
	}

	public function toArgument(): TemplateType
	{
		return $this->narrowed->toArgument();
	}

	public function isArgument(): bool
	{
		return $this->narrowed->isArgument();
	}

	public function isValidVariance(Type $a, Type $b): IsSuperTypeOfResult
	{
		return $this->narrowed->isValidVariance($a, $b);
	}

	public function getVariance(): TemplateTypeVariance
	{
		return $this->narrowed->getVariance();
	}

	public function getStrategy(): TemplateTypeStrategy
	{
		return $this->narrowed->getStrategy();
	}

	public function getReferencedClasses(): array
	{
		return $this->narrowed->getReferencedClasses();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return $this->narrowed->getReferencedTemplateTypes($positionVariance);
	}

	public function equals(Type $type): bool
	{
		return $this->narrowed->equals($type instanceof self ? $type->narrowed : $type);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->narrowed->describe($level);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		return $this->narrowed->isSuperTypeOf($type);
	}

	public function isResolvable(): bool
	{
		return false;
	}

	protected function getResult(): Type
	{
		return $this->narrowed;
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		return $this->rebuild($cb($this->subject), $cb($this->target), $cb);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		return $this->rebuild(
			$cb($this->subject, $right->subject),
			$cb($this->target, $right->target),
			static fn (Type $type): Type => $cb($type, $right->narrowed),
		);
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	private function rebuild(Type $subject, Type $target, callable $cb): Type
	{
		if (
			!$subject instanceof TemplateType
			|| $subject->getName() !== $this->subject->getName()
			|| !$subject->getScope()->equals($this->subject->getScope())
		) {
			// the subject resolved to another type: narrow that one instead
			return self::narrow($subject, $target, $this->conditionHolds);
		}

		// still the same template type, at most narrowed by an enclosing conditional
		// type or turned into an argument: the narrowing stays as computed

		$narrowed = $cb($this->narrowed);
		if (!$narrowed instanceof TemplateType || $subject->isArgument()) {
			return $narrowed;
		}

		if ($subject === $this->subject && $target === $this->target && $narrowed === $this->narrowed) {
			return $this;
		}

		return new self($subject, $target, $this->conditionHolds, $narrowed);
	}

	public function toPhpDocNode(): TypeNode
	{
		return $this->narrowed->toPhpDocNode();
	}

}
