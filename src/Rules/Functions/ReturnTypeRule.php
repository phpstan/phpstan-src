<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use ReflectionFunction;
use function function_exists;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Return_>
 */
class ReturnTypeRule implements Rule
{

	private FunctionReturnTypeCheck $returnTypeCheck;

	private Reflector $reflector;

	public function __construct(
		FunctionReturnTypeCheck $returnTypeCheck,
		Reflector $reflector
	)
	{
		$this->returnTypeCheck = $returnTypeCheck;
		$this->reflector = $reflector;
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
			$reflection = new ReflectionFunction($function->getName());
		} else {
			try {
				$reflection = $this->reflector->reflectFunction($function->getName());
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
			$reflection !== null && $reflection->isGenerator()
		);
	}

}
