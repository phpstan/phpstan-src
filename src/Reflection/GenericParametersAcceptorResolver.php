<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\DummyParameterWithPhpDocs;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function is_int;

class GenericParametersAcceptorResolver
{

	/**
	 * @api
	 * @param array<int|string, Type> $argTypes
	 */
	public static function resolve(array $argTypes, ParametersAcceptor $parametersAcceptor): ParametersAcceptorWithPhpDocs
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
					$lastParameter = $parameters[count($parameters) - 1];
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

		foreach ($parametersAcceptor->getParameters() as $param) {
			if (isset($namedArgTypes[$param->getName()])) {
				$argType = $namedArgTypes[$param->getName()];
			} elseif ($param->getDefaultValue() !== null) {
				$argType = $param->getDefaultValue();
			} else {
				continue;
			}

			$paramType = $param->getType();
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
			$passedArgs['$' . $param->getName()] = $argType;
		}

		$returnType = $parametersAcceptor->getReturnType();
		if (
			$returnType instanceof ConditionalTypeForParameter
			&& !$returnType->isNegated()
			&& array_key_exists($returnType->getParameterName(), $passedArgs)
		) {
			$paramType = $returnType->getTarget();
			$argType = $passedArgs[$returnType->getParameterName()];
			$typeMap = $typeMap->union($paramType->inferTemplateTypes($argType));
		}

		$resolvedTemplateTypeMap = new TemplateTypeMap(array_merge(
			$parametersAcceptor->getTemplateTypeMap()->map(static fn (string $name, Type $type): Type => new ErrorType())->getTypes(),
			$typeMap->getTypes(),
		));

		if (!$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs) {
			$parametersAcceptor = new FunctionVariantWithPhpDocs(
				$parametersAcceptor->getTemplateTypeMap(),
				$parametersAcceptor->getResolvedTemplateTypeMap(),
				array_map(static fn (ParameterReflection $parameter): ParameterReflectionWithPhpDocs => new DummyParameterWithPhpDocs(
					$parameter->getName(),
					$parameter->getType(),
					$parameter->isOptional(),
					$parameter->passedByReference(),
					$parameter->isVariadic(),
					$parameter->getDefaultValue(),
					new MixedType(),
					$parameter->getType(),
					null,
				), $parametersAcceptor->getParameters()),
				$parametersAcceptor->isVariadic(),
				$parametersAcceptor->getReturnType(),
				$parametersAcceptor->getReturnType(),
				new MixedType(),
				TemplateTypeVarianceMap::createEmpty(),
			);
		}

		return new ResolvedFunctionVariant(
			$parametersAcceptor,
			$resolvedTemplateTypeMap,
			$parametersAcceptor->getCallSiteVarianceMap(),
			$passedArgs,
		);
	}

}
