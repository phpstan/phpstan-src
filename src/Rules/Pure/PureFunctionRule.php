<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(level: 2)]
final class PureFunctionRule implements Rule
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
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$function = $node->getFunctionReflection();
		$description = sprintf('Function %s()', $function->getName());

		$errors = $this->check->check(
			$scope,
			$description,
			'Function',
			$function,
			$function->getParameters(),
			$function->getReturnType(),
			$node->getImpurePoints(),
			$node->getStatementResult()->getThrowPoints(),
			$node->getStatements(),
			false,
		);

		if (
			$this->checkMissingPurePhpDocTag
			&& $this->check->canBeMarkedPure(
				$function,
				$function->getParameters(),
				$function->getReturnType(),
				$node->getImpurePoints(),
				$node->getStatementResult()->getThrowPoints(),
				$node->isGenerator(),
				$node->returnsByRef(),
				false,
			)
		) {
			$errors[] = $this->check->createMissingPureTagError($description, 'Function', $node->getFunction());
		}

		return $errors;
	}

}
