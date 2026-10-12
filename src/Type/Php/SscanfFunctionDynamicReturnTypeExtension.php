<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\Functions\PrintfHelper;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function preg_match_all;

#[AutowiredService]
final class SscanfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private PrintfHelper $printfHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sscanf', 'fscanf'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) !== 2) {
			return null;
		}

		$formatType = $scope->getType($args[1]->value)->getConstantStrings();
		if (count($formatType) !== 1) {
			return null;
		}
		$formatType = $formatType[0];

		$formatValue = $formatType->getValue();
		$placeholderCount = $this->printfHelper->getScanfPlaceholdersCount($formatValue);
		if ($placeholderCount === null) {
			return $this->phpVersion->throwsValueErrorForInternalFunctions() ? new NeverType() : new NullType();
		}

		if ($placeholderCount === 0) {
			return TypeCombinator::addNull(
				ConstantArrayTypeBuilder::createEmpty()->getArray(),
			);
		}

		if (preg_match_all('/%(\d*)(\[[^\]]+\]|[cdeEfosux]{1})/', $formatValue, $matches) !== $placeholderCount) {
			$safeBuilder = ConstantArrayTypeBuilder::createEmpty();
			for ($i = 0; $i < $placeholderCount; ++$i) {
				$safeBuilder->setOffsetValueType(
					new ConstantIntegerType($i),
					TypeCombinator::union(
						new FloatType(),
						new IntegerType(),
						new IntersectionType([
							new StringType(),
							new AccessoryNonEmptyStringType(),
						]),
						new NullType(),
					),
				);
			}
			return TypeCombinator::addNull($safeBuilder->getArray());
		}

		$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
		for ($i = 0; $i < count($matches[0]); $i++) {
			$length = $matches[1][$i];
			$specifier = $matches[2][$i];

			$type = new StringType();
			if ($length !== '') {
				if (((int) $length) > 1) {
					$type = new IntersectionType([
						$type,
						new AccessoryNonFalsyStringType(),
					]);
				} else {
					$type = new IntersectionType([
						$type,
						new AccessoryNonEmptyStringType(),
					]);
				}
			}

			if (in_array($specifier, ['d', 'o', 'u', 'x'], true)) {
				$type = new IntegerType();
			}

			if (in_array($specifier, ['e', 'E', 'f'], true)) {
				$type = new FloatType();
			}

			$type = TypeCombinator::addNull($type);
			$arrayBuilder->setOffsetValueType(new ConstantIntegerType($i), $type);
		}

		return TypeCombinator::addNull($arrayBuilder->getArray());
	}

}
