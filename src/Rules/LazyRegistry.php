<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\DependencyInjection\Container;
use function class_implements;
use function class_parents;

final class LazyRegistry implements Registry
{

	public const RULE_TAG = 'phpstan.rules.rule';

	/** @var Rule[][]|null */
	private ?array $rules = null;

	/** @var Rule[][] */
	private array $cache = [];

	public function __construct(private Container $container)
	{
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
			$rulesFromContainer = $this->getRulesFromContainer();
			foreach ($parentNodeTypes as $parentNodeType) {
				foreach ($rulesFromContainer[$parentNodeType] ?? [] as $rule) {
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

	/**
	 * @return Rule[][]
	 */
	private function getRulesFromContainer(): array
	{
		if ($this->rules !== null) {
			return $this->rules;
		}

		$rules = [];
		foreach ($this->container->getServicesByTag(self::RULE_TAG) as $rule) {
			$rules[$rule->getNodeType()][] = $rule;
		}

		return $this->rules = $rules;
	}

}
