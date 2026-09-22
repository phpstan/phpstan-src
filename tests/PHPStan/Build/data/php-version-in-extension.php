<?php // lint >= 8.0

declare(strict_types = 1);

namespace PhpVersionInExtension;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;

final class InjectsPhpVersion implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'foo' && $this->phpVersion->getVersionId() >= 80000;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		return null;
	}

}

final class ReadsPhpVersionFromScope implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private string $functionName)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === $this->functionName;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if ($scope->getPhpVersion()->throwsValueErrorForInternalFunctions()->yes()) {
			return new NullType();
		}

		return null;
	}

}

final class OperatorExtensionWithoutScope implements OperatorTypeSpecifyingExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		return $this->phpVersion->getVersionId() >= 80400;
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		return new NullType();
	}

}
