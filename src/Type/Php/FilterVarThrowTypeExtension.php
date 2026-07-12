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
final class FilterVarThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
		private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper,
	)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return $functionReflection->getName() === 'filter_var';
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $funcCall,
		Scope $scope,
	): ?Type
	{
		if (!isset($funcCall->getArgs()[2])) {
			return null;
		}

		if (
			!$this->phpVersion->hasFilterThrowOnFailureConstant()
			|| !$this->reflectionProvider->hasConstant(new Name\FullyQualified('FILTER_THROW_ON_FAILURE'), null)
		) {
			return null;
		}

		$flagsExpr = $funcCall->getArgs()[2]->value;
		$flagsType = $scope->getType($flagsExpr);

		if (!$this->filterFunctionReturnTypeHelper->hasFlag('FILTER_THROW_ON_FAILURE', $flagsType)->no()) {
			return new ObjectType('Filter\FilterFailedException');
		}

		return null;
	}

}
