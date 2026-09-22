<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class HighlightStringDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'highlight_string';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$doesNotReturnFalse = $scope->getPhpVersion()->highlightStringDoesNotReturnFalse();
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return PhpVersions::pickType($doesNotReturnFalse, new ConstantBooleanType(true), new BooleanType());
		}

		$returnType = $scope->getType($args[1]->value);
		if ($returnType->isTrue()->yes()) {
			return new StringType();
		}

		return PhpVersions::pickType($doesNotReturnFalse, new ConstantBooleanType(true), new BooleanType());
	}

}
