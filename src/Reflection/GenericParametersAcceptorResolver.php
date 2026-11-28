<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\CallableAssertionsHelper;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function count;
use function is_int;

#[ReferencedByTurboExtension(key: 'genericParametersAcceptorResolver')]
final class GenericParametersAcceptorResolver
{

	/**
	 * The parameter with the templates that the sibling argument types
	 * determine resolved, for typing a closure passed to it before its own
	 * type is known. A template that also occurs in a non-covariant position
	 * of the parameter (the T in callable(T): T, an accumulator) is fed by
	 * the closure's own return, so it stays unresolved unless
	 * $accumulatorTypes holds a round of its fixed point
	 * (see resolveAccumulatorTemplateTypes()).
	 *
	 * @param array<int|string, Type> $siblingTypes
	 * @param array<string, Type> $accumulatorTypes
	 */
	public static function resolveParameterFromSiblings(array $siblingTypes, ParametersAcceptor $parametersAcceptor, ParameterReflection $parameter, array $accumulatorTypes = []): ParameterReflection
	{
		$parameterType = $parameter->getType();
		if (!$parameterType->hasTemplateOrLateResolvableType()) {
			return $parameter;
		}

		$siblingTemplateTypes = [];
		foreach (self::resolve($siblingTypes, $parametersAcceptor)->getResolvedTemplateTypeMap()->getTypes() as $name => $type) {
			if ($type instanceof ErrorType) {
				continue;
			}
			$siblingTemplateTypes[$name] = $type;
		}
		foreach ($parameterType->getReferencedTemplateTypes(TemplateTypeVariance::createContravariant()) as $reference) {
			if ($reference->getPositionVariance()->covariant()) {
				continue;
			}
			unset($siblingTemplateTypes[$reference->getType()->getName()]);
		}
		$siblingTemplateTypes = array_merge($siblingTemplateTypes, $accumulatorTypes);
		if ($siblingTemplateTypes === []) {
			return $parameter;
		}

		$parameterType = TemplateTypeHelper::resolveTemplateTypes($parameterType, new TemplateTypeMap($siblingTemplateTypes), TemplateTypeVarianceMap::createEmpty(), TemplateTypeVariance::createContravariant());

		return new ExtendedDummyParameter(
			$parameter->getName(),
			$parameterType,
			$parameter->isOptional(),
			$parameter->passedByReference(),
			$parameter->isVariadic(),
			$parameter->getDefaultValue(),
			$parameter instanceof ExtendedParameterReflection ? $parameter->getNativeType() : new MixedType(),
			$parameterType,
			null,
			TrinaryLogic::createMaybe(),
			null,
			[],
			null,
			TrinaryLogic::createNo(),
			TrinaryLogic::createNo(),
		);
	}

	/**
	 * The templates a callable parameter passes to the closure given for it
	 * (the T in callable(T): U) and the templates it receives back from it
	 * (the U), as two sets of template names.
	 *
	 * @return array{array<string, true>, array<string, true>}
	 */
	public static function getCallableTemplateFlow(ParameterReflection $parameter): array
	{
		$parameterType = $parameter->getType();
		if (!$parameterType->hasTemplateOrLateResolvableType()) {
			return [[], []];
		}

		$passed = [];
		$returned = [];
		foreach ($parameterType->getReferencedTemplateTypes(TemplateTypeVariance::createContravariant()) as $reference) {
			$name = $reference->getType()->getName();
			if ($reference->getPositionVariance()->covariant()) {
				$passed[$name] = true;
			} else {
				$returned[$name] = true;
			}
		}

		return [$passed, $returned];
	}

	/**
	 * The accumulators - templates that callable parameters pass to their
	 * closures and receive back from them, as the TCarry in
	 * callable(TCarry, T): TCarry, or the T and U of callable(T): U and
	 * callable(U): T together (see getCallableTemplateFlow()) - each with the
	 * type the argument types determine. Without the closures among $argTypes
	 * this is the seed of the accumulators' fixed point, with the closures
	 * typed by the previous round it is the next round. An accumulator the
	 * arguments do not determine is left out.
	 *
	 * @param array<int|string, Type> $argTypes
	 * @param array<string, true> $accumulators
	 * @return array<string, Type>
	 */
	public static function resolveAccumulatorTemplateTypes(array $argTypes, ParametersAcceptor $parametersAcceptor, array $accumulators): array
	{
		if ($accumulators === []) {
			return [];
		}

		$resolvedTemplateTypeMap = self::resolve($argTypes, $parametersAcceptor)->getResolvedTemplateTypeMap();
		$types = [];
		foreach (array_keys($accumulators) as $name) {
			$type = $resolvedTemplateTypeMap->getType($name);
			if ($type === null || $type instanceof ErrorType) {
				continue;
			}
			$types[$name] = self::isUnresolvableAccumulatorType($type, $parametersAcceptor->getTemplateTypeMap()) ? new MixedType() : $type;
		}

		return $types;
	}

