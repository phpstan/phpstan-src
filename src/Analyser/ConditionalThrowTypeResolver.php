<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function array_last;
use function count;
use function substr;

/**
 * Resolves conditional `@throws` types like `($x is 0 ? Exception : void)`.
 *
 * The same `ConditionalTypeForParameter` representation used for conditional
 * return types is resolved here against either the arguments passed at a call
 * site (so callers see whether the call throws) or against the parameter
 * variables inside the function body (so the body's throw points are matched
 * against the declared `@throws` type).
 */
final class ConditionalThrowTypeResolver
{

	/**
	 * @param Arg[] $args
	 */
	public static function resolveForCall(
		Type $throwType,
		ParametersAcceptor $parametersAcceptor,
		array $args,
		Scope $scope,
	): Type
	{
		if (!$throwType->hasTemplateOrLateResolvableType()) {
			return $throwType;
		}

		return self::resolve($throwType, self::getPassedArgs($parametersAcceptor, $args, $scope));
	}

	public static function resolveForScope(Type $throwType, Scope $scope): Type
	{
		if (!$throwType->hasTemplateOrLateResolvableType()) {
			return $throwType;
		}

		$passedArgs = [];
		foreach (self::collectParameterNames($throwType) as $parameterName) {
			$variableName = substr($parameterName, 1);
			if (!$scope->hasVariableType($variableName)->yes()) {
				continue;
			}

			$passedArgs[$parameterName] = $scope->getType(new Variable($variableName));
		}

		return self::resolve($throwType, $passedArgs);
	}

	/**
	 * @param array<string, Type> $passedArgs
	 */
	private static function resolve(Type $throwType, array $passedArgs): Type
	{
		if ($passedArgs === []) {
			return $throwType;
		}

		$resolved = TypeTraverser::map($throwType, static function (Type $type, callable $traverse) use ($passedArgs): Type {
			if ($type instanceof ConditionalTypeForParameter && array_key_exists($type->getParameterName(), $passedArgs)) {
				$type = $traverse($type);
				if ($type instanceof ConditionalTypeForParameter) {
					return $type->toConditional($passedArgs[$type->getParameterName()]);
				}

				return $type;
			}

			return $traverse($type);
		});

		return TypeUtils::resolveLateResolvableTypes($resolved, false);
	}

	/**
	 * @return list<string>
	 */
	private static function collectParameterNames(Type $throwType): array
	{
		$names = [];
		TypeTraverser::map($throwType, static function (Type $type, callable $traverse) use (&$names): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$names[] = $type->getParameterName();
			}

			return $traverse($type);
		});

		return $names;
	}

	/**
	 * @param Arg[] $args
	 * @return array<string, Type>
	 */
	private static function getPassedArgs(ParametersAcceptor $parametersAcceptor, array $args, Scope $scope): array
	{
		$parameters = $parametersAcceptor->getParameters();

		$namedArgTypes = [];
		$i = 0;
		foreach ($args as $arg) {
			if ($arg->unpack) {
				// unpacked arguments cannot be reliably mapped to a single parameter
				$i++;
				continue;
			}

			if ($arg->name !== null) {
				$namedArgTypes[$arg->name->toString()] = $scope->getType($arg->value);
				continue;
			}

			if (isset($parameters[$i])) {
				$namedArgTypes[$parameters[$i]->getName()] = $scope->getType($arg->value);
			} elseif (count($parameters) > 0) {
				$lastParameter = array_last($parameters);
				if ($lastParameter->isVariadic()) {
					$namedArgTypes[$lastParameter->getName()] = $scope->getType($arg->value);
				}
			}

			$i++;
		}

		$passedArgs = [];
		foreach ($parameters as $parameter) {
			if (array_key_exists($parameter->getName(), $namedArgTypes)) {
				$passedArgs['$' . $parameter->getName()] = $namedArgTypes[$parameter->getName()];
			} elseif ($parameter->getDefaultValue() !== null) {
				$passedArgs['$' . $parameter->getName()] = $parameter->getDefaultValue();
			}
		}

		return $passedArgs;
	}

}
