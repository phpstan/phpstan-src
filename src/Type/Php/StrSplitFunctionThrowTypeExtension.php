<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function count;

/**
 * str_split() throws ValueError only when $length is less than 1.
 */
#[AutowiredService]
final class StrSplitFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_split';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if ($scope->getPhpVersion()->throwsValueErrorForInternalFunctions()->no()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) < 2) {
			return new VoidType();
		}

		if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($scope->getNativeType($args[1]->value))->yes()) {
			return new VoidType();
		}

		return $functionReflection->getThrowType();
	}

}
