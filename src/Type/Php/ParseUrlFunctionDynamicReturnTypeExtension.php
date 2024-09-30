<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ValueError;
use function count;
use function parse_url;
use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_PASS;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;
use const PHP_URL_USER;

final class ParseUrlFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var array<int,Type>|null */
	private ?array $componentTypesPairedConstants = null;

	/** @var array<string,Type>|null */
	private ?array $componentTypesPairedStrings = null;

	/** @var array<int,Type>|null */
	private ?array $componentTypesPairedConstantsForLowercaseString = null;

	/** @var array<string,Type>|null */
	private ?array $componentTypesPairedStringsForLowercaseString = null;

	private ?Type $allComponentsTogetherType = null;

	private ?Type $allComponentsTogetherTypeForLowercaseString = null;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'parse_url';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$this->cacheReturnTypes();

		$urlType = $scope->getType($functionCall->getArgs()[0]->value);
		if (count($functionCall->getArgs()) > 1) {
			$componentType = $scope->getType($functionCall->getArgs()[1]->value);

			if (!$componentType->isConstantValue()->yes()) {
				return $this->createAllComponentsReturnType($urlType->isLowercaseString()->yes());
			}

			$componentType = $componentType->toInteger();
			if (!$componentType instanceof ConstantIntegerType) {
				return $this->createAllComponentsReturnType($urlType->isLowercaseString()->yes());
			}
		} else {
			$componentType = new ConstantIntegerType(-1);
		}

		if (count($urlType->getConstantStrings()) > 0) {
			$types = [];
			foreach ($urlType->getConstantStrings() as $constantString) {
				try {
					$result = @parse_url($constantString->getValue(), $componentType->getValue());
				} catch (ValueError) {
					$types[] = new ConstantBooleanType(false);
					continue;
				}

				$types[] = $scope->getTypeFromValue($result);
			}

			return TypeCombinator::union(...$types);
		}

		if ($componentType->getValue() === -1) {
			return TypeCombinator::union(
				$this->createComponentsArray($urlType->isLowercaseString()->yes()),
				new ConstantBooleanType(false),
			);
		}

		if ($urlType->isLowercaseString()->yes()) {
			return $this->componentTypesPairedConstantsForLowercaseString[$componentType->getValue()] ?? new ConstantBooleanType(false);
		}

		return $this->componentTypesPairedConstants[$componentType->getValue()] ?? new ConstantBooleanType(false);
	}

	private function createAllComponentsReturnType(bool $urlIsLowercase): Type
	{
		if ($urlIsLowercase) {
			if ($this->allComponentsTogetherTypeForLowercaseString === null) {
				$returnTypes = [
					new ConstantBooleanType(false),
					new NullType(),
					IntegerRangeType::fromInterval(0, 65535),
					new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]),
					$this->createComponentsArray(true),
				];

				$this->allComponentsTogetherTypeForLowercaseString = TypeCombinator::union(...$returnTypes);
			}

			return $this->allComponentsTogetherTypeForLowercaseString;
		}

		if ($this->allComponentsTogetherType === null) {
			$returnTypes = [
				new ConstantBooleanType(false),
				new NullType(),
				IntegerRangeType::fromInterval(0, 65535),
				new StringType(),
				$this->createComponentsArray(false),
			];

			$this->allComponentsTogetherType = TypeCombinator::union(...$returnTypes);
		}

		return $this->allComponentsTogetherType;
	}

	private function createComponentsArray(bool $urlIsLowercase): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();

		if ($urlIsLowercase) {
			if ($this->componentTypesPairedStringsForLowercaseString === null) {
				throw new ShouldNotHappenException();
			}

			foreach ($this->componentTypesPairedStringsForLowercaseString as $componentName => $componentValueType) {
				$builder->setOffsetValueType(new ConstantStringType($componentName), $componentValueType, true);
			}
		} else {
			if ($this->componentTypesPairedStrings === null) {
				throw new ShouldNotHappenException();
			}

			foreach ($this->componentTypesPairedStrings as $componentName => $componentValueType) {
				$builder->setOffsetValueType(new ConstantStringType($componentName), $componentValueType, true);
			}
		}

		return $builder->getArray();
	}

	private function cacheReturnTypes(): void
	{
		if ($this->componentTypesPairedConstants !== null) {
			return;
		}

		$string = new StringType();
		$lowercaseString = new IntersectionType([new StringType(), new AccessoryLowercaseStringType()]);
		$port = IntegerRangeType::fromInterval(0, 65535);
		$false = new ConstantBooleanType(false);
		$null = new NullType();

		$stringOrFalseOrNull = TypeCombinator::union($string, $false, $null);
		$lowercaseStringOrFalseOrNull = TypeCombinator::union($lowercaseString, $false, $null);
		$portOrFalseOrNull = TypeCombinator::union($port, $false, $null);

		$this->componentTypesPairedConstants = [
			PHP_URL_SCHEME => $stringOrFalseOrNull,
			PHP_URL_HOST => $stringOrFalseOrNull,
			PHP_URL_PORT => $portOrFalseOrNull,
			PHP_URL_USER => $stringOrFalseOrNull,
			PHP_URL_PASS => $stringOrFalseOrNull,
			PHP_URL_PATH => $stringOrFalseOrNull,
			PHP_URL_QUERY => $stringOrFalseOrNull,
			PHP_URL_FRAGMENT => $stringOrFalseOrNull,
		];
		$this->componentTypesPairedConstantsForLowercaseString = [
			PHP_URL_SCHEME => $lowercaseStringOrFalseOrNull,
			PHP_URL_HOST => $lowercaseStringOrFalseOrNull,
			PHP_URL_PORT => $portOrFalseOrNull,
			PHP_URL_USER => $lowercaseStringOrFalseOrNull,
			PHP_URL_PASS => $lowercaseStringOrFalseOrNull,
			PHP_URL_PATH => $lowercaseStringOrFalseOrNull,
			PHP_URL_QUERY => $lowercaseStringOrFalseOrNull,
			PHP_URL_FRAGMENT => $lowercaseStringOrFalseOrNull,
		];

		$this->componentTypesPairedStrings = [
			'scheme' => $string,
			'host' => $string,
			'port' => $port,
			'user' => $string,
			'pass' => $string,
			'path' => $string,
			'query' => $string,
			'fragment' => $string,
		];
		$this->componentTypesPairedStringsForLowercaseString = [
			'scheme' => $lowercaseString,
			'host' => $lowercaseString,
			'port' => $port,
			'user' => $lowercaseString,
			'pass' => $lowercaseString,
			'path' => $lowercaseString,
			'query' => $lowercaseString,
			'fragment' => $lowercaseString,
		];
	}

}
