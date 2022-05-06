<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use function array_column;
use function array_map;
use function array_pop;
use function count;
use function implode;
use function in_array;
use function is_string;
use function reset;
use function sprintf;
use function strtolower;

class ImpossibleCheckTypeHelper
{

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private TypeSpecifier $typeSpecifier,
		private array $universalObjectCratesClasses,
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function findSpecifiedType(
		Scope $scope,
		Expr $node,
	): ?bool
	{
		if (
			$node instanceof FuncCall
			&& count($node->getArgs()) > 0
		) {
			if ($node->name instanceof Node\Name) {
				$functionName = strtolower((string) $node->name);
				if ($functionName === 'assert') {
					$assertValue = $scope->getType($node->getArgs()[0]->value)->toBoolean();
					if (!$assertValue instanceof ConstantBooleanType) {
						return null;
					}

					return $assertValue->getValue();
				}
				if (in_array($functionName, [
					'class_exists',
					'interface_exists',
					'trait_exists',
					'enum_exists',
				], true)) {
					return null;
				}
				if (in_array($functionName, ['count', 'sizeof'], true)) {
					return null;
				} elseif ($functionName === 'defined') {
					return null;
				} elseif ($functionName === 'array_search') {
					return null;
				} elseif (
					$functionName === 'in_array'
					&& count($node->getArgs()) >= 3
				) {
					$haystackType = $scope->getType($node->getArgs()[1]->value);
					if ($haystackType instanceof MixedType) {
						return null;
					}

					if (!$haystackType->isArray()->yes()) {
						return null;
					}

					$constantArrays = TypeUtils::getOldConstantArrays($haystackType);
					$needleType = $scope->getType($node->getArgs()[0]->value);
					$valueType = $haystackType->getIterableValueType();
					$constantNeedleTypesCount = count(TypeUtils::getConstantScalars($needleType));
					$constantHaystackTypesCount = count(TypeUtils::getConstantScalars($valueType));
					$isNeedleSupertype = $needleType->isSuperTypeOf($valueType);
					if (count($constantArrays) === 0) {
						if ($haystackType->isIterableAtLeastOnce()->yes()) {
							if ($constantNeedleTypesCount === 1 && $constantHaystackTypesCount === 1) {
								if ($isNeedleSupertype->yes()) {
									return true;
								}
								if ($isNeedleSupertype->no()) {
									return false;
								}
							}
						}
						return null;
					}

					if (!$haystackType instanceof ConstantArrayType || count($haystackType->getValueTypes()) > 0) {
						$haystackArrayTypes = TypeUtils::getArrays($haystackType);
						if (count($haystackArrayTypes) === 1 && $haystackArrayTypes[0]->getIterableValueType() instanceof NeverType) {
							return null;
						}

						if ($isNeedleSupertype->maybe() || $isNeedleSupertype->yes()) {
							foreach ($haystackArrayTypes as $haystackArrayType) {
								foreach (TypeUtils::getConstantScalars($haystackArrayType->getIterableValueType()) as $constantScalarType) {
									if ($constantScalarType->isSuperTypeOf($needleType)->yes()) {
										continue 2;
									}
								}

								return null;
							}
						}

						if ($isNeedleSupertype->yes()) {
							$hasConstantNeedleTypes = $constantNeedleTypesCount > 0;
							$hasConstantHaystackTypes = $constantHaystackTypesCount > 0;
							if (
								(!$hasConstantNeedleTypes && !$hasConstantHaystackTypes)
								|| $hasConstantNeedleTypes !== $hasConstantHaystackTypes
							) {
								return null;
							}
						}
					}
				} elseif (
					$functionName === 'array_key_exists'
					&& count($node->getArgs()) >= 2
				) {
					$arrayType = $scope->getType($node->getArgs()[1]->value);
					if ($arrayType instanceof MixedType) {
						return null;
					}

					if (!$arrayType->isArray()->yes()) {
						return null;
					}

					if (!$arrayType instanceof ConstantArrayType || count($arrayType->getKeyTypes()) > 0) {
						$keyType = $scope->getType($node->getArgs()[0]->value);

						$arrayTypes = TypeUtils::getArrays($arrayType);
						if (count($arrayTypes) === 1 && $arrayTypes[0]->getIterableKeyType() instanceof NeverType) {
							return null;
						}

						$arrayType = $arrayType->getIterableKeyType();
						$isKeySupertype = $keyType->isSuperTypeOf($arrayType);

						if ($isKeySupertype->maybe() || $isKeySupertype->yes()) {
							foreach ($arrayTypes as $arrayTypeInner) {
								foreach (TypeUtils::getConstantScalars($arrayTypeInner->getIterableKeyType()) as $constantScalarType) {
									if ($keyType->isSuperTypeOf($constantScalarType)->yes()) {
										continue 2;
									}
								}

								return null;
							}
						}

						if ($isKeySupertype->yes()) {
							$hasConstantKeyTypes = count(TypeUtils::getConstantScalars($keyType)) > 0;
							$hasConstantArrayTypes = count(TypeUtils::getConstantScalars($arrayType)) > 0;
							if (
								(
									!$hasConstantKeyTypes
									&& !$hasConstantArrayTypes
								)
								|| $hasConstantKeyTypes !== $hasConstantArrayTypes
							) {
								return null;
							}
						}
					}
				} elseif (
					$functionName === 'method_exists'
					&& count($node->getArgs()) >= 2
				) {
					$objectType = $scope->getType($node->getArgs()[0]->value);
					$methodType = $scope->getType($node->getArgs()[1]->value);

					if ($objectType instanceof ConstantStringType
						&& !$this->reflectionProvider->hasClass($objectType->getValue())
					) {
						return false;
					}

					if ($methodType instanceof ConstantStringType) {
						if ($objectType instanceof ConstantStringType) {
							$objectType = new ObjectType($objectType->getValue());
						}

						if ($objectType instanceof TypeWithClassName) {
							if ($objectType->hasMethod($methodType->getValue())->yes()) {
								return true;
							}

							if ($objectType->hasMethod($methodType->getValue())->no()) {
								return false;
							}
						}
					}
				}
			}
		}

		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $node, TypeSpecifierContext::createTruthy());

