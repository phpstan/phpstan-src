<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCallableNode>
 */
#[RegisteredRule(level: 0)]
final class MethodCallableRule implements Rule
{

	public function __construct(private MethodCallCheck $methodCallCheck)
	{
	}

	public function getNodeType(): string
	{
		return MethodCallableNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->getPhpVersion()->supportsFirstClassCallables()->yes()) {
			return [
				RuleErrorBuilder::message('First-class callables are supported only on PHP 8.1 and later.')
					->nonIgnorable()
					->identifier('callable.notSupported')
					->build(),
			];
		}

		$methodName = $node->getName();
		if (!$methodName instanceof Node\Identifier) {
			return [];
		}

		$methodNameName = $methodName->toString();

		return $this->methodCallCheck->check($scope, $methodNameName, $node->getVar(), $node->getName())[0];
	}

}
