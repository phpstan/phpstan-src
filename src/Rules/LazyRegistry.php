<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Type\ExtensionClassHelper;
use function array_values;
use function spl_object_id;

#[AutowiredService(name: 'registry', as: Registry::class)]
final class LazyRegistry implements Registry
{

	public const RULE_TAG = 'phpstan.rules.rule';

	/** @var Rule[][]|null */
	private ?array $rulesByNodeType = null;

	/** @var Rule[][] */
	private array $cache = [];

	/**
	 * @param ExtensionsCollection<Rule<Node>> $rules
	 */
	public function __construct(
		#[AutowiredExtensions(of: Rule::class)]
		private ExtensionsCollection $rules,
	)
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
			$parentNodeTypes = ExtensionClassHelper::getExtensionClassNamesByRuntimeReflection($nodeType);

			$rules = [];
			$rulesFromContainer = $this->getRulesByNodeType();
			foreach ($parentNodeTypes as $parentNodeType) {
				foreach ($rulesFromContainer[$parentNodeType] ?? [] as $rule) {
					// a rule that named two ancestors of this node class is still called once
					$rules[spl_object_id($rule)] = $rule;
				}
			}

			$this->cache[$nodeType] = array_values($rules);
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
	private function getRulesByNodeType(): array
	{
		if ($this->rulesByNodeType !== null) {
			return $this->rulesByNodeType;
		}

		$rules = [];
		foreach ($this->rules->getAll() as $rule) {
			foreach ($rule instanceof MultipleNodeTypesRule ? $rule->getNodeTypes() : [$rule->getNodeType()] as $nodeType) {
				$rules[$nodeType][] = $rule;
			}
		}

		return $this->rulesByNodeType = $rules;
	}

}
