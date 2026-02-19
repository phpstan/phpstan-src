<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<StaticMethodCallableNode>
 */
#[RegisteredRule(level: 0)]
final class StaticMethodCallableRule implements Rule
{

	public function __construct(private StaticMethodCallCheck $methodCallCheck, private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return StaticMethodCallableNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->supportsFirstClassCallables()) {
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

		return $this->methodCallCheck->check($scope, $methodNameName, $node->getClass(), $node->getName())[0];
	}

}
