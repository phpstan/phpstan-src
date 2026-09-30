<?php declare(strict_types = 1);

namespace ResultCacheE2EFileDependency;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

final class DataFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['ResultCacheE2EFileDependency\functionData', 'ResultCacheE2EFileDependency\holderData'], true);
	}

	/**
	 * @param Scope&DependencyEmitter $scope
	 */
	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		return DataFile::type($functionReflection->getName() === 'ResultCacheE2EFileDependency\functionData' ? 'function' : 'holder', $scope);
	}

}
