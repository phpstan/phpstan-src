<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Closure;
use PhpParser\Node;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Parser\ArrayFilterArgVisitor;
use PHPStan\Parser\ArrayFindArgVisitor;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ArrayWalkArgVisitor;
use PHPStan\Parser\ClosureBindArgVisitor;
use PHPStan\Parser\ClosureBindToVarVisitor;
use PHPStan\Parser\CurlSetOptArgVisitor;
use PHPStan\Parser\CurlSetOptArrayArgVisitor;
use PHPStan\Parser\ImplodeArgVisitor;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_is_list;
use function array_key_exists;
use function array_key_last;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function constant;
use function count;
use function defined;
use function is_int;
use function is_string;
use function sprintf;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;
use const CURLOPT_SHARE;
use const CURLOPT_SSL_VERIFYHOST;

/**
 * @api
 */
final class ParametersAcceptorSelector
{

	/**
	 * @param Node\Arg[] $args
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 */
	public static function selectFromArgs(
		Scope $scope,
		array $args,
		array $parametersAcceptors,
		?array $namedArgumentsVariants = null,
	): ParametersAcceptor
	{
		$types = [];
		$unpack = false;
		if (
			count($args) > 0
			&& count($parametersAcceptors) > 0
		) {
			$arrayMapArgs = $args[0]->value->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
			if ($arrayMapArgs !== null) {
				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				$callbackParameters = [];
				foreach ($arrayMapArgs as $arg) {
					$argType = $scope->getType($arg->value);
					if ($arg->unpack) {
						$constantArrays = $argType->getConstantArrays();
						if (count($constantArrays) > 0) {
							foreach ($constantArrays as $constantArray) {
								$valueTypes = $constantArray->getValueTypes();
								foreach ($valueTypes as $valueType) {
									$callbackParameters[] = new DummyParameter('item', $scope->getIterableValueType($valueType), false, PassedByReference::createNo(), false, null);
								}
							}
						}
					} else {
						$callbackParameters[] = new DummyParameter('item', $scope->getIterableValueType($argType), false, PassedByReference::createNo(), false, null);
					}
				}

				if (isset($parameters[0])) {
					$parameters[0] = new NativeParameterReflection(
						$parameters[0]->getName(),
						$parameters[0]->isOptional(),
						new UnionType([
							new CallableType($callbackParameters, new MixedType(), false),
							new NullType(),
						]),
						$parameters[0]->passedByReference(),
						$parameters[0]->isVariadic(),
						$parameters[0]->getDefaultValue(),
					);
					$parametersAcceptors = [
						new FunctionVariant(
							$acceptor->getTemplateTypeMap(),
							$acceptor->getResolvedTemplateTypeMap(),
							$parameters,
							$acceptor->isVariadic(),
							$acceptor->getReturnType(),
							$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						),
					];
				}
			}

			if (count($args) >= 3 && (bool) $args[0]->getAttribute(CurlSetOptArgVisitor::ATTRIBUTE_NAME)) {
				$optType = $scope->getType($args[1]->value);

				$valueTypes = [];
				foreach ($optType->getConstantScalarValues() as $scalarValue) {
					if (!is_int($scalarValue)) {
						$valueTypes = [];
						break;
					}

					$valueType = self::getCurlOptValueType($scalarValue);
					if ($valueType === null) {
						$valueTypes = [];
						break;
					}

					$valueTypes[] = $valueType;
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				if (count($valueTypes) !== 0 && isset($parameters[2])) {
					$parameters[2] = new NativeParameterReflection(
						$parameters[2]->getName(),
						$parameters[2]->isOptional(),
						TypeCombinator::union(...$valueTypes),
						$parameters[2]->passedByReference(),
						$parameters[2]->isVariadic(),
						$parameters[2]->getDefaultValue(),
					);

					$parametersAcceptors = [
						new FunctionVariant(
							$acceptor->getTemplateTypeMap(),
							$acceptor->getResolvedTemplateTypeMap(),
							$parameters,
							$acceptor->isVariadic(),
							$acceptor->getReturnType(),
							$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						),
					];
				}
			}

			if (count($args) >= 2 && (bool) $args[1]->getAttribute(CurlSetOptArrayArgVisitor::ATTRIBUTE_NAME)) {
				$optArrayType = $scope->getType($args[1]->value);

				$hasTypes = false;
				$builder = ConstantArrayTypeBuilder::createEmpty();
				foreach ($optArrayType->getIterableKeyType()->getConstantScalarTypes() as $optType) {
					$optValue = $optType->getValue();

					if (!is_int($optValue)) {
						$hasTypes = false;
						break;
					}

					$optValueType = self::getCurlOptValueType($optValue);
					if ($optValueType === null) {
						$hasTypes = false;
						break;
					}

					$hasTypes = true;
					$builder->setOffsetValueType(
						new ConstantIntegerType($optValue),
						$optValueType,
						!$optArrayType->hasOffsetValueType($optType)->yes(),
					);
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				if ($hasTypes && isset($parameters[1])) {
					$parameters[1] = new NativeParameterReflection(
						$parameters[1]->getName(),
						$parameters[1]->isOptional(),
						$builder->getArray(),
						$parameters[1]->passedByReference(),
						$parameters[1]->isVariadic(),
						$parameters[1]->getDefaultValue(),
					);

					$parametersAcceptors = [
						new FunctionVariant(
							$acceptor->getTemplateTypeMap(),
							$acceptor->getResolvedTemplateTypeMap(),
							$parameters,
							$acceptor->isVariadic(),
							$acceptor->getReturnType(),
							$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						),
					];
				}
			}

			if ((bool) $args[0]->getAttribute(ArrayFilterArgVisitor::ATTRIBUTE_NAME)) {
				if (isset($args[2])) {
					$mode = $scope->getType($args[2]->value);
					if ($mode instanceof ConstantIntegerType) {
						if ($mode->getValue() === ARRAY_FILTER_USE_KEY) {
							$arrayFilterParameters = [
								new DummyParameter('key', $scope->getIterableKeyType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
							];
						} elseif ($mode->getValue() === ARRAY_FILTER_USE_BOTH) {
							$arrayFilterParameters = [
								new DummyParameter('item', $scope->getIterableValueType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
								new DummyParameter('key', $scope->getIterableKeyType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
							];
						}
					}
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				if (isset($parameters[1])) {
					$parameters[1] = new NativeParameterReflection(
						$parameters[1]->getName(),
						$parameters[1]->isOptional(),
						new UnionType([
							new CallableType(
								$arrayFilterParameters ?? [
									new DummyParameter('item', $scope->getIterableValueType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
								],
								new BooleanType(),
								false,
							),
							new NullType(),
						]),
						$parameters[1]->passedByReference(),
						$parameters[1]->isVariadic(),
						$parameters[1]->getDefaultValue(),
					);
					$parametersAcceptors = [
						new FunctionVariant(
							$acceptor->getTemplateTypeMap(),
							$acceptor->getResolvedTemplateTypeMap(),
							$parameters,
							$acceptor->isVariadic(),
							$acceptor->getReturnType(),
							$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						),
					];
				}
			}

			if (count($args) <= 2 && (bool) $args[0]->getAttribute(ImplodeArgVisitor::ATTRIBUTE_NAME)) {
				$acceptor = $namedArgumentsVariants[0] ?? $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				if (
					(isset($args[1]) || ($args[0]->name !== null && $args[0]->name->name === 'array'))
					&& isset($parameters[0]) && isset($parameters[1])
				) {
					$parameters = [
						new NativeParameterReflection($parameters[0]->getName(), false, new StringType(), PassedByReference::createNo(), false, null),
						new NativeParameterReflection($parameters[1]->getName(), false, new ArrayType(new MixedType(), new MixedType()), PassedByReference::createNo(), false, null),
					];
				} elseif (isset($parameters[0])) {
					$parameters = [
						new NativeParameterReflection($parameters[0]->getName(), false, new ArrayType(new MixedType(), new MixedType()), PassedByReference::createNo(), false, null),
					];
				}

				$parametersAcceptors = [
					new FunctionVariant(
						$acceptor->getTemplateTypeMap(),
						$acceptor->getResolvedTemplateTypeMap(),
						$parameters,
						$acceptor->isVariadic(),
						$acceptor->getReturnType(),
						$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
					),
				];
			}

			if ((bool) $args[0]->getAttribute(ArrayWalkArgVisitor::ATTRIBUTE_NAME)) {
				$arrayWalkParameters = [
					new DummyParameter('item', $scope->getIterableValueType($scope->getType($args[0]->value)), false, PassedByReference::createReadsArgument(), false, null),
					new DummyParameter('key', $scope->getIterableKeyType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
				];
				if (isset($args[2])) {
					$arrayWalkParameters[] = new DummyParameter('arg', $scope->getType($args[2]->value), false, PassedByReference::createNo(), false, null);
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				if (isset($parameters[1])) {
					$parameters[1] = new NativeParameterReflection(
						$parameters[1]->getName(),
						$parameters[1]->isOptional(),
						new CallableType($arrayWalkParameters, new MixedType(), false),
						$parameters[1]->passedByReference(),
						$parameters[1]->isVariadic(),
						$parameters[1]->getDefaultValue(),
					);
					$parametersAcceptors = [
						new FunctionVariant(
							$acceptor->getTemplateTypeMap(),
							$acceptor->getResolvedTemplateTypeMap(),
							$parameters,
							$acceptor->isVariadic(),
							$acceptor->getReturnType(),
							$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						),
					];
				}
			}

			if ((bool) $args[0]->getAttribute(ArrayFindArgVisitor::ATTRIBUTE_NAME)) {
				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				if (isset($parameters[1])) {
					$argType = $scope->getType($args[0]->value);
					$parameters[1] = new NativeParameterReflection(
						$parameters[1]->getName(),
						$parameters[1]->isOptional(),
						new CallableType(
							[
								new DummyParameter('value', $scope->getIterableValueType($argType), false, PassedByReference::createNo(), false, null),
								new DummyParameter('key', $scope->getIterableKeyType($argType), false, PassedByReference::createNo(), false, null),
							],
							new BooleanType(),
							false,
						),
						$parameters[1]->passedByReference(),
						$parameters[1]->isVariadic(),
						$parameters[1]->getDefaultValue(),
					);
					$parametersAcceptors = [
						new FunctionVariant(
							$acceptor->getTemplateTypeMap(),
							$acceptor->getResolvedTemplateTypeMap(),
							$parameters,
							$acceptor->isVariadic(),
							$acceptor->getReturnType(),
							$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
						),
					];
				}
			}

			$closureBindToVar = $args[0]->getAttribute(ClosureBindToVarVisitor::ATTRIBUTE_NAME);
			if (
				$closureBindToVar instanceof Node\Expr\Variable
				&& is_string($closureBindToVar->name)
			) {
				$varType = $scope->getType($closureBindToVar);
				if ((new ObjectType(Closure::class))->isSuperTypeOf($varType)->yes()) {
					$inFunction = $scope->getFunction();
					if ($inFunction !== null) {
						$closureThisParameters = [];
						foreach ($inFunction->getParameters() as $parameter) {
							if ($parameter->getClosureThisType() === null) {
								continue;
							}
							$closureThisParameters[$parameter->getName()] = $parameter->getClosureThisType();
						}
						if (array_key_exists($closureBindToVar->name, $closureThisParameters)) {
							if ($scope->hasExpressionType(new ParameterVariableOriginalValueExpr($closureBindToVar->name))->yes()) {
								$acceptor = $parametersAcceptors[0];
								$parameters = $acceptor->getParameters();
								if (isset($parameters[0])) {
									$parameters[0] = new NativeParameterReflection(
										$parameters[0]->getName(),
										$parameters[0]->isOptional(),
										$closureThisParameters[$closureBindToVar->name],
										$parameters[0]->passedByReference(),
										$parameters[0]->isVariadic(),
										$parameters[0]->getDefaultValue(),
									);
									$parametersAcceptors = [
										new FunctionVariant(
											$acceptor->getTemplateTypeMap(),
											$acceptor->getResolvedTemplateTypeMap(),
											$parameters,
											$acceptor->isVariadic(),
											$acceptor->getReturnType(),
											$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
										),
									];
								}
							}
						}
					}
				}
			}

			if (
				$args[0]->getAttribute(ClosureBindArgVisitor::ATTRIBUTE_NAME) !== null
				&& $args[0]->value instanceof Node\Expr\Variable
				&& is_string($args[0]->value->name)
			) {
				$closureVarName = $args[0]->value->name;
				$inFunction = $scope->getFunction();
				if ($inFunction !== null) {
					$closureThisParameters = [];
					foreach ($inFunction->getParameters() as $parameter) {
						if ($parameter->getClosureThisType() === null) {
							continue;
						}
						$closureThisParameters[$parameter->getName()] = $parameter->getClosureThisType();
					}
					if (array_key_exists($closureVarName, $closureThisParameters)) {
						if ($scope->hasExpressionType(new ParameterVariableOriginalValueExpr($closureVarName))->yes()) {
							$acceptor = $parametersAcceptors[0];
							$parameters = $acceptor->getParameters();

							if (isset($parameters[1])) {
								$parameters[1] = new NativeParameterReflection(
									$parameters[1]->getName(),
									$parameters[1]->isOptional(),
									$closureThisParameters[$closureVarName],
									$parameters[1]->passedByReference(),
									$parameters[1]->isVariadic(),
									$parameters[1]->getDefaultValue(),
								);
								$parametersAcceptors = [
									new FunctionVariant(
										$acceptor->getTemplateTypeMap(),
										$acceptor->getResolvedTemplateTypeMap(),
										$parameters,
										$acceptor->isVariadic(),
										$acceptor->getReturnType(),
										$acceptor instanceof ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(),
									),
								];
							}
						}
					}
				}
			}
		}

		if (count($parametersAcceptors) === 1) {
			$acceptor = $parametersAcceptors[0];
			if (!self::hasAcceptorTemplateOrLateResolvableType($acceptor)) {
				return $acceptor;
			}
		}

		$reorderedArgs = $args;
		$parameters = null;
		$singleParametersAcceptor = null;
		if (count($parametersAcceptors) === 1) {
			if (!array_is_list($args)) {
				// actually $args parameter should be typed to list but we can't atm,
				// because its a BC break.
				$args = array_values($args);
			}
			$reorderedArgs = ArgumentsNormalizer::reorderArgs($parametersAcceptors[0], $args);
			$singleParametersAcceptor = $parametersAcceptors[0];
		}

		$hasName = false;
		foreach ($reorderedArgs ?? $args as $i => $arg) {
			$originalArg = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
			$parameter = null;
			if ($singleParametersAcceptor !== null) {
				$parameters = $singleParametersAcceptor->getParameters();
				if (isset($parameters[$i])) {
					$parameter = $parameters[$i];
				} elseif (count($parameters) > 0 && $singleParametersAcceptor->isVariadic()) {
					$parameter = array_last($parameters);
				}
			}

			if ($parameter !== null && $scope instanceof MutatingScope) {
				$rememberTypes = !$originalArg->value instanceof Node\Expr\Closure && !$originalArg->value instanceof Node\Expr\ArrowFunction;
				$scope = $scope->pushInFunctionCall(null, $parameter, $rememberTypes);
			}

			$type = $scope->getType($originalArg->value);

			if ($parameter !== null && $scope instanceof MutatingScope) {
				$scope = $scope->popInFunctionCall();
			}

			if ($originalArg->name !== null) {
				$index = $originalArg->name->toString();
				$hasName = true;
			} else {
				$index = $i;
			}
			if ($originalArg->unpack) {
				$unpack = true;
				$types[$index] = $type->getIterableValueType();
			} else {
				$types[$index] = $type;
			}
		}

		if ($hasName && $namedArgumentsVariants !== null) {
			return self::selectFromTypes($types, $namedArgumentsVariants, $unpack);
		}

		return self::selectFromTypes($types, $parametersAcceptors, $unpack);
	}

	private static function hasAcceptorTemplateOrLateResolvableType(ParametersAcceptor $acceptor): bool
	{
		if (self::hasTemplateOrLateResolvableType($acceptor->getReturnType())) {
			return true;
		}

		foreach ($acceptor->getParameters() as $parameter) {
			if (
				$parameter instanceof ExtendedParameterReflection
				&& $parameter->getOutType() !== null
				&& self::hasTemplateOrLateResolvableType($parameter->getOutType())
			) {
				return true;
			}

			if (
				$parameter instanceof ExtendedParameterReflection
				&& $parameter->getClosureThisType() !== null
				&& self::hasTemplateOrLateResolvableType($parameter->getClosureThisType())
			) {
				return true;
			}

			if (!self::hasTemplateOrLateResolvableType($parameter->getType())) {
				continue;
			}

			return true;
		}

		return false;
	}

	private static function hasTemplateOrLateResolvableType(Type $type): bool
	{
		$has = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$has): Type {
			if ($type instanceof TemplateType || $type instanceof LateResolvableType) {
				$has = true;
				return $type;
			}

			return $traverse($type);
		});

		return $has;
	}

	/**
	 * @param array<int|string, Type> $types
	 * @param ParametersAcceptor[] $parametersAcceptors
	 */
	public static function selectFromTypes(
		array $types,
		array $parametersAcceptors,
		bool $unpack,
	): ParametersAcceptor
	{
		if (count($parametersAcceptors) === 1) {
			return GenericParametersAcceptorResolver::resolve($types, $parametersAcceptors[0]);
		}

		if (count($parametersAcceptors) === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}

		$typesCount = count($types);
		$acceptableAcceptors = [];

		foreach ($parametersAcceptors as $parametersAcceptor) {
			if ($unpack) {
				$acceptableAcceptors[] = $parametersAcceptor;
				continue;
			}

			$functionParametersMinCount = 0;
			$functionParametersMaxCount = 0;
			foreach ($parametersAcceptor->getParameters() as $parameter) {
				if (!$parameter->isOptional()) {
					$functionParametersMinCount++;
				}

				$functionParametersMaxCount++;
			}

			if ($typesCount < $functionParametersMinCount) {
				continue;
			}

			if (
				!$parametersAcceptor->isVariadic()
				&& $typesCount > $functionParametersMaxCount
			) {
				continue;
			}

			$acceptableAcceptors[] = $parametersAcceptor;
		}

		if (count($acceptableAcceptors) === 0) {
			return GenericParametersAcceptorResolver::resolve($types, self::combineAcceptors($parametersAcceptors));
		}

		if (count($acceptableAcceptors) === 1) {
			return GenericParametersAcceptorResolver::resolve($types, $acceptableAcceptors[0]);
		}

		$winningAcceptors = [];
		$winningCertainty = null;
		foreach ($acceptableAcceptors as $acceptableAcceptor) {
			$isSuperType = TrinaryLogic::createYes();
			$acceptableAcceptor = GenericParametersAcceptorResolver::resolve($types, $acceptableAcceptor);
			foreach ($acceptableAcceptor->getParameters() as $i => $parameter) {
				if (!isset($types[$i])) {
					if (!$unpack || count($types) <= 0) {
						break;
					}

					$type = $types[array_key_last($types)];
				} else {
					$type = $types[$i];
				}

				if ($parameter->getType() instanceof MixedType) {
					$isSuperType = $isSuperType->and(TrinaryLogic::createMaybe());
				} else {
					$isSuperType = $isSuperType->and($parameter->getType()->isSuperTypeOf($type)->result);
				}
			}

			if ($isSuperType->no()) {
				continue;
			}

			if ($winningCertainty === null) {
				$winningAcceptors[] = $acceptableAcceptor;
				$winningCertainty = $isSuperType;
			} else {
				$comparison = $winningCertainty->compareTo($isSuperType);
				if ($comparison === $isSuperType) {
					$winningAcceptors = [$acceptableAcceptor];
					$winningCertainty = $isSuperType;
				} elseif ($comparison === null) {
					$winningAcceptors[] = $acceptableAcceptor;
				}
			}
		}

		if (count($winningAcceptors) === 0) {
			return GenericParametersAcceptorResolver::resolve($types, self::combineAcceptors($acceptableAcceptors));
		}

		return GenericParametersAcceptorResolver::resolve($types, self::combineAcceptors($winningAcceptors));
	}

	/**
	 * @param ParametersAcceptor[] $acceptors
	 */
	public static function combineAcceptors(array $acceptors): ExtendedParametersAcceptor
	{
		if (count($acceptors) === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}
		if (count($acceptors) === 1) {
			return self::wrapAcceptor($acceptors[0]);
		}

		$minimumNumberOfParameters = null;
		foreach ($acceptors as $acceptor) {
			$acceptorParametersMinCount = 0;
			foreach ($acceptor->getParameters() as $parameter) {
				if ($parameter->isOptional()) {
					continue;
				}

				$acceptorParametersMinCount++;
			}

			if ($minimumNumberOfParameters !== null && $minimumNumberOfParameters <= $acceptorParametersMinCount) {
				continue;
			}

			$minimumNumberOfParameters = $acceptorParametersMinCount;
		}

		$parameters = [];
		$isVariadic = false;
		$returnTypes = [];
		$phpDocReturnTypes = [];
		$nativeReturnTypes = [];
		$callableOccurred = false;
		$throwPoints = [];
		$isPure = TrinaryLogic::createNo();
		$impurePoints = [];
		$invalidateExpressions = [];
		$usedVariables = [];
		$acceptsNamedArguments = TrinaryLogic::createNo();
		$mustUseReturnValue = TrinaryLogic::createMaybe();

		foreach ($acceptors as $acceptor) {
			$returnTypes[] = $acceptor->getReturnType();

			if ($acceptor instanceof ExtendedParametersAcceptor) {
				$phpDocReturnTypes[] = $acceptor->getPhpDocReturnType();
				$nativeReturnTypes[] = $acceptor->getNativeReturnType();
			}
			if ($acceptor instanceof CallableParametersAcceptor) {
				$callableOccurred = true;
				$throwPoints = array_merge($throwPoints, $acceptor->getThrowPoints());
				$isPure = $isPure->or($acceptor->isPure());
				$impurePoints = array_merge($impurePoints, $acceptor->getImpurePoints());
				$invalidateExpressions = array_merge($invalidateExpressions, $acceptor->getInvalidateExpressions());
				$usedVariables = array_merge($usedVariables, $acceptor->getUsedVariables());
				$acceptsNamedArguments = $acceptsNamedArguments->or($acceptor->acceptsNamedArguments());
				$mustUseReturnValue = $mustUseReturnValue->or($acceptor->mustUseReturnValue());
			}
			$isVariadic = $isVariadic || $acceptor->isVariadic();

			foreach ($acceptor->getParameters() as $i => $parameter) {
				if (!isset($parameters[$i])) {
					$parameters[$i] = new ExtendedDummyParameter(
						$parameter->getName(),
						$parameter->getType(),
						$i + 1 > $minimumNumberOfParameters,
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue(),
						$parameter instanceof ExtendedParameterReflection ? $parameter->getNativeType() : new MixedType(),
						$parameter instanceof ExtendedParameterReflection ? $parameter->getPhpDocType() : new MixedType(),
						$parameter instanceof ExtendedParameterReflection ? $parameter->getOutType() : null,
						$parameter instanceof ExtendedParameterReflection ? $parameter->isImmediatelyInvokedCallable() : TrinaryLogic::createMaybe(),
						$parameter instanceof ExtendedParameterReflection ? $parameter->getClosureThisType() : null,
						$parameter instanceof ExtendedParameterReflection ? $parameter->getAttributes() : [],
					);
					continue;
				}

				$isVariadic = $parameters[$i]->isVariadic() || $parameter->isVariadic();
				$defaultValueLeft = $parameters[$i]->getDefaultValue();
				$defaultValueRight = $parameter->getDefaultValue();
				if ($defaultValueLeft !== null && $defaultValueRight !== null) {
					$defaultValue = TypeCombinator::union($defaultValueLeft, $defaultValueRight);
				} else {
					$defaultValue = null;
				}

				$type = TypeCombinator::union($parameters[$i]->getType(), $parameter->getType());
				$nativeType = $parameters[$i]->getNativeType();
				$phpDocType = $parameters[$i]->getPhpDocType();
				$outType = $parameters[$i]->getOutType();
				$immediatelyInvokedCallable = $parameters[$i]->isImmediatelyInvokedCallable();
				$closureThisType = $parameters[$i]->getClosureThisType();
				$attributes = $parameters[$i]->getAttributes();
				if ($parameter instanceof ExtendedParameterReflection) {
					$nativeType = TypeCombinator::union($nativeType, $parameter->getNativeType());
					$phpDocType = TypeCombinator::union($phpDocType, $parameter->getPhpDocType());

					if ($parameter->getOutType() !== null) {
						$outType = $outType === null ? null : TypeCombinator::union($outType, $parameter->getOutType());
					} else {
						$outType = null;
					}

					if ($parameter->getClosureThisType() !== null && $closureThisType !== null) {
						$closureThisType = TypeCombinator::union($closureThisType, $parameter->getClosureThisType());
					} else {
						$closureThisType = null;
					}

					$immediatelyInvokedCallable = $parameter->isImmediatelyInvokedCallable()->or($immediatelyInvokedCallable);
					$attributes = array_merge($attributes, $parameter->getAttributes());
				} else {
					$nativeType = new MixedType();
					$phpDocType = $type;
					$outType = null;
					$immediatelyInvokedCallable = TrinaryLogic::createMaybe();
					$closureThisType = null;
				}

				$parameters[$i] = new ExtendedDummyParameter(
					$parameters[$i]->getName() !== $parameter->getName() ? sprintf('%s|%s', $parameters[$i]->getName(), $parameter->getName()) : $parameter->getName(),
					$type,
					$i + 1 > $minimumNumberOfParameters,
					$parameters[$i]->passedByReference()->combine($parameter->passedByReference()),
					$isVariadic,
					$defaultValue,
					$nativeType,
					$phpDocType,
					$outType,
					$immediatelyInvokedCallable,
					$closureThisType,
					$attributes,
				);

				if ($isVariadic) {
					$parameters = array_slice($parameters, 0, $i + 1);
					break;
				}
			}
		}

		$returnType = TypeCombinator::union(...$returnTypes);
		$phpDocReturnType = $phpDocReturnTypes === [] ? null : TypeCombinator::union(...$phpDocReturnTypes);
		$nativeReturnType = $nativeReturnTypes === [] ? null : TypeCombinator::union(...$nativeReturnTypes);

		if ($callableOccurred) {
			return new ExtendedCallableFunctionVariant(
				TemplateTypeMap::createEmpty(),
				null,
				array_values($parameters),
				$isVariadic,
				$returnType,
				$phpDocReturnType ?? $returnType,
				$nativeReturnType ?? new MixedType(),
				null,
				$throwPoints,
				$isPure,
				$impurePoints,
				$invalidateExpressions,
				$usedVariables,
				$acceptsNamedArguments,
				$mustUseReturnValue,
			);
		}

		return new ExtendedFunctionVariant(
			TemplateTypeMap::createEmpty(),
			null,
			array_values($parameters),
			$isVariadic,
			$returnType,
			$phpDocReturnType ?? $returnType,
			$nativeReturnType ?? new MixedType(),
		);
	}

	private static function wrapAcceptor(ParametersAcceptor $acceptor): ExtendedParametersAcceptor
	{
		if ($acceptor instanceof ExtendedParametersAcceptor) {
			return $acceptor;
		}

		if ($acceptor instanceof CallableParametersAcceptor) {
			return new ExtendedCallableFunctionVariant(
				$acceptor->getTemplateTypeMap(),
				$acceptor->getResolvedTemplateTypeMap(),
				array_map(static fn (ParameterReflection $parameter): ExtendedParameterReflection => self::wrapParameter($parameter), $acceptor->getParameters()),
				$acceptor->isVariadic(),
				$acceptor->getReturnType(),
				$acceptor->getReturnType(),
				new MixedType(),
				TemplateTypeVarianceMap::createEmpty(),
				$acceptor->getThrowPoints(),
				$acceptor->isPure(),
				$acceptor->getImpurePoints(),
				$acceptor->getInvalidateExpressions(),
				$acceptor->getUsedVariables(),
				$acceptor->acceptsNamedArguments(),
				$acceptor->mustUseReturnValue(),
			);
		}

		return new ExtendedFunctionVariant(
			$acceptor->getTemplateTypeMap(),
			$acceptor->getResolvedTemplateTypeMap(),
			array_map(static fn (ParameterReflection $parameter): ExtendedParameterReflection => self::wrapParameter($parameter), $acceptor->getParameters()),
			$acceptor->isVariadic(),
			$acceptor->getReturnType(),
			$acceptor->getReturnType(),
			new MixedType(),
			TemplateTypeVarianceMap::createEmpty(),
		);
	}

	private static function wrapParameter(ParameterReflection $parameter): ExtendedParameterReflection
	{
		return $parameter instanceof ExtendedParameterReflection ? $parameter : new ExtendedDummyParameter(
			$parameter->getName(),
			$parameter->getType(),
			$parameter->isOptional(),
			$parameter->passedByReference(),
			$parameter->isVariadic(),
			$parameter->getDefaultValue(),
			new MixedType(),
			$parameter->getType(),
			null,
			TrinaryLogic::createMaybe(),
			null,
			[],
		);
	}

	private static function getCurlOptValueType(int $curlOpt): ?Type
	{
		if (defined('CURLOPT_SSL_VERIFYHOST') && $curlOpt === CURLOPT_SSL_VERIFYHOST) {
			return new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(2)]);
		}

		$boolConstants = [
			'CURLOPT_AUTOREFERER',
			'CURLOPT_COOKIESESSION',
			'CURLOPT_CERTINFO',
			'CURLOPT_CONNECT_ONLY',
			'CURLOPT_CRLF',
			'CURLOPT_DISALLOW_USERNAME_IN_URL',
			'CURLOPT_DNS_SHUFFLE_ADDRESSES',
			'CURLOPT_HAPROXYPROTOCOL',
			'CURLOPT_SSH_COMPRESSION',
			'CURLOPT_DNS_USE_GLOBAL_CACHE',
			'CURLOPT_FAILONERROR',
			'CURLOPT_SSL_FALSESTART',
			'CURLOPT_FILETIME',
			'CURLOPT_FOLLOWLOCATION',
			'CURLOPT_FORBID_REUSE',
			'CURLOPT_FRESH_CONNECT',
			'CURLOPT_FTP_USE_EPRT',
			'CURLOPT_FTP_USE_EPSV',
			'CURLOPT_FTP_CREATE_MISSING_DIRS',
			'CURLOPT_FTPAPPEND',
			'CURLOPT_TCP_NODELAY',
			'CURLOPT_FTPASCII',
			'CURLOPT_FTPLISTONLY',
			'CURLOPT_HEADER',
			'CURLOPT_HTTP09_ALLOWED',
			'CURLOPT_HTTPGET',
			'CURLOPT_HTTPPROXYTUNNEL',
			'CURLOPT_HTTP_CONTENT_DECODING',
			'CURLOPT_KEEP_SENDING_ON_ERROR',
			'CURLOPT_MUTE',
			'CURLOPT_NETRC',
			'CURLOPT_NOBODY',
			'CURLOPT_NOPROGRESS',
			'CURLOPT_NOSIGNAL',
			'CURLOPT_PATH_AS_IS',
			'CURLOPT_PIPEWAIT',
			'CURLOPT_POST',
			'CURLOPT_PUT',
			'CURLOPT_RETURNTRANSFER',
			'CURLOPT_SASL_IR',
			'CURLOPT_SSL_ENABLE_ALPN',
			'CURLOPT_SSL_ENABLE_NPN',
			'CURLOPT_SSL_VERIFYPEER',
			'CURLOPT_SSL_VERIFYSTATUS',
			'CURLOPT_PROXY_SSL_VERIFYPEER',
			'CURLOPT_SUPPRESS_CONNECT_HEADERS',
			'CURLOPT_TCP_FASTOPEN',
			'CURLOPT_TFTP_NO_OPTIONS',
			'CURLOPT_TRANSFERTEXT',
			'CURLOPT_UNRESTRICTED_AUTH',
			'CURLOPT_UPLOAD',
			'CURLOPT_VERBOSE',
		];
		foreach ($boolConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new BooleanType();
			}
		}

		$intConstants = [
			'CURLOPT_BUFFERSIZE',
			'CURLOPT_CONNECTTIMEOUT',
			'CURLOPT_CONNECTTIMEOUT_MS',
			'CURLOPT_DNS_CACHE_TIMEOUT',
			'CURLOPT_EXPECT_100_TIMEOUT_MS',
			'CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS',
			'CURLOPT_FTPSSLAUTH',
			'CURLOPT_HEADEROPT',
			'CURLOPT_HTTP_VERSION',
			'CURLOPT_HTTPAUTH',
			'CURLOPT_INFILESIZE',
			'CURLOPT_LOW_SPEED_LIMIT',
			'CURLOPT_LOW_SPEED_TIME',
			'CURLOPT_MAXCONNECTS',
			'CURLOPT_MAXREDIRS',
			'CURLOPT_PORT',
			'CURLOPT_POSTREDIR',
			'CURLOPT_PROTOCOLS',
			'CURLOPT_PROXYAUTH',
			'CURLOPT_PROXYPORT',
			'CURLOPT_PROXYTYPE',
			'CURLOPT_REDIR_PROTOCOLS',
			'CURLOPT_RESUME_FROM',
			'CURLOPT_SOCKS5_AUTH',
			'CURLOPT_SSL_OPTIONS',
			'CURLOPT_SSL_VERIFYHOST',
			'CURLOPT_SSLVERSION',
			'CURLOPT_PROXY_SSL_OPTIONS',
			'CURLOPT_PROXY_SSL_VERIFYHOST',
			'CURLOPT_PROXY_SSLVERSION',
			'CURLOPT_STREAM_WEIGHT',
			'CURLOPT_TCP_KEEPALIVE',
			'CURLOPT_TCP_KEEPIDLE',
			'CURLOPT_TCP_KEEPINTVL',
			'CURLOPT_TIMECONDITION',
			'CURLOPT_TIMEOUT',
			'CURLOPT_TIMEOUT_MS',
			'CURLOPT_TIMEVALUE',
			'CURLOPT_TIMEVALUE_LARGE',
			'CURLOPT_MAX_RECV_SPEED_LARGE',
			'CURLOPT_SSH_AUTH_TYPES',
			'CURLOPT_IPRESOLVE',
			'CURLOPT_FTP_FILEMETHOD',
		];
		foreach ($intConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new IntegerType();
			}
		}

		$nullableStringConstants = [
			'CURLOPT_CUSTOMREQUEST',
			'CURLOPT_DNS_INTERFACE',
			'CURLOPT_DNS_LOCAL_IP4',
			'CURLOPT_DNS_LOCAL_IP6',
			'CURLOPT_DOH_URL',
			'CURLOPT_FTP_ACCOUNT',
			'CURLOPT_FTPPORT',
			'CURLOPT_HSTS',
			'CURLOPT_KRBLEVEL',
			'CURLOPT_RANGE',
			'CURLOPT_RTSP_SESSION_ID',
			'CURLOPT_UNIX_SOCKET_PATH',
			'CURLOPT_XOAUTH2_BEARER',
		];
		foreach ($nullableStringConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new UnionType([
					new NullType(),
					TypeCombinator::intersect(
						new StringType(),
						new AccessoryNonEmptyStringType(),
					),
				]);
			}
		}

		$nonEmptyStringConstants = [
			'CURLOPT_ABSTRACT_UNIX_SOCKET',
			'CURLOPT_ALTSVC',
			'CURLOPT_AWS_SIGV4',
			'CURLOPT_CAINFO',
			'CURLOPT_CAPATH',
			'CURLOPT_COOKIE',
			'CURLOPT_COOKIEJAR',
			'CURLOPT_COOKIELIST',
			'CURLOPT_DEFAULT_PROTOCOL',
			'CURLOPT_DNS_SERVERS',
			'CURLOPT_EGDSOCKET',
			'CURLOPT_FTP_ALTERNATIVE_TO_USER',
			'CURLOPT_INTERFACE',
			'CURLOPT_KEYPASSWD',
			'CURLOPT_KRB4LEVEL',
			'CURLOPT_LOGIN_OPTIONS',
			'CURLOPT_MAIL_AUTH',
			'CURLOPT_MAIL_FROM',
			'CURLOPT_NOPROXY',
			'CURLOPT_PASSWORD',
			'CURLOPT_PINNEDPUBLICKEY',
			'CURLOPT_PROTOCOLS_STR',
			'CURLOPT_PROXY_CAINFO',
			'CURLOPT_PROXY_CAPATH',
			'CURLOPT_PROXY_CRLFILE',
			'CURLOPT_PROXY_ISSUERCERT',
			'CURLOPT_PROXY_KEYPASSWD',
			'CURLOPT_PROXY_PINNEDPUBLICKEY',
			'CURLOPT_PROXY_SERVICE_NAME',
			'CURLOPT_PROXY_SSL_CIPHER_LIST',
			'CURLOPT_PROXY_SSLCERT',
			'CURLOPT_PROXY_SSLCERTTYPE',
			'CURLOPT_PROXY_SSLKEY',
			'CURLOPT_PROXY_SSLKEYTYPE',
			'CURLOPT_PROXY_TLS13_CIPHERS',
			'CURLOPT_PROXY_TLSAUTH_PASSWORD',
			'CURLOPT_PROXY_TLSAUTH_TYPE',
			'CURLOPT_PROXY_TLSAUTH_USERNAME',
			'CURLOPT_PROXYPASSWORD',
			'CURLOPT_PROXYUSERNAME',
			'CURLOPT_PROXYUSERPWD',
			'CURLOPT_RANDOM_FILE',
			'CURLOPT_REDIR_PROTOCOLS_STR',
			'CURLOPT_REFERER',
			'CURLOPT_REQUEST_TARGET',
			'CURLOPT_RTSP_STREAM_URI',
			'CURLOPT_RTSP_TRANSPORT',
			'CURLOPT_SASL_AUTHZID',
			'CURLOPT_SERVICE_NAME',
			'CURLOPT_SOCKS5_GSSAPI_SERVICE',
			'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5',
			'CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256',
			'CURLOPT_SSH_PRIVATE_KEYFILE',
			'CURLOPT_SSH_PUBLIC_KEYFILE',
			'CURLOPT_SSL_CIPHER_LIST',
			'CURLOPT_SSL_EC_CURVES',
			'CURLOPT_SSLCERT',
			'CURLOPT_SSLCERTPASSWD',
			'CURLOPT_SSLCERTTYPE',
			'CURLOPT_SSLENGINE',
			'CURLOPT_SSLENGINE_DEFAULT',
			'CURLOPT_SSLKEY',
			'CURLOPT_SSLKEYPASSWD',
			'CURLOPT_SSLKEYTYPE',
			'CURLOPT_TLS13_CIPHERS',
			'CURLOPT_TLSAUTH_PASSWORD',
			'CURLOPT_TLSAUTH_TYPE',
			'CURLOPT_TLSAUTH_USERNAME',
			'CURLOPT_TRANSFER_ENCODING',
			'CURLOPT_URL',
			'CURLOPT_USERAGENT',
			'CURLOPT_USERNAME',
			'CURLOPT_USERPWD',
		];
		foreach ($nonEmptyStringConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				);
			}
		}

		$stringConstants = [
			'CURLOPT_COOKIEFILE',
			'CURLOPT_ENCODING', // Alias: CURLOPT_ACCEPT_ENCODING
			'CURLOPT_PRE_PROXY',
			'CURLOPT_PRIVATE',
			'CURLOPT_PROXY',
		];
		foreach ($stringConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new StringType();
			}
		}

