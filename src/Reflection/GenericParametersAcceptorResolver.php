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

			$paramType = self::resolvePredicateTemplateTypes($param->getType(), $predicateTypeMap);
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
			$passedArgs['$' . $param->getName()] = $argType;
		}

		$typeMap = $typeMap->union($predicateTypeMap);

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
