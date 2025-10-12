<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Registry;
use PHPStan\Rules\Rule;
use function get_class;

/**
 * @implements Rule<Node>
 */
final class DelayedRule implements Rule
{

	private Registry $registry;

	/** @var list<IdentifierRuleError> */
	private array $errors = [];

	/**
	 * @param Rule<covariant Node> $rule
	 */
	public function __construct(Rule $rule)
	{
		$this->registry = new DirectRegistry([$rule]);
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function getDelayedErrors(): array
	{
		return $this->errors;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker $scope): array
	{
		$nodeType = get_class($node);
		foreach ($this->registry->getRules($nodeType) as $rule) {
			foreach ($rule->processNode($node, $scope) as $error) {
				$this->errors[] = $error;
			}
		}

		return [];
	}

}
