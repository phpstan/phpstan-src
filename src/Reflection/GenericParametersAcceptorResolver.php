<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CallableAssertionsHelper;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function count;
use function is_int;

final class GenericParametersAcceptorResolver
{

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
		$predicateTypeMap = TemplateTypeMap::createEmpty();
		foreach ($parameters as $param) {
			if (!isset($namedArgTypes[$param->getName()])) {
				continue;
			}

			$predicateTypeMap = $predicateTypeMap->union(
				self::inferPredicateTemplateTypes($param->getType(), $namedArgTypes[$param->getName()]),
			);
		}

		// template types that appear in an invariant position (e.g. T inside an
		// invariant Column<T>) are determined exactly by that argument, so they
		// are inferred first and substituted into the remaining parameter types —
		// the other occurrences of the same template type are then validated
		// against the anchored type instead of widening it into a union.
		// e.g. in where(Column<T> $column, T $value) called with IntColumn (Column<int>)
		// and 'foo', T becomes int and the error is reported on $value, not $column.
		$anchorTypeMap = TemplateTypeMap::createEmpty();
		$invariantNamesByParam = [];
		foreach ($parameters as $param) {
			if (!isset($namedArgTypes[$param->getName()])) {
				continue;
			}

			$invariantNames = self::getInvariantTemplateTypeNames($param->getType());
			if (count($invariantNames) === 0) {
				continue;
			}

			$invariantNamesByParam[$param->getName()] = $invariantNames;

			$paramType = self::resolvePredicateTemplateTypes($param->getType(), $predicateTypeMap);
			$inferred = $paramType->inferTemplateTypes($namedArgTypes[$param->getName()]);
			$kept = [];
			foreach ($inferred->getTypes() as $name => $type) {
				if (!array_key_exists($name, $invariantNames)) {
					continue;
				}

				$kept[$name] = $type;
			}

			if (count($kept) === 0) {
				continue;
			}

			$anchorTypeMap = $anchorTypeMap->union(new TemplateTypeMap($kept));
		}

		foreach ($parameters as $param) {
			if (isset($namedArgTypes[$param->getName()])) {
				$argType = $namedArgTypes[$param->getName()];
			} elseif ($param->getDefaultValue() !== null) {
				$argType = $param->getDefaultValue();
			} elseif ($param->isVariadic()) {
				$argType = new NeverType(true);
			} else {
				continue;
			}

			// Substitute the anchored types into the consumer parameters so they
			// are validated against the anchored type, but keep them out of the
			// parameter that anchored them — its own (possibly dependent) template
			// types still need to be inferred from the original parameter type.
			$paramSubstitutionMap = $predicateTypeMap->union($anchorTypeMap);
			foreach (array_keys($invariantNamesByParam[$param->getName()] ?? []) as $name) {
				$paramSubstitutionMap = $paramSubstitutionMap->unsetType($name);
			}

			$paramType = self::resolvePredicateTemplateTypes($param->getType(), $paramSubstitutionMap);
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
			$passedArgs['$' . $param->getName()] = $argType;
		}

		$typeMap = $typeMap->union($predicateTypeMap)->union($anchorTypeMap);

		$returnType = $parametersAcceptor->getReturnType();
		if (
			$returnType instanceof ConditionalTypeForParameter
			&& !$returnType->isNegated()
			&& array_key_exists($returnType->getParameterName(), $passedArgs)
		) {
			$paramType = self::resolvePredicateTemplateTypes($returnType->getTarget(), $predicateTypeMap);
			$argType = $passedArgs[$returnType->getParameterName()];
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
		}

		$resolvedTemplateTypeMap = new TemplateTypeMap(array_merge(
			$parametersAcceptor->getTemplateTypeMap()->map(static fn (string $name, Type $type): Type => new ErrorType())->getTypes(),
			$typeMap->getTypes(),
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
	 * @return array<string, true> names of template types that occur in an invariant position in $paramType
	 */
	private static function getInvariantTemplateTypeNames(Type $paramType): array
	{
		$names = [];
		foreach ($paramType->getReferencedTemplateTypes(TemplateTypeVariance::createCovariant()) as $reference) {
			if (!$reference->getPositionVariance()->invariant()) {
				continue;
			}

			$names[$reference->getType()->getName()] = true;
		}

		return $names;
	}

	private static function inferPredicateTemplateTypes(Type $paramType, Type $argType): TemplateTypeMap
	{
		$typeMap = TemplateTypeMap::createEmpty();
		if (!$argType->isCallable()->yes()) {
			return $typeMap;
		}

		foreach ($paramType instanceof UnionType ? $paramType->getTypes() : [$paramType] as $innerType) {
			if (!$innerType instanceof CallableParametersAcceptor) {
				continue;
			}
			if ($innerType->getAsserts()->getAll() === []) {
				continue;
			}

			foreach ($argType->getCallableParametersAcceptors(new OutOfClassScope()) as $receivedAcceptor) {
				$typeMap = $typeMap->union(CallableAssertionsHelper::inferTemplateTypesOnAsserts($innerType, $receivedAcceptor));
			}
		}

		return $typeMap;
	}

	private static function resolvePredicateTemplateTypes(Type $type, TemplateTypeMap $predicateTypeMap): Type
	{
		if ($predicateTypeMap->isEmpty()) {
			return $type;
		}

		return TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$predicateTypeMap,
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		);
	}

}
