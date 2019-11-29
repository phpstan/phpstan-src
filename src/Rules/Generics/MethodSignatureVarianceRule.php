<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
class MethodSignatureVarianceRule implements Rule
{

	/** @var \PHPStan\Rules\Generics\VarianceCheck */
	private $varianceCheck;

	public function __construct(VarianceCheck $varianceCheck)
	{
		$this->varianceCheck = $varianceCheck;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();
		$className = $classReflection->getDisplayName();
		$methodName = $node->name->toString();
		$method = $classReflection->getNativeMethod($methodName);

		return $this->varianceCheck->checkParametersAcceptor(
			ParametersAcceptorSelector::selectSingle($method->getVariants()),
			sprintf('in parameter %%s of method %s::%s()', $className, $methodName),
			sprintf('in return type of method %s::%s()', $className, $methodName),
			$methodName === '__construct'
		);
	}

}
