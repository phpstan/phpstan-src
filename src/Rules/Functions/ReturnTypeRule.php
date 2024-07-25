<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Return_>
 */
final class ReturnTypeRule implements Rule
{

	public function __construct(
		private FunctionReturnTypeCheck $returnTypeCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Return_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getFunction() === null) {
			return [];
		}

		if ($scope->isInAnonymousFunction()) {
			return [];
		}

		$function = $scope->getFunction();
		if (
			!($function instanceof PhpFunctionFromParserNodeReflection)
			|| $function instanceof PhpMethodFromParserNodeReflection
		) {
			return [];
		}

		return $this->returnTypeCheck->checkReturnType(
			$scope,
			ParametersAcceptorSelector::selectSingle($function->getVariants())->getReturnType(),
			$node->expr,
			$node,
			sprintf(
				'Function %s() should return %%s but empty return statement found.',
				$function->getName(),
			),
			sprintf(
				'Function %s() with return type void returns %%s but should not return anything.',
				$function->getName(),
			),
			sprintf(
				'Function %s() should return %%s but returns %%s.',
				$function->getName(),
			),
			sprintf(
				'Function %s() should never return but return statement found.',
				$function->getName(),
			),
			$function->isGenerator(),
		);
	}

}