		$intArrayStringKeysConstants = [
			'CURLOPT_HTTPHEADER',
		];
		foreach ($intArrayStringKeysConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new ArrayType(new IntegerType(), new StringType());
			}
		}

		$arrayConstants = [
			'CURLOPT_CONNECT_TO',
			'CURLOPT_HTTP200ALIASES',
			'CURLOPT_POSTQUOTE',
			'CURLOPT_PROXYHEADER',
			'CURLOPT_QUOTE',
			'CURLOPT_RESOLVE',
		];
		foreach ($arrayConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new ArrayType(new MixedType(), new MixedType());
			}
		}

		$arrayOrStringConstants = [
			'CURLOPT_POSTFIELDS',
		];
		foreach ($arrayOrStringConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new UnionType([
					new StringType(),
					new ArrayType(new MixedType(), new MixedType()),
				]);
			}
		}

		$resourceConstants = [
			'CURLOPT_FILE',
			'CURLOPT_INFILE',
			'CURLOPT_STDERR',
			'CURLOPT_WRITEHEADER',
		];
		foreach ($resourceConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new ResourceType();
			}
		}

		if ($curlOpt === CURLOPT_SHARE) {
			$phpversion = PhpVersionStaticAccessor::getInstance();

			if ($phpversion->supportsCurlShareHandle()) {
				$shareType = new ObjectType('CurlShareHandle');
			} else {
				$shareType = new ResourceType();
			}
			if ($phpversion->supportsCurlSharePersistentHandle()) {
				$shareType = TypeCombinator::union($shareType, new ObjectType('CurlSharePersistentHandle'));
			}

			return $shareType;
		}

		// unknown constant
		return null;
	}

}
