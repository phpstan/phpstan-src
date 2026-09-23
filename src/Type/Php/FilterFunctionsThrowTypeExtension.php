<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

#[AutowiredService]
final class FilterFunctionsThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
		private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper,
		private FilterFunctionFlagsHelper $filterFunctionFlagsHelper,
	)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return $this->filterFunctionFlagsHelper->isSupported($functionReflection);
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $funcCall,
		Scope $scope,
	): ?Type
	{
		if (
			!$this->phpVersion->hasFilterThrowOnFailureConstant()
			|| !$this->reflectionProvider->hasConstant(new Name\FullyQualified('FILTER_THROW_ON_FAILURE'), null)
		) {
			return null;
		}

		foreach ($this->filterFunctionFlagsHelper->getFlagsTypes($functionReflection, $funcCall, $scope) as $flagsType) {
			if ($this->filterFunctionReturnTypeHelper->hasFlag('FILTER_THROW_ON_FAILURE', $flagsType)->no()) {
				continue;
			}

			return new ObjectType('Filter\FilterFailedException');
		}

		return null;
	}

}
