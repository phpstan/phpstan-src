<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CallableType;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_key_last;
use function array_slice;
use function count;
use function sprintf;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;

/** @api */
class ParametersAcceptorSelector
{

	/**
	 * @template T of ParametersAcceptor
	 * @param T[] $parametersAcceptors
	 * @return T
	 */
	public static function selectSingle(
		array $parametersAcceptors,
	): ParametersAcceptor
	{
		$count = count($parametersAcceptors);
		if ($count === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}
		if ($count !== 1) {
			throw new ShouldNotHappenException('Multiple variants - use selectFromArgs() instead.');
		}

		return $parametersAcceptors[0];
	}

	/**
	 * @param Node\Arg[] $args
	 * @param ParametersAcceptor[] $parametersAcceptors
	 */
	public static function selectFromArgs(
		Scope $scope,
		array $args,
		array $parametersAcceptors,
	): ParametersAcceptor
	{
		$types = [];
		$unpack = false;
		if (
			count($args) > 0
			&& count($parametersAcceptors) > 0
		) {
			$arrayMapArgs = $args[0]->value->getAttribute('arrayMapArgs');
			if ($arrayMapArgs !== null) {
				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				$callbackParameters = [];
				foreach ($arrayMapArgs as $arg) {
					$callbackParameters[] = new DummyParameter('item', $scope->getType($arg->value)->getIterableValueType(), false, PassedByReference::createNo(), false, null);
				}
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
					),
				];
			}

			if (isset($args[0]) && (bool) $args[0]->getAttribute('isArrayFilterArg')) {
				if (isset($args[2])) {
					$mode = $scope->getType($args[2]->value);
					if ($mode instanceof ConstantIntegerType) {
						if ($mode->getValue() === ARRAY_FILTER_USE_KEY) {
							$arrayFilterParameters = [
								new DummyParameter('key', $scope->getType($args[0]->value)->getIterableKeyType(), false, PassedByReference::createNo(), false, null),
							];
						} elseif ($mode->getValue() === ARRAY_FILTER_USE_BOTH) {
							$arrayFilterParameters = [
								new DummyParameter('item', $scope->getType($args[0]->value)->getIterableValueType(), false, PassedByReference::createNo(), false, null),
								new DummyParameter('key', $scope->getType($args[0]->value)->getIterableKeyType(), false, PassedByReference::createNo(), false, null),
							];
						}
					}
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				$parameters[1] = new NativeParameterReflection(
					$parameters[1]->getName(),
					$parameters[1]->isOptional(),
					new CallableType(
						$arrayFilterParameters ?? [
							new DummyParameter('item', $scope->getType($args[0]->value)->getIterableValueType(), false, PassedByReference::createNo(), false, null),
						],
						new MixedType(),
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
					),
				];
			}
		}

		if (count($parametersAcceptors) === 1) {
			$acceptor = $parametersAcceptors[0];
			if (!self::hasAcceptorTemplateOrConditionalType($acceptor)) {
				return $acceptor;
			}
		}

		foreach ($args as $i => $arg) {
			$type = $scope->getType($arg->value);
			$index = $arg->name !== null ? $arg->name->toString() : $i;
			if ($arg->unpack) {
				$unpack = true;
				$types[$index] = $type->getIterableValueType();
			} else {
				$types[$index] = $type;
			}
		}

		return self::selectFromTypes($types, $parametersAcceptors, $unpack);
	}

	private static function hasAcceptorTemplateOrConditionalType(ParametersAcceptor $acceptor): bool
	{
		if (self::hasTemplateOrConditionalType($acceptor->getReturnType())) {
			return true;
		}

		foreach ($acceptor->getParameters() as $parameter) {
			if (!self::hasTemplateOrConditionalType($parameter->getType())) {
				continue;
			}

			return true;
		}

		return false;
	}

	private static function hasTemplateOrConditionalType(Type $type): bool
	{
		$has = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$has): Type {
			if ($type instanceof TemplateType || $type instanceof ConditionalType || $type instanceof ConditionalTypeForParameter) {
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
					$isSuperType = $isSuperType->and($parameter->getType()->isSuperTypeOf($type));
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

		return self::combineAcceptors($winningAcceptors);
	}

	/**
	 * @param ParametersAcceptor[] $acceptors
	 */
	public static function combineAcceptors(array $acceptors): ParametersAcceptor
	{
		if (count($acceptors) === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}
		if (count($acceptors) === 1) {
			return $acceptors[0];
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
		$returnType = null;

		foreach ($acceptors as $acceptor) {
			if ($returnType === null) {
				$returnType = $acceptor->getReturnType();
			} else {
				$returnType = TypeCombinator::union($returnType, $acceptor->getReturnType());
			}
			$isVariadic = $isVariadic || $acceptor->isVariadic();

			foreach ($acceptor->getParameters() as $i => $parameter) {
				if (!isset($parameters[$i])) {
					$parameters[$i] = new NativeParameterReflection(
						$parameter->getName(),
						$i + 1 > $minimumNumberOfParameters,
						$parameter->getType(),
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue(),
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

				$parameters[$i] = new NativeParameterReflection(
					$parameters[$i]->getName() !== $parameter->getName() ? sprintf('%s|%s', $parameters[$i]->getName(), $parameter->getName()) : $parameter->getName(),
					$i + 1 > $minimumNumberOfParameters,
					TypeCombinator::union($parameters[$i]->getType(), $parameter->getType()),
					$parameters[$i]->passedByReference()->combine($parameter->passedByReference()),
					$isVariadic,
					$defaultValue,
				);

				if ($isVariadic) {
					$parameters = array_slice($parameters, 0, $i + 1);
					break;
				}
			}
		}

		return new FunctionVariant(
			TemplateTypeMap::createEmpty(),
			null,
			$parameters,
			$isVariadic,
			$returnType,
		);
	}

}
