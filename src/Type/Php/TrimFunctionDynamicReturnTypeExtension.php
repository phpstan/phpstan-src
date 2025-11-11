<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function preg_match;
use function rtrim;
use function trim;

#[AutowiredService]
final class TrimFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['trim', 'rtrim'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$stringType = $scope->getType($args[0]->value);
		$accessory = [];
		$defaultType = new StringType();
		if ($stringType->isLowercaseString()->yes()) {
			$accessory[] = new AccessoryLowercaseStringType();
		}
		if ($stringType->isUppercaseString()->yes()) {
			$accessory[] = new AccessoryUppercaseStringType();
		}
		if (count($accessory) > 0) {
			$accessory[] = new StringType();
			$defaultType = new IntersectionType($accessory);
		}

		if (count($functionCall->getArgs()) !== 2) {
			return $defaultType;
		}

		$trimChars = $scope->getType($functionCall->getArgs()[1]->value);

		$trimConstantStrings = $trimChars->getConstantStrings();
		if (count($trimConstantStrings) > 0) {
			$result = [];
			$stringConstantStrings = $stringType->getConstantStrings();
			$functionName = $functionReflection->getName();

			foreach ($trimConstantStrings as $trimConstantString) {
				if (count($stringConstantStrings) > 0) {
					foreach ($stringConstantStrings as $stringConstantString) {
						$result[] = new ConstantStringType(
							$functionName === 'rtrim'
								? rtrim($stringConstantString->getValue(), $trimConstantString->getValue())
								: trim($stringConstantString->getValue(), $trimConstantString->getValue()),
							true,
						);
					}
				} elseif (preg_match('/\d/', $trimConstantString->getValue()) === 0 && $stringType->isNumericString()->yes()) {
					$result[] = new IntersectionType([new StringType(), new AccessoryNumericStringType()]);
				} else {
					return $defaultType;
				}
			}

			return TypeCombinator::union(...$result);
		}

		return $defaultType;
	}

}
