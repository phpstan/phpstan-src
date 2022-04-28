<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function in_array;

class StringFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'addslashes',
			'addcslashes',
			'escapeshellarg',
			'escapeshellcmd',
			'strtoupper',
			'strtolower',
			'mb_strtoupper',
			'mb_strtolower',
			'lcfirst',
			'ucfirst',
			'ucwords',
			'htmlspecialchars',
			'htmlentities',
			'urlencode',
			'urldecode',
			'preg_quote',
			'rawurlencode',
			'rawurldecode',
			'vsprintf',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType        = $scope->getType($args[0]->value);
		$accessoryTypes = [];

		if ($argType->isNonEmptyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if ($argType->isLiteralString()->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

}
