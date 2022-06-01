<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\ArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
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

			$passedArgType = $param->isVariadic() ? new ArrayType(new MixedType(), $argType) : $argType;
			$passedArgs['$' . $param->getName()] = $passedArgType;
		}

		$resolvedTemplateTypeMap = new TemplateTypeMap(array_merge(
			$parametersAcceptor->getTemplateTypeMap()->map(static fn (string $name, Type $type): Type => new ErrorType())->getTypes(),
			$typeMap->getTypes(),
		));

		return new ResolvedFunctionVariant(
			$parametersAcceptor,
			$resolvedTemplateTypeMap,
			$passedArgs,
		);
	}

}
