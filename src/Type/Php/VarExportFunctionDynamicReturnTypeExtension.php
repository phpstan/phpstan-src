<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

class VarExportFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(
			$functionReflection->getName(),
			[
				'var_export',
				'highlight_file',
				'show_source', // alias of highlight_file
				'highlight_string',
				'print_r',
			],
			true,
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, Node\Expr\FuncCall $functionCall, Scope $scope): Type
	{
		if ($functionReflection->getName() === 'var_export') {
			$fallbackReturnType = new NullType();
		} elseif ($functionReflection->getName() === 'print_r') {
			$fallbackReturnType = new ConstantBooleanType(true);
		} else {
			$fallbackReturnType = new BooleanType();
		}

		if (count($functionCall->getArgs()) < 1) {
			return TypeCombinator::union(
				new StringType(),
				$fallbackReturnType,
			);
		}

		if (count($functionCall->getArgs()) < 2) {
			return $fallbackReturnType;
		}

		$returnArgumentType = $scope->getType($functionCall->getArgs()[1]->value);
		if ((new ConstantBooleanType(true))->isSuperTypeOf($returnArgumentType)->yes()) {
			return new StringType();
		}

		return $fallbackReturnType;
	}

}
