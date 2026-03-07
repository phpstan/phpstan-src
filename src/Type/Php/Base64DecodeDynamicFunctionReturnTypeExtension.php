<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function base64_decode;

#[AutowiredService]
final class Base64DecodeDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'base64_decode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return new StringType();
		}

		$stringArgNode = $args[0]->value;
		$constantStrings = $scope->getType($stringArgNode)->getConstantStrings();
		if ($constantStrings !== []) {
			$isValidBase64 = TrinaryLogic::lazyExtremeIdentity(
				$constantStrings,
				static function (ConstantStringType $constantString): TrinaryLogic {
					$isValid = base64_decode($constantString->getValue(), true) !== false;
					return TrinaryLogic::createFromBoolean($isValid);
				},
			);
		} else {
			$isValidBase64 = TrinaryLogic::createMaybe();
		}

		if (isset($functionCall->getArgs()[1])) {
			$strictArgNode = $functionCall->getArgs()[1]->value;
			$isStrict = $scope->getType($strictArgNode)->toBoolean()->toTrinaryLogic();
		} else {
			$isStrict = TrinaryLogic::createNo();
		}

		if ($isStrict->no() || $isValidBase64->yes()) {
			return new StringType();
		}
		if ($isStrict->yes() && $isValidBase64->no()) {
			return new ConstantBooleanType(false);
		}
		if ($isStrict->maybe() && $isValidBase64->maybe()) {
			return new BenevolentUnionType([new StringType(), new ConstantBooleanType(false)]);
		}
		return new UnionType([new StringType(), new ConstantBooleanType(false)]);
	}

}
