<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExtensionClassHelper;

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
		private ReflectionProvider $reflectionProvider,
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
			$parentNodeTypes = ExtensionClassHelper::getExtensionClassNames($this->reflectionProvider, $nodeType);

			$rules = [];
			$rulesFromContainer = $this->getRulesByNodeType();
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
	private function getRulesByNodeType(): array
	{
		if ($this->rulesByNodeType !== null) {
			return $this->rulesByNodeType;
		}

		$rules = [];
		foreach ($this->rules->getAll() as $rule) {
			$rules[$rule->getNodeType()][] = $rule;
		}

		return $this->rulesByNodeType = $rules;
	}

}
