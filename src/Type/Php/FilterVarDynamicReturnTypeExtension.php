<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function strtolower;

#[AutowiredService]
final class FilterVarDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'filter_var';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$inputType = $scope->getType($args[0]->value);
		$filterType = isset($args[1]) ? $scope->getType($args[1]->value) : null;
		$flagsType = isset($args[2]) ? $scope->getType($args[2]->value) : null;

		return $this->filterFunctionReturnTypeHelper->getType($inputType, $filterType, $flagsType);
	}

}
