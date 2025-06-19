<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class TooWideMethodParameterOutTypeRule implements Rule
{

	public function __construct(
		private TooWideParameterOutTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$inMethod = $node->getMethodReflection();

		return $this->check->check(
			$node->getExecutionEnds(),
			$node->getReturnStatements(),
			$inMethod->getParameters(),
			sprintf('Method %s::%s()', $inMethod->getDeclaringClass()->getDisplayName(), $inMethod->getName()),
		);
	}

}
