<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\ConditionalType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function array_merge;
use function count;
use function is_int;

class GenericParametersAcceptorResolver
{

	/**
	 * @api
	 * @param array<int|string, Type> $argTypes
	 */
	public static function resolve(array $argTypes, ParametersAcceptor $parametersAcceptor): ParametersAcceptor
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

		$resolvedTemplateTypeMap = new TemplateTypeMap(array_merge(
			$parametersAcceptor->getTemplateTypeMap()->map(static fn (string $name, Type $type): Type => new ErrorType())->getTypes(),
			$typeMap->getTypes(),
		));

		return new ResolvedFunctionVariant(
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				$parametersAcceptor->getParameters(),
				$parametersAcceptor->isVariadic(),
				TypeTraverser::map($parametersAcceptor->getReturnType(), static function (Type $type, callable $traverse) use ($passedArgs): Type {
					if ($type instanceof ConditionalTypeForParameter || $type instanceof ConditionalType) {
						$type = $traverse($type);

						if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $passedArgs)) {
							$type = $type->toConditional($passedArgs[$type->getParameterName()]);
						}

						if ($type instanceof ConditionalType) {
							return $type->resolve();
						}

						return $type;
					}

					return $traverse($type);
				}),
			),
			$resolvedTemplateTypeMap,
			$passedArgs,
		);
	}

}
