<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\ConditionalTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;

/** @api */
final class ConditionalType implements CompoundType, LateResolvableType
{

	use ConditionalTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(
		private Type $subject,
		private Type $target,
		Type $if,
		Type $else,
		private bool $negated,
	)
	{
		$this->if = $if;
		$this->else = $else;
	}

	public function getSubject(): Type
	{
		return $this->subject;
	}

	public function getTarget(): Type
	{
		return $this->target;
	}

	public function isNegated(): bool
	{
		return $this->negated;
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

	public function resolve(): Type
	{
		return $this->getResult();
	}

	public function getResult(): Type
	{
		if ($this->result === null) {
			$isSuperType = $this->target->isSuperTypeOf($this->subject);

			if ($isSuperType->yes()) {
				$this->result = !$this->negated ? $this->if : $this->else;
			} elseif ($isSuperType->no()) {
				$this->result = !$this->negated ? $this->else : $this->if;
			} else {
				$this->result = TypeCombinator::union($this->if, $this->else);
			}
		}

		return $this->result;
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