		// don't validate types on overwrite
		if ($specifiedTypes->shouldOverwrite()) {
			return null;
		}

		$sureTypes = $specifiedTypes->getSureTypes();
		$sureNotTypes = $specifiedTypes->getSureNotTypes();

		$isSpecified = static function (Expr $expr) use ($scope, $node): bool {
			if ($expr === $node) {
				return true;
			}

			if ($expr instanceof Expr\Variable && is_string($expr->name) && !$scope->hasVariableType($expr->name)->yes()) {
				return true;
			}

			return (
				$node instanceof FuncCall
				|| $node instanceof MethodCall
				|| $node instanceof Expr\StaticCall
			) && $scope->isSpecified($expr);
		};

		if (count($sureTypes) === 1 && count($sureNotTypes) === 0) {
			$sureType = reset($sureTypes);
			if ($isSpecified($sureType[0])) {
				return null;
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$argumentType = $scope->getType($sureType[0]);
			} else {
				$argumentType = $scope->getNativeType($sureType[0]);
			}

			/** @var Type $resultType */
			$resultType = $sureType[1];

			$isSuperType = $resultType->isSuperTypeOf($argumentType);
			if ($isSuperType->yes()) {
				return true;
			} elseif ($isSuperType->no()) {
				return false;
			}

			return null;
		} elseif (count($sureNotTypes) === 1 && count($sureTypes) === 0) {
			$sureNotType = reset($sureNotTypes);
			if ($isSpecified($sureNotType[0])) {
				return null;
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$argumentType = $scope->getType($sureNotType[0]);
			} else {
				$argumentType = $scope->getNativeType($sureNotType[0]);
			}

			/** @var Type $resultType */
			$resultType = $sureNotType[1];

			$isSuperType = $resultType->isSuperTypeOf($argumentType);
			if ($isSuperType->yes()) {
				return false;
			} elseif ($isSuperType->no()) {
				return true;
			}

			return null;
		}

		if (count($sureTypes) > 0) {
			foreach ($sureTypes as $sureType) {
				if ($isSpecified($sureType[0])) {
					return null;
				}
			}
			$types = TypeCombinator::union(
				...array_column($sureTypes, 1),
			);
			if ($types instanceof NeverType) {
				return false;
			}
		}

		if (count($sureNotTypes) > 0) {
			foreach ($sureNotTypes as $sureNotType) {
				if ($isSpecified($sureNotType[0])) {
					return null;
				}
			}
			$types = TypeCombinator::union(
				...array_column($sureNotTypes, 1),
			);
			if ($types instanceof NeverType) {
				return true;
			}
		}

		return null;
	}

	/**
	 * @param Node\Arg[] $args
	 */
	public function getArgumentsDescription(
		Scope $scope,
		array $args,
	): string
	{
		if (count($args) === 0) {
			return '';
		}

		$descriptions = array_map(static fn (Arg $arg): string => $scope->getType($arg->value)->describe(VerbosityLevel::value()), $args);

		if (count($descriptions) < 3) {
			return sprintf(' with %s', implode(' and ', $descriptions));
		}

		$lastDescription = array_pop($descriptions);

		return sprintf(
			' with arguments %s and %s',
			implode(', ', $descriptions),
			$lastDescription,
		);
	}

	public function doNotTreatPhpDocTypesAsCertain(): self
	{
		if (!$this->treatPhpDocTypesAsCertain) {
			return $this;
		}

		return new self(
			$this->reflectionProvider,
			$this->typeSpecifier,
			$this->universalObjectCratesClasses,
			false,
		);
	}

}
