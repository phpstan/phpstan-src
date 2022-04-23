<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\ConditionalTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;

/** @api */
final class ConditionalTypeForParameter implements CompoundType
{

	use ConditionalTypeTrait;
	use NonGeneralizableTypeTrait;

	public function __construct(
		private string $parameterName,
		private Type $target,
		Type $if,
		Type $else,
		private bool $negated,
	)
	{
		$this->if = $if;
		$this->else = $else;
	}

	public function getParameterName(): string
	{
		return $this->parameterName;
	}

	public function changeParameterName(string $parameterName): self
	{
		return new self(
			$parameterName,
			$this->target,
			$this->if,
			$this->else,
			$this->negated,
		);
	}

	public function toConditional(Type $subject): Type
	{
		return new ConditionalType(
			$subject,
			$this->target,
			$this->if,
			$this->else,
			$this->negated,
		);
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

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$target = $cb($this->target);
		$if = $cb($this->if);
		$else = $cb($this->else);

		if ($this->target === $target && $this->if === $if && $this->else === $else) {
			return $this;
		}

		return new ConditionalTypeForParameter($this->parameterName, $target, $if, $else, $this->negated);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['parameterName'],
			$properties['target'],
			$properties['if'],
			$properties['else'],
			$properties['negated'],
		);
	}

}
