<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\FunctionReturnTypeCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Return_>
 */
class ReturnTypeRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\FunctionReturnTypeCheck $returnTypeCheck;

	public function __construct(FunctionReturnTypeCheck $returnTypeCheck)
	{
		$this->returnTypeCheck = $returnTypeCheck;
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

		$method = $scope->getFunction();
		if (!($method instanceof MethodReflection)) {
			return [];
		}

		$reflection = null;
		if ($method->getDeclaringClass()->getNativeReflection()->hasMethod($method->getName())) {
			$reflection = $method->getDeclaringClass()->getNativeReflection()->getMethod($method->getName());
		}

		return $this->returnTypeCheck->checkReturnType(
			$scope,
			ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType(),
			$node->expr,
			$node,
			sprintf(
				'Method %s::%s() should return %%s but empty return statement found.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName()
			),
			sprintf(
				'Method %s::%s() with return type void returns %%s but should not return anything.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName()
			),
			sprintf(
				'Method %s::%s() should return %%s but returns %%s.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName()
			),
			sprintf(
				'Method %s::%s() should never return but return statement found.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName()
			),
			sprintf(
				'Method %s::%s() should never return an iterable directly when already using yield.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName()
			),
			$reflection !== null && $reflection->isGenerator()
		);
	}

}
