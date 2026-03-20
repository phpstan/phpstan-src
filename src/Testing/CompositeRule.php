<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\Rule;
use function get_class;

/**
 * Allows testing of rules which delegate work to NodeCallbackInvoker.
 *
 * @implements Rule<Node>
 *
 * @api
 */
final class CompositeRule implements Rule
{

	private DirectRegistry $registry;

	/**
	 * @template T of Node
	 * @param array<Rule<T>> $rules
	 *
	 * @api
	 */
	public function __construct(array $rules)
	{
		$this->registry = new DirectRegistry($rules);
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$errors = [];

		$nodeType = get_class($node);
		foreach ($this->registry->getRules($nodeType) as $rule) {
			foreach ($rule->processNode($node, $scope) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

}