	/**
	 * An accumulator type with an error type, from a closure body that does
	 * not type with the accumulator, or with a template of the called
	 * function itself, which a closure parameter the arguments leave
	 * unresolved returns, cannot be resolved further: it widens to mixed.
	 */
	private static function isUnresolvableAccumulatorType(Type $type, TemplateTypeMap $calleeTemplateTypeMap): bool
	{
		$unresolvable = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($calleeTemplateTypeMap, &$unresolvable): Type {
			if ($type instanceof ErrorType) {
				$unresolvable = true;
			} elseif ($type instanceof TemplateType) {
				$calleeTemplateType = $calleeTemplateTypeMap->getType($type->getName());
				if ($calleeTemplateType instanceof TemplateType && $calleeTemplateType->getScope()->equals($type->getScope())) {
					$unresolvable = true;
				}
			}

			return $unresolvable ? $type : $traverse($type);
		});

		return $unresolvable;
	}

	/**
	 * @api
	 * @param array<int|string, Type> $argTypes
	 */
	public static function resolve(array $argTypes, ParametersAcceptor $parametersAcceptor): ExtendedParametersAcceptor
	{
		$typeMap = TemplateTypeMap::createEmpty();
		$passedArgs = [];

		$parameters = $parametersAcceptor->getParameters();
		$namedArgTypes = [];
		foreach ($argTypes as $i => $argType) {
			if (is_int($i)) {
				if (isset($parameters[$i])) {
					$namedArgTypes[$parameters[$i]->getName()] = $argType;
					continue;
				}
				if (count($parameters) > 0) {
					$lastParameter = array_last($parameters);
					if ($lastParameter->isVariadic()) {
						$parameterName = $lastParameter->getName();
						if (array_key_exists($parameterName, $namedArgTypes)) {
							$namedArgTypes[$parameterName] = TypeCombinator::union($namedArgTypes[$parameterName], $argType);
							continue;
						}
						$namedArgTypes[$parameterName] = $argType;
					}
				}
				continue;
			}

			$namedArgTypes[$i] = $argType;
		}

		// type predicates of passed callables determine their template types exactly,
		// so they are inferred first and substituted into the parameter types —
		// the remaining template types are then inferred from what is left over,
		// e.g. in partition(iterable<T0|T1> $values, callable(T0|T1): ($value is T0 ? true : false) $predicate)
		// called with iterable<int|string> and is_int(...), T0 becomes int and T1 string
		// null when no passed callable has a type predicate, which is almost always
		$predicateTypeMap = null;
		foreach ($parameters as $param) {
			$paramName = $param->getName();
			if (!isset($namedArgTypes[$paramName])) {
				continue;
			}

			$paramPredicateTypeMap = self::inferPredicateTemplateTypes($param->getType(), $namedArgTypes[$paramName]);
			if ($paramPredicateTypeMap === null || $paramPredicateTypeMap->isEmpty()) {
				continue;
			}

			$predicateTypeMap = $predicateTypeMap === null ? $paramPredicateTypeMap : $predicateTypeMap->union($paramPredicateTypeMap);
		}

		foreach ($parameters as $param) {
			$paramName = $param->getName();
			if (isset($namedArgTypes[$paramName])) {
				$argType = $namedArgTypes[$paramName];
			} elseif ($param->getDefaultValue() !== null) {
				$argType = $param->getDefaultValue();
			} elseif ($param->isVariadic()) {
				$argType = new NeverType(true);
			} else {
				continue;
			}

			$paramType = $predicateTypeMap === null ? $param->getType() : self::resolvePredicateTemplateTypes($param->getType(), $predicateTypeMap);
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
			$passedArgs['$' . $paramName] = $argType;
		}

		if ($predicateTypeMap !== null) {
			$typeMap = $typeMap->union($predicateTypeMap);
		}

		$returnType = $parametersAcceptor->getReturnType();
		if (
			$returnType instanceof ConditionalTypeForParameter
			&& !$returnType->isNegated()
			&& array_key_exists($returnType->getParameterName(), $passedArgs)
		) {
			$paramType = $predicateTypeMap === null ? $returnType->getTarget() : self::resolvePredicateTemplateTypes($returnType->getTarget(), $predicateTypeMap);
			$argType = $passedArgs[$returnType->getParameterName()];
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
		}

		$resolvedTemplateTypeMap = new TemplateTypeMap(array_merge(
			$parametersAcceptor->getTemplateTypeMap()->map(static fn (string $name, Type $type): Type => new ErrorType())->getTypes(),
			$typeMap->getTypes(),
			self::resolveContravariantReturnTemplateTypes($returnType, $typeMap->getUpperBoundTypes()),
		));

		$originalParametersAcceptor = $parametersAcceptor;

		if (!$parametersAcceptor instanceof ExtendedParametersAcceptor) {
			$parametersAcceptor = new ExtendedFunctionVariant(
				$parametersAcceptor->getTemplateTypeMap(),
				$parametersAcceptor->getResolvedTemplateTypeMap(),
				array_map(static fn (ParameterReflection $parameter): ExtendedParameterReflection => new ExtendedDummyParameter(
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
					null,
					TrinaryLogic::createNo(),
					TrinaryLogic::createNo(),
				), $parameters),
				$parametersAcceptor->isVariadic(),
				$returnType,
				$returnType,
				new MixedType(),
				TemplateTypeVarianceMap::createEmpty(),
			);
		}

		$result = new ResolvedFunctionVariantWithOriginal(
			$parametersAcceptor,
			$resolvedTemplateTypeMap,
			$parametersAcceptor->getCallSiteVarianceMap(),
			$passedArgs,
		);
		if ($originalParametersAcceptor instanceof CallableParametersAcceptor) {
			return new ResolvedFunctionVariantWithCallable(
				$result,
				$originalParametersAcceptor->getThrowPoints(),
				$originalParametersAcceptor->isPure(),
				$originalParametersAcceptor->getImpurePoints(),
				$originalParametersAcceptor->getInvalidateExpressions(),
				$originalParametersAcceptor->getUsedVariables(),
				$originalParametersAcceptor->acceptsNamedArguments(),
				$originalParametersAcceptor->mustUseReturnValue(),
				$originalParametersAcceptor->getAsserts(),
				$originalParametersAcceptor->isStaticClosure(),
			);
		}

		return $result;
	}

	/**
	 * A template that occurs in the return type only in contravariant positions, such as
	 * the T in callable(T): void, makes the return type more precise the larger it is, so it
	 * resolves to its upper bound rather than to the lower bound getTypes() prefers.
	 *
	 * @param array<string, Type> $upperBoundTypes
	 * @return array<string, Type>
	 */
	private static function resolveContravariantReturnTemplateTypes(Type $returnType, array $upperBoundTypes): array
	{
		if ($upperBoundTypes === []) {
			return [];
		}

		$contravariantOnly = [];
		foreach ($returnType->getReferencedTemplateTypes(TemplateTypeVariance::createCovariant()) as $reference) {
			$name = $reference->getType()->getName();
			if (!array_key_exists($name, $upperBoundTypes)) {
				continue;
			}

			$contravariantOnly[$name] = ($contravariantOnly[$name] ?? true) && $reference->getPositionVariance()->contravariant();
		}

		$resolved = [];
		foreach ($contravariantOnly as $name => $isContravariantOnly) {
			if (!$isContravariantOnly) {
				continue;
			}

			$resolved[$name] = $upperBoundTypes[$name];
		}

		return $resolved;
	}

	/**
	 * @return TemplateTypeMap|null null when the parameter type is no callable with a type predicate
	 */
	private static function inferPredicateTemplateTypes(Type $paramType, Type $argType): ?TemplateTypeMap
	{
		$typeMap = null;
		foreach ($paramType instanceof UnionType ? $paramType->getTypes() : [$paramType] as $innerType) {
			if (!$innerType instanceof CallableParametersAcceptor) {
				continue;
			}
			if ($innerType->getAsserts()->getAll() === []) {
				continue;
			}

			// asked only for parameters with asserts - it reflects the class named by
			// a callable-like argument
			$typeMap ??= TemplateTypeMap::createEmpty();
			if (!$argType->isCallable()->yes()) {
				return $typeMap;
			}

			foreach ($argType->getCallableParametersAcceptors(new OutOfClassScope()) as $receivedAcceptor) {
				$typeMap = $typeMap->union(CallableAssertionsHelper::inferTemplateTypesOnAsserts($innerType, $receivedAcceptor));
			}
		}

		return $typeMap;
	}

	private static function resolvePredicateTemplateTypes(Type $type, TemplateTypeMap $predicateTypeMap): Type
	{
		return TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$predicateTypeMap,
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		);
	}

}
