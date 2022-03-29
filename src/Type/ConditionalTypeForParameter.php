<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Generic\TemplateTypeVariance;
use function sprintf;

/** @api */
final class ConditionalTypeForParameter extends UnionType
{

	public function __construct(
		private string $parameterName,
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
			$this->parameterName,
			$this->negated ? 'is not' : 'is',
			$this->target->describe($level),
			$this->if->describe($level),
			$this->else->describe($level),
		);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = [];

		foreach ([$this->target, $this->if, $this->else] as $type) {
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
		$target = $cb($this->target);
		$if = $cb($this->if);
		$else = $cb($this->else);

		if ($this->target === $target && $this->if === $if && $this->else === $else) {
			return $this;
		}

		return new ConditionalTypeForParameter($this->parameterName, $target, $if, $else, $this->negated);
	}

}
