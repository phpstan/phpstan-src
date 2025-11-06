<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_pop;
use function count;
use function implode;
use function in_array;
use function is_string;
use function sprintf;
use function strtolower;

#[AutowiredService]
final class ImpossibleCheckTypeHelper
{

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private TypeSpecifier $typeSpecifier,
		#[AutowiredParameter]
		private array $universalObjectCratesClasses,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function findSpecifiedType(
		Scope $scope,
		Expr $node,
	): ?bool
	{
		if ($node instanceof FuncCall) {
			if ($node->isFirstClassCallable()) {
				return null;
			}
			$args = $node->getArgs();
			$argsCount = count($args);
			if ($node->name instanceof Node\Name) {
				$functionName = strtolower((string) $node->name);
				if ($functionName === 'assert' && $argsCount >= 1) {
					$arg = $args[0]->value;
					$assertValue = ($this->treatPhpDocTypesAsCertain ? $scope->getType($arg) : $scope->getNativeType($arg))->toBoolean();
					$assertValueIsTrue = $assertValue->isTrue()->yes();
					if (! $assertValueIsTrue && ! $assertValue->isFalse()->yes()) {
						return null;
					}

					return $assertValueIsTrue;
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
				} elseif ($functionName === 'in_array' && $argsCount >= 2) {
					$haystackArg = $args[1]->value;
					$haystackType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($haystackArg) : $scope->getNativeType($haystackArg));
					if ($haystackType instanceof MixedType) {
						return null;
					}

					if (!$haystackType->isArray()->yes()) {
						return null;
					}

					$needleArg = $args[0]->value;
					$needleType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($needleArg) : $scope->getNativeType($needleArg));

					$isStrictComparison = false;
					if ($argsCount >= 3) {
						$strictNodeType = $scope->getType($args[2]->value);
						$isStrictComparison = $strictNodeType->isTrue()->yes();
					}

					$isStrictComparison = $isStrictComparison
						|| $needleType->isEnum()->yes()
						|| $haystackType->getIterableValueType()->isEnum()->yes();

					if (!$isStrictComparison) {
						return null;
					}

					$valueType = $haystackType->getIterableValueType();
					$constantNeedleTypesCount = count($needleType->getFiniteTypes());
					$constantHaystackTypesCount = count($valueType->getFiniteTypes());
					$isNeedleSupertype = $needleType->isSuperTypeOf($valueType);
					if ($haystackType->isConstantArray()->no()) {
						if ($haystackType->isIterableAtLeastOnce()->yes()) {
							// In this case the generic implementation via typeSpecifier fails, because the argument types cannot be narrowed down.
							if ($constantNeedleTypesCount === 1 && $constantHaystackTypesCount === 1) {
								if ($isNeedleSupertype->yes()) {
									return true;
								}
								if ($isNeedleSupertype->no()) {
									return false;
								}
							}

							return null;
						}
					}

					if (!$haystackType instanceof ConstantArrayType || count($haystackType->getValueTypes()) > 0) {
						$haystackArrayTypes = $haystackType->getArrays();
						if (count($haystackArrayTypes) === 1 && $haystackArrayTypes[0]->getIterableValueType() instanceof NeverType) {
							return null;
						}

						if ($isNeedleSupertype->maybe() || $isNeedleSupertype->yes()) {
							foreach ($haystackArrayTypes as $haystackArrayType) {
								if ($haystackArrayType instanceof ConstantArrayType) {
									foreach ($haystackArrayType->getValueTypes() as $i => $haystackArrayValueType) {
										if ($haystackArrayType->isOptionalKey($i)) {
											continue;
										}

										$haystackArrayValueConstantScalarTypes = $haystackArrayValueType->getConstantScalarTypes();
										if (count($haystackArrayValueConstantScalarTypes) > 1) {
											continue;
										}

										foreach ($haystackArrayValueConstantScalarTypes as $constantScalarType) {
											if ($constantScalarType->isSuperTypeOf($needleType)->yes()) {
												continue 3;
											}
										}
									}
								} else {
									foreach ($haystackArrayType->getIterableValueType()->getConstantScalarTypes() as $constantScalarType) {
										if ($constantScalarType->isSuperTypeOf($needleType)->yes()) {
											continue 2;
										}
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
				} elseif ($functionName === 'method_exists' && $argsCount >= 2) {
					$objectArg = $args[0]->value;
					$objectType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($objectArg) : $scope->getNativeType($objectArg));

					if ($objectType instanceof ConstantStringType
						&& !$this->reflectionProvider->hasClass($objectType->getValue())
					) {
						return false;
					}

					$methodArg = $args[1]->value;
					$methodType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($methodArg) : $scope->getNativeType($methodArg));

					if ($methodType instanceof ConstantStringType) {
						if ($objectType instanceof ConstantStringType) {
							$objectType = new ObjectType($objectType->getValue());
						}

						if ($objectType->getObjectClassNames() !== []) {
							if ($objectType->hasMethod($methodType->getValue())->yes()) {
								return true;
							}

							if ($objectType->hasMethod($methodType->getValue())->no()) {
								return false;
							}
						}

						$genericType = TypeTraverser::map($objectType, static function (Type $type, callable $traverse): Type {
							if ($type instanceof UnionType || $type instanceof IntersectionType) {
								return $traverse($type);
							}
							if ($type instanceof GenericClassStringType) {
								return $type->getGenericType();
							}
							return new MixedType();
						});

						if ($genericType instanceof TypeWithClassName) {
							if ($genericType->hasMethod($methodType->getValue())->yes()) {
								return true;
							}

							$classReflection = $genericType->getClassReflection();
							if (
								$classReflection !== null
								&& $classReflection->isFinal()
								&& $genericType->hasMethod($methodType->getValue())->no()) {
								return false;
							}
						}
					}
				}
			}
		}

		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$typeSpecifierScope = $this->treatPhpDocTypesAsCertain ? $scope : $scope->doNotTreatPhpDocTypesAsCertain();
		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($typeSpecifierScope, $node, $this->determineContext($typeSpecifierScope, $node));

		// don't validate types on overwrite
		if ($specifiedTypes->shouldOverwrite()) {
			return null;
		}

		$sureTypes = $specifiedTypes->getSureTypes();
		$sureNotTypes = $specifiedTypes->getSureNotTypes();

		$rootExpr = $specifiedTypes->getRootExpr();
		if ($rootExpr !== null) {
			if (self::isSpecified($typeSpecifierScope, $node, $rootExpr)) {
				return null;
			}

			$rootExprType = ($this->treatPhpDocTypesAsCertain ? $scope->getType($rootExpr) : $scope->getNativeType($rootExpr));
			if ($rootExprType instanceof ConstantBooleanType) {
				return $rootExprType->getValue();
			}

			return null;
		}

		$results = [];

		$assignedInCallVars = [];
		if ($node instanceof Expr\CallLike) {
			foreach ($node->getArgs() as $arg) {
				$expr = $arg->value;
				while ($expr instanceof Expr\Assign) {
					$expr = $expr->expr;
				}
				$assignedExpr = $expr;

				$expr = $arg->value;
				while ($expr instanceof Expr\Assign) {
					$assignedInCallVars[] = new Expr\Assign(
						$expr->var,
						$assignedExpr,
						$expr->getAttributes(),
					);

					$expr = $expr->expr;
				}
			}
		}
		foreach ($sureTypes as $sureType) {
			if (self::isSpecified($typeSpecifierScope, $node, $sureType[0])) {
				$results[] = TrinaryLogic::createMaybe();
				continue;
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$argumentType = $scope->getType($sureType[0]);
			} else {
				$argumentType = $scope->getNativeType($sureType[0]);
			}

			/** @var Type $resultType */
			$resultType = $sureType[1];

			foreach ($assignedInCallVars as $assignedInCallVar) {
				if ($sureType[0] !== $assignedInCallVar->var) {
					continue;
				}

				$argumentType = $scope->getType($assignedInCallVar->expr);
			}

			$results[] = $resultType->isSuperTypeOf($argumentType)->result;
		}

		foreach ($sureNotTypes as $sureNotType) {
			if (self::isSpecified($typeSpecifierScope, $node, $sureNotType[0])) {
				$results[] = TrinaryLogic::createMaybe();
				continue;
			}

			if ($this->treatPhpDocTypesAsCertain) {
				$argumentType = $scope->getType($sureNotType[0]);
			} else {
				$argumentType = $scope->getNativeType($sureNotType[0]);
			}

			/** @var Type $resultType */
			$resultType = $sureNotType[1];

			$results[] = $resultType->isSuperTypeOf($argumentType)->negate()->result;
		}

		if (count($results) === 0) {
			return null;
		}

		$result = TrinaryLogic::createYes()->and(...$results);
		return $result->maybe() ? null : $result->yes();
	}

	private static function isSpecified(Scope $scope, Expr $node, Expr $expr): bool
	{
		if ($expr === $node) {
			return true;
		}

		if ($expr instanceof Expr\Variable) {
			return is_string($expr->name) && !$scope->hasVariableType($expr->name)->yes();
		}

		if ($expr instanceof Expr\BooleanNot) {
			return self::isSpecified($scope, $node, $expr->expr);
		}

		if ($expr instanceof Expr\BinaryOp) {
			return self::isSpecified($scope, $node, $expr->left) || self::isSpecified($scope, $node, $expr->right);
		}

		return (
			$node instanceof FuncCall
			|| $node instanceof MethodCall
			|| $node instanceof Expr\StaticCall
		) && $scope->hasExpressionType($expr)->yes();
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

		$descriptions = array_map(fn (Arg $arg): string => ($this->treatPhpDocTypesAsCertain ? $scope->getType($arg->value) : $scope->getNativeType($arg->value))->describe(VerbosityLevel::value()), $args);

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

	private function determineContext(Scope $scope, Expr $node): TypeSpecifierContext
	{
		if ($node instanceof Expr\CallLike && $node->isFirstClassCallable()) {
			return TypeSpecifierContext::createTruthy();
		}

		if ($node instanceof FuncCall && $node->name instanceof Node\Name) {
			if ($this->reflectionProvider->hasFunction($node->name, $scope)) {
				$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $functionReflection->getVariants(), $functionReflection->getNamedArgumentsVariants());
				$returnType = TypeUtils::resolveLateResolvableTypes($parametersAcceptor->getReturnType());

				return $returnType->isVoid()->yes() ? TypeSpecifierContext::createNull() : TypeSpecifierContext::createTruthy();
			}
		} elseif ($node instanceof MethodCall && $node->name instanceof Node\Identifier) {
			$methodCalledOnType = $scope->getType($node->var);
			$methodReflection = $scope->getMethodReflection($methodCalledOnType, $node->name->name);
			if ($methodReflection !== null) {
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants());
				$returnType = TypeUtils::resolveLateResolvableTypes($parametersAcceptor->getReturnType());

				return $returnType->isVoid()->yes() ? TypeSpecifierContext::createNull() : TypeSpecifierContext::createTruthy();
			}
		} elseif ($node instanceof StaticCall && $node->name instanceof Node\Identifier) {
			if ($node->class instanceof Node\Name) {
				$calleeType = $scope->resolveTypeByName($node->class);
			} else {
				$calleeType = $scope->getType($node->class);
			}

			$staticMethodReflection = $scope->getMethodReflection($calleeType, $node->name->name);
			if ($staticMethodReflection !== null) {
				$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $staticMethodReflection->getVariants(), $staticMethodReflection->getNamedArgumentsVariants());
				$returnType = TypeUtils::resolveLateResolvableTypes($parametersAcceptor->getReturnType());

				return $returnType->isVoid()->yes() ? TypeSpecifierContext::createNull() : TypeSpecifierContext::createTruthy();
			}
		}

		return TypeSpecifierContext::createTruthy();
	}

}
