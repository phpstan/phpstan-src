<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

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
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;

class ImpossibleCheckTypeHelper
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

	/** @var string[] */
	private array $universalObjectCratesClasses;

	private bool $treatPhpDocTypesAsCertain;

	/**
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param \PHPStan\Analyser\TypeSpecifier $typeSpecifier
	 * @param string[] $universalObjectCratesClasses
	 * @param bool $treatPhpDocTypesAsCertain
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		TypeSpecifier $typeSpecifier,
		array $universalObjectCratesClasses,
		bool $treatPhpDocTypesAsCertain
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->typeSpecifier = $typeSpecifier;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
	}

	public function findSpecifiedType(
		Scope $scope,
		Expr $node
	): ?bool
	{
		if (
			$node instanceof FuncCall
			&& count($node->args) > 0
		) {
			if ($node->name instanceof \PhpParser\Node\Name) {
				$functionName = strtolower((string) $node->name);
				if ($functionName === 'assert') {
					$assertValue = $scope->getType($node->args[0]->value)->toBoolean();
					if (!$assertValue instanceof ConstantBooleanType) {
						return null;
					}

					return $assertValue->getValue();
				}
				if (in_array($functionName, [
					'class_exists',
					'interface_exists',
					'trait_exists',
				], true)) {
					return null;
				}
				if ($functionName === 'count') {
					return null;
				} elseif ($functionName === 'defined') {
					return null;
				} elseif (
					$functionName === 'in_array'
					&& count($node->args) >= 3
				) {
					$haystackType = $scope->getType($node->args[1]->value);
					if ($haystackType instanceof MixedType) {
						return null;
					}

					if (!$haystackType->isArray()->yes()) {
						return null;
					}

					if (!$haystackType instanceof ConstantArrayType || count($haystackType->getValueTypes()) > 0) {
						$needleType = $scope->getType($node->args[0]->value);

						$haystackArrayTypes = TypeUtils::getArrays($haystackType);
						if (count($haystackArrayTypes) === 1 && $haystackArrayTypes[0]->getIterableValueType() instanceof NeverType) {
							return null;
						}

						$valueType = $haystackType->getIterableValueType();
						$isNeedleSupertype = $needleType->isSuperTypeOf($valueType);

						if ($isNeedleSupertype->maybe() || $isNeedleSupertype->yes()) {
							foreach ($haystackArrayTypes as $haystackArrayType) {
								foreach (TypeUtils::getConstantScalars($haystackArrayType->getIterableValueType()) as $constantScalarType) {
									if ($needleType->isSuperTypeOf($constantScalarType)->yes()) {
										continue 2;
									}
								}

								return null;
							}
						}

						if ($isNeedleSupertype->yes()) {
							$hasConstantNeedleTypes = count(TypeUtils::getConstantScalars($needleType)) > 0;
							$hasConstantHaystackTypes = count(TypeUtils::getConstantScalars($valueType)) > 0;
							if (
								(
									!$hasConstantNeedleTypes
									&& !$hasConstantHaystackTypes
								)
								|| $hasConstantNeedleTypes !== $hasConstantHaystackTypes
							) {
								return null;
							}
						}
					}
				} elseif ($functionName === 'method_exists' && count($node->args) >= 2) {
					$objectType = $scope->getType($node->args[0]->value);
					$methodType = $scope->getType($node->args[1]->value);

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

			/** @var \PHPStan\Type\Type $resultType */
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

			/** @var \PHPStan\Type\Type $resultType */
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
				...array_column($sureTypes, 1)
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
				...array_column($sureNotTypes, 1)
			);
			if ($types instanceof NeverType) {
				return true;
			}
		}

		return null;
	}

	/**
	 * @param Scope $scope
	 * @param \PhpParser\Node\Arg[] $args
	 * @return string
	 */
	public function getArgumentsDescription(
		Scope $scope,
		array $args
	): string
	{
		if (count($args) === 0) {
			return '';
		}

		$descriptions = array_map(static function (Arg $arg) use ($scope): string {
			return $scope->getType($arg->value)->describe(VerbosityLevel::value());
		}, $args);

		if (count($descriptions) < 3) {
			return sprintf(' with %s', implode(' and ', $descriptions));
		}

		$lastDescription = array_pop($descriptions);

		return sprintf(
			' with arguments %s and %s',
			implode(', ', $descriptions),
			$lastDescription
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
			false
		);
	}

}
