<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function sprintf;

/** @api */
final class ConditionalType extends UnionType
{

	public function __construct(
		private Type $subject,
		private Type $target,
		private Type $if,
		private Type $else,
		private bool $negated,
	)
	{
		parent::__construct([$if, $else]);
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

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = [];

		foreach ([$this->subject, $this->target, $this->if, $this->else] as $type) {
			foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$subject = $cb($this->subject);
		$target = $cb($this->target);
		$if = $cb($this->if);
		$else = $cb($this->else);

		if ($this->subject === $subject && $this->target === $target && $this->if === $if && $this->else === $else) {
			return $this;
		}

		if ($if->equals($else)) {
			return $if;
		}

		$isSuperType = $target->isSuperTypeOf($subject);

		if ($isSuperType->yes()) {
			return !$this->negated ? $if : $else;
		} elseif ($isSuperType->no()) {
			return !$this->negated ? $else : $if;
		}

		$result = new ConditionalType($subject, $target, $if, $else, $this->negated);

		if ($result->containsConditionalOrTemplateParameter()) {
			return $result;
		}

		return TypeCombinator::union($if, $else);
	}

	private function containsConditionalOrTemplateParameter(): bool
	{
		$contains = false;
		TypeTraverser::map($this, function (Type $type, callable $traverse) use (&$contains): Type {
			if (($type instanceof self && $type !== $this) || $type instanceof TemplateType) {
				$contains = true;
			}

			if ($contains) {
				return $type;
			}

			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}

			return $type;
		});

		return $contains;
	}

}
