<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Bug4003\Boo;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_filter;
use function count;
use function in_array;
use function version_compare;

class VersionCompareFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var list<string> */
	private $operators = [
		'<',
		'lt',
		'<=',
		'le',
		'>',
		'gt',
		'>=',
		'ge',
		'==',
		'=',
		'eq',
		'!=',
		'<>',
		'ne',
	];

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'version_compare';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 2) {
			return ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants())->getReturnType();
		}

		$version1Strings = TypeUtils::getConstantStrings($scope->getType($args[0]->value));
		$version2Strings = TypeUtils::getConstantStrings($scope->getType($args[1]->value));
		$counts = [
			count($version1Strings),
			count($version2Strings),
		];

		$returnsBool = false;
		if (isset($args[2])) {
			$returnsBool = true;

			if (!$scope->getType($args[2]->value) instanceof ConstantScalarType) {
				// we don't use ParametersAcceptorSelector::selectFromArgs() because the return type depends on php-version
				// and the function has multiple signatures
				$returnType = TypeCombinator::union(
					new ConstantIntegerType(-1),
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new BooleanType()
				);

				if ($this->phpVersion->getVersionId() < 80000) {
					return TypeCombinator::union(
						new NullType(),
						$returnType
					);
				}

				return $returnType;
			}

			if ((new NullType())->isSuperTypeOf($scope->getType($args[2]->value))->yes() &&
				$this->phpVersion->getVersionId() >= 80000) {
				$returnsBool = false;
			}
		}

		if ($returnsBool) {
			$operatorStrings = TypeUtils::getConstantStrings($scope->getType($args[2]->value));

			foreach ($operatorStrings as $operatorString) {
				if (!in_array($operatorString->getValue(), $this->operators, true)) {
					if ($this->phpVersion->getVersionId() <= 80000) {
						return new NullType();
					}
					return new ErrorType();
				}
			}

			$counts[] = count($operatorStrings);
			$returnType = new BooleanType();
		} else {
			$returnType = TypeCombinator::union(
				new ConstantIntegerType(-1),
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			);
		}

		if (count(array_filter($counts, static fn (int $count): bool => $count === 0)) > 0) {
			return $returnType; // one of the arguments is not a constant string
		}

		if (count(array_filter($counts, static fn (int $count): bool => $count > 1)) > 1) {
			return $returnType; // more than one argument can have multiple possibilities, avoid combinatorial explosion
		}

		$types = [];
		foreach ($version1Strings as $version1String) {
			foreach ($version2Strings as $version2String) {
				if (isset($operatorStrings)) {
					foreach ($operatorStrings as $operatorString) {
						$value = version_compare($version1String->getValue(), $version2String->getValue(), $operatorString->getValue());
						$types[$value] = new ConstantBooleanType($value);
					}
				} else {
					$value = version_compare($version1String->getValue(), $version2String->getValue());
					$types[$value] = new ConstantIntegerType($value);
				}
			}
		}
		return TypeCombinator::union(...$types);
	}

}
