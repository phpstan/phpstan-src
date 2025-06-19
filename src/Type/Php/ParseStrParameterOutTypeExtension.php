<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;
use function in_array;
use function strtolower;

#[AutowiredService]
final class ParseStrParameterOutTypeExtension implements FunctionParameterOutTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return in_array(strtolower($functionReflection->getName()), ['parse_str', 'mb_parse_str'], true)
			&& $parameter->getName() === 'result';
	}

	public function getParameterOutTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$stringType = $scope->getType($args[0]->value);
		$accessory = [];
		if ($stringType->isLowercaseString()->yes()) {
			$accessory[] = new AccessoryLowercaseStringType();
		}
		if ($stringType->isUppercaseString()->yes()) {
			$accessory[] = new AccessoryUppercaseStringType();
		}
		if (count($accessory) > 0) {
			$accessory[] = new StringType();
			$valueType = new IntersectionType($accessory);
		} else {
			$valueType = new StringType();
		}

		return new ArrayType(
			new UnionType([new StringType(), new IntegerType()]),
			new UnionType([new ArrayType(new MixedType(), new MixedType(true)), $valueType]),
		);
	}

}
