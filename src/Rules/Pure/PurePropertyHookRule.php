<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
#[RegisteredRule(level: 2)]
final class PurePropertyHookRule implements Rule
{

	public function __construct(private FunctionPurityCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return PropertyHookReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$hookReflection = $node->getHookReflection();
		$hookName = $hookReflection->getPropertyHookName();
		if ($hookName === null) {
			throw new ShouldNotHappenException();
		}

		return $this->check->check(
			$scope,
			sprintf(
				'%s hook for property %s::$%s',
				ucfirst($hookName),
				$hookReflection->getDeclaringClass()->getDisplayName(),
				$hookReflection->getHookedPropertyName(),
			),
			'PropertyHook',
			$hookReflection,
			$hookReflection->getParameters(),
			$hookReflection->getReturnType(),
			$node->getImpurePoints(),
			$node->getStatementResult()->getThrowPoints(),
			$node->getPropertyHookNode()->getStmts() ?? [],
			false,
		);
	}

}
