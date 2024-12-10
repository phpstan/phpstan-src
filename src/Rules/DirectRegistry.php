<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use function class_implements;
use function class_parents;

/**
 * @final
 */
class DirectRegistry implements Registry
{

	/** @var Rule[][] */
	private array $rules = [];

	/** @var Rule[][] */
	private array $cache = [];

	/**
	 * @param Rule[] $rules
	 */
	public function __construct(array $rules)
	{
		foreach ($rules as $rule) {
			$this->rules[$rule->getNodeType()][] = $rule;
		}
	}

	/**
	 * @template TNodeType of Node
	 * @param class-string<TNodeType> $nodeType
	 * @return array<Rule<TNodeType>>
	 */
	public function getRules(string $nodeType): array
	{
		if (!isset($this->cache[$nodeType])) {
			$parentNodeTypes = [$nodeType] + class_parents($nodeType) + class_implements($nodeType);

			$rules = [];
			foreach ($parentNodeTypes as $parentNodeType) {
				foreach ($this->rules[$parentNodeType] ?? [] as $rule) {
					$rules[] = $rule;
				}
			}

			$this->cache[$nodeType] = $rules;
		}

		/**
		 * @var array<Rule<TNodeType>> $selectedRules
		 */
		$selectedRules = $this->cache[$nodeType];

		return $selectedRules;
	}

}
