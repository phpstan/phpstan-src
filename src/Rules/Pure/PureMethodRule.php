<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
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

	public function __construct(
		private FunctionPurityCheck $check,
		#[AutowiredParameter]
		private bool $checkMissingPurePhpDocTag,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		$description = sprintf('Method %s::%s()', $method->getDeclaringClass()->getDisplayName(), $method->getName());

		$errors = $this->check->check(
			$scope,
			$description,
			'Method',
			$method,
			$method->getParameters(),
			$method->getReturnType(),
			$node->getImpurePoints(),
			$node->getStatementResult()->getThrowPoints(),
			$node->getStatements(),
			$method->isConstructor(),
		);

		// a trait method is analysed once per using class, and its PHPDoc is shared by all of them
		if (
			$this->checkMissingPurePhpDocTag
			&& !$scope->isInTrait()
			&& $this->check->canBeMarkedPure(
				$method,
				$method->getParameters(),
				$method->getReturnType(),
				$node->getImpurePoints(),
				$node->getStatementResult()->getThrowPoints(),
				$node->isGenerator(),
				$node->returnsByRef(),
				$method->isConstructor(),
			)
		) {
			$errors[] = $this->check->createMissingPureTagError($description, 'Method', $node->getClassMethod());
		}

		return $errors;
	}

}
