<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\FunctionReturnTypeCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Return_>
 */
class ReturnTypeRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\FunctionReturnTypeCheck $returnTypeCheck;

	private FunctionReflector $functionReflector;

	public function __construct(
		FunctionReturnTypeCheck $returnTypeCheck,
		FunctionReflector $functionReflector
	)
	{
		$this->returnTypeCheck = $returnTypeCheck;
		$this->functionReflector = $functionReflector;
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

		$reflection = null;
		if (function_exists($function->getName())) {
			$reflection = new \ReflectionFunction($function->getName());
		} else {
			try {
				$reflection = $this->functionReflector->reflect($function->getName());
			} catch (IdentifierNotFound $e) {
				// pass
			}
		}

		return $this->returnTypeCheck->checkReturnType(
			$scope,
			ParametersAcceptorSelector::selectSingle($function->getVariants())->getReturnType(),
			$node->expr,
			$node,
			sprintf(
				'Function %s() should return %%s but empty return statement found.',
				$function->getName()
			),
			sprintf(
				'Function %s() with return type void returns %%s but should not return anything.',
				$function->getName()
			),
			sprintf(
				'Function %s() should return %%s but returns %%s.',
				$function->getName()
			),
			sprintf(
				'Function %s() should never return but return statement found.',
				$function->getName()
			),
			sprintf(
				'Function %s() should never return an iterable directly when already using yield.',
				$function->getName()
			),
			$reflection !== null && $reflection->isGenerator()
		);
	}

}
