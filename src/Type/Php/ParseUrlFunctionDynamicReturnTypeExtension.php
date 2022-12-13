<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
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

	private ?Type $allComponentsTogetherType = null;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'parse_url';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return ParametersAcceptorSelector::selectSingle(
				$functionReflection->getVariants(),
			)->getReturnType();
		}

		$this->cacheReturnTypes();

		if (count($functionCall->getArgs()) > 1) {
			$componentType = $scope->getType($functionCall->getArgs()[1]->value);

			if (!$componentType instanceof ConstantType) {
				return $this->createAllComponentsReturnType();
			}

			$componentType = $componentType->toInteger();

			if (!$componentType instanceof ConstantIntegerType) {
				throw new ShouldNotHappenException();
			}
		} else {
			$componentType = new ConstantIntegerType(-1);
		}

		$urlType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($urlType instanceof ConstantStringType) {
			try {
				$result = @parse_url($urlType->getValue(), $componentType->getValue());
			} catch (ValueError) {
				return new ConstantBooleanType(false);
			}

			return $scope->getTypeFromValue($result);
		}

		if ($componentType->getValue() === -1) {
			return $this->createAllComponentsReturnType();
		}

		return $this->componentTypesPairedConstants[$componentType->getValue()] ?? new ConstantBooleanType(false);
	}

	private function createAllComponentsReturnType(): Type
	{
		if ($this->allComponentsTogetherType === null) {
			$returnTypes = [
				new ConstantBooleanType(false),
			];

			$builder = ConstantArrayTypeBuilder::createEmpty();

			if ($this->componentTypesPairedStrings === null) {
				throw new ShouldNotHappenException();
			}

			foreach ($this->componentTypesPairedStrings as $componentName => $componentValueType) {
				$builder->setOffsetValueType(new ConstantStringType($componentName), $componentValueType, true);
			}

			$returnTypes[] = $builder->getArray();

			$this->allComponentsTogetherType = TypeCombinator::union(...$returnTypes);
		}

		return $this->allComponentsTogetherType;
	}

	private function cacheReturnTypes(): void
	{
		if ($this->componentTypesPairedConstants !== null) {
			return;
		}

		$string = new StringType();
		$port = IntegerRangeType::fromInterval(0, 65535);
		$false = new ConstantBooleanType(false);
		$null = new NullType();

		$stringOrFalseOrNull = TypeCombinator::union($string, $false, $null);
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
	}

}
