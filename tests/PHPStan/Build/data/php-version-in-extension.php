<?php // lint >= 8.0

declare(strict_types = 1);

namespace PhpVersionInExtension;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use PHPStan\Rules\Rule;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
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

final class InjectsPhpVersionInParameterOutExtension implements MethodParameterOutTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $this->phpVersion->getVersionId() >= 80000;
	}

	public function getParameterOutTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return null;
	}

}

final class InjectsPhpVersionInRestrictedUsageExtension implements RestrictedMethodUsageExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isRestrictedMethodUsage(ExtendedMethodReflection $methodReflection, Scope $scope): ?RestrictedUsage
	{
		if ($this->phpVersion->getVersionId() >= 80000) {
			return null;
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

/**
 * @implements Rule<Node>
 */
final class RuleWithInjectedPhpVersion implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->phpVersion->getVersionId() >= 80000) {
			return [];
		}

		return [];
	}

}
