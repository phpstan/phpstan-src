<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;

#[AutowiredService]
final class VersionCompareFunctionDynamicThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'version_compare';
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $funcCall,
		Scope $scope,
	): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return null;
		}

		$args = $funcCall->getArgs();
		if (!isset($args[2])) {
			return null;
		}

		$operatorStrings = $scope->getType($args[2]->value)->getConstantStrings();
		if (count($operatorStrings) === 0) {
			return $functionReflection->getThrowType();
		}

		foreach ($operatorStrings as $operatorString) {
			$operatorValue = $operatorString->getValue();
			if (!in_array($operatorValue, VersionCompareFunctionDynamicReturnTypeExtension::VALID_OPERATORS, true)) {
				return $functionReflection->getThrowType();
			}
		}

		return null;
	}

}
