<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
#[RegisteredRule(level: 2)]
final class PureMethodRule implements Rule
{

	public function __construct(private FunctionPurityCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();

		return $this->check->check(
			sprintf('Method %s::%s()', $method->getDeclaringClass()->getDisplayName(), $method->getName()),
			'Method',
			$method,
			$method->getParameters(),
			$method->getReturnType(),
			$node->getImpurePoints(),
			$node->getStatementResult()->getThrowPoints(),
			$node->getStatements(),
			$method->isConstructor(),
		);
	}

}
