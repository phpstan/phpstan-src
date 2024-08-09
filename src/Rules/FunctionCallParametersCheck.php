<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_fill;
use function array_key_exists;
use function array_pop;
use function count;
use function implode;
use function is_string;
use function max;
use function sprintf;

class FunctionCallParametersCheck
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NullsafeCheck $nullsafeCheck,
		private PhpVersion $phpVersion,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private bool $checkArgumentTypes,
		private bool $checkArgumentsPassedByReference,
		private bool $checkExtraArguments,
		private bool $checkMissingTypehints,
		private bool $checkUnresolvableParameterTypes,
	)
	{
	}

	/**
	 * @param Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $funcCall
	 * @param array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string, 10: string, 11: string, 12: string, 13?: string} $messages
	 * @param 'attribute'|'callable'|'method'|'staticMethod'|'function'|'new' $nodeType
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		ParametersAcceptor $parametersAcceptor,
		Scope $scope,
		bool $isBuiltin,
		$funcCall,
		array $messages,
		string $nodeType = 'function',
	): array
	{
		$functionParametersMinCount = 0;
		$functionParametersMaxCount = 0;
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if (!$parameter->isOptional()) {
				$functionParametersMinCount++;
			}

			$functionParametersMaxCount++;
		}

		if ($parametersAcceptor->isVariadic()) {
			$functionParametersMaxCount = -1;
		}

		/** @var array<int, array{Expr, Type|null, bool, string|null, int}> $arguments */
		$arguments = [];
		/** @var array<int, Node\Arg> $args */
		$args = $funcCall->getArgs();
		$hasNamedArguments = false;
		$hasUnpackedArgument = false;
		$errors = [];
		foreach ($args as $i => $arg) {
			$type = $scope->getType($arg->value);
			if ($hasNamedArguments && $arg->unpack) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by an unpacked (...) argument.')
					->identifier('argument.unpackAfterNamed')
					->line($arg->getStartLine())
					->nonIgnorable()
					->build();
			}
			if ($hasUnpackedArgument && !$arg->unpack) {
				$errors[] = RuleErrorBuilder::message('Unpacked argument (...) cannot be followed by a non-unpacked argument.')
					->identifier('argument.nonUnpackAfterUnpacked')
					->line($arg->getStartLine())
					->nonIgnorable()
					->build();
			}
			if ($arg->unpack) {
				$hasUnpackedArgument = true;
			}
			$argumentName = null;
			if ($arg->name !== null) {
				$hasNamedArguments = true;
				$argumentName = $arg->name->toString();
			}
			if ($arg->unpack) {
				$arrays = $type->getConstantArrays();
				if (count($arrays) > 0) {
					$minKeys = null;
					foreach ($arrays as $array) {
						$countType = $array->getArraySize();
						if ($countType instanceof ConstantIntegerType) {
							$keysCount = $countType->getValue();
						} elseif ($countType instanceof IntegerRangeType) {
							$keysCount = $countType->getMin();
							if ($keysCount === null) {
								throw new ShouldNotHappenException();
							}
						} else {
							throw new ShouldNotHappenException();
						}
						if ($minKeys !== null && $keysCount >= $minKeys) {
							continue;
						}

						$minKeys = $keysCount;
					}

					for ($j = 0; $j < $minKeys; $j++) {
						$types = [];
						$commonKey = null;
						foreach ($arrays as $constantArray) {
							$types[] = $constantArray->getValueTypes()[$j];
							$keyType = $constantArray->getKeyTypes()[$j];
							if ($commonKey === null) {
								$commonKey = $keyType->getValue();
							} elseif ($commonKey !== $keyType->getValue()) {
								$commonKey = false;
							}
						}
						$keyArgumentName = null;
						if (is_string($commonKey)) {
							$keyArgumentName = $commonKey;
							$hasNamedArguments = true;
						}
						$arguments[] = [
							$arg->value,
							TypeCombinator::union(...$types),
							false,
							$keyArgumentName,
							$arg->getStartLine(),
						];
					}
				} else {
					$arguments[] = [
						$arg->value,
						$type->getIterableValueType(),
						true,
						null,
						$arg->getStartLine(),
					];
				}
				continue;
			}

			$arguments[] = [
				$arg->value,
				null,
				false,
				$argumentName,
				$arg->getStartLine(),
			];
		}

		if ($hasNamedArguments && !$this->phpVersion->supportsNamedArguments() && !(bool) $funcCall->getAttribute('isAttribute', false)) {
			$errors[] = RuleErrorBuilder::message('Named arguments are supported only on PHP 8.0 and later.')
				->identifier('argument.namedNotSupported')
				->line($funcCall->getStartLine())
				->nonIgnorable()
				->build();
		}

		if (!$hasNamedArguments) {
			$invokedParametersCount = count($arguments);
			foreach ($arguments as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName]) {
				if ($unpack) {
					$invokedParametersCount = max($functionParametersMinCount, $functionParametersMaxCount);
					break;
				}
			}

			if (
				$invokedParametersCount < $functionParametersMinCount
				|| ($this->checkExtraArguments && $invokedParametersCount > $functionParametersMaxCount)
			) {
				if ($functionParametersMinCount === $functionParametersMaxCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[0] : $messages[1],
						$invokedParametersCount,
						$functionParametersMinCount,
					))
						->identifier('arguments.count')
						->line($funcCall->getStartLine())
						->build();
				} elseif ($functionParametersMaxCount === -1 && $invokedParametersCount < $functionParametersMinCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[2] : $messages[3],
						$invokedParametersCount,
						$functionParametersMinCount,
					))
						->identifier('arguments.count')
						->line($funcCall->getStartLine())
						->build();
				} elseif ($functionParametersMaxCount !== -1) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[4] : $messages[5],
						$invokedParametersCount,
						$functionParametersMinCount,
						$functionParametersMaxCount,
					))
						->identifier('arguments.count')
						->line($funcCall->getStartLine())
						->build();
				}
			}
		}

		if (
			!$funcCall instanceof Node\Expr\New_
			&& !$scope->isInFirstLevelStatement()
			&& $scope->getKeepVoidType($funcCall)->isVoid()->yes()
		) {
			$errors[] = RuleErrorBuilder::message($messages[7])
				->identifier(sprintf('%s.void', $nodeType))
				->line($funcCall->getStartLine())
				->build();
		}

		[$addedErrors, $argumentsWithParameters] = $this->processArguments($parametersAcceptor, $funcCall->getStartLine(), $isBuiltin, $arguments, $hasNamedArguments, $messages[10], $messages[11]);
		foreach ($addedErrors as $error) {
			$errors[] = $error;
		}

		if (!$this->checkArgumentTypes && !$this->checkArgumentsPassedByReference) {
			return $errors;
		}

		foreach ($argumentsWithParameters as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter, $originalParameter]) {
			if ($this->checkArgumentTypes && $unpack) {
				$iterableTypeResult = $this->ruleLevelHelper->findTypeToCheck(
					$scope,
					$argumentValue,
					'',
					static fn (Type $type): bool => $type->isIterable()->yes(),
				);
				$iterableTypeResultType = $iterableTypeResult->getType();
				if (
					!$iterableTypeResultType instanceof ErrorType
					&& !$iterableTypeResultType->isIterable()->yes()
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Only iterables can be unpacked, %s given in argument #%d.',
						$iterableTypeResultType->describe(VerbosityLevel::typeOnly()),
						$i + 1,
					))->identifier('argument.unpackNonIterable')->line($argumentLine)->build();
				}
			}

			if ($parameter === null) {
				continue;
			}

			if ($argumentValueType === null) {
				if ($scope instanceof MutatingScope) {
					$scope = $scope->pushInFunctionCall(null, $parameter);
				}
				$argumentValueType = $scope->getType($argumentValue);

				if ($scope instanceof MutatingScope) {
					$scope = $scope->popInFunctionCall();
				}
			}

			if ($this->checkArgumentTypes) {
				$parameterType = TypeUtils::resolveLateResolvableTypes($parameter->getType());

				if (
					!$parameter->passedByReference()->createsNewVariable()
					|| (!$isBuiltin && $this->checkUnresolvableParameterTypes) // bleeding edge only
				) {
					$accepts = $this->ruleLevelHelper->acceptsWithReason($parameterType, $argumentValueType, $scope->isDeclareStrictTypes());

					if (!$accepts->result) {
						$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $argumentValueType);
						$errors[] = RuleErrorBuilder::message(sprintf(
							$messages[6],
							$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
							$parameterType->describe($verbosityLevel),
							$argumentValueType->describe($verbosityLevel),
						))
							->identifier('argument.type')
							->line($argumentLine)
							->acceptsReasonsTip($accepts->reasons)
							->build();
					}
				}

				if ($this->checkUnresolvableParameterTypes
					&& $originalParameter !== null
					&& isset($messages[13])
					&& !$this->unresolvableTypeHelper->containsUnresolvableType($originalParameter->getType())
					&& $this->unresolvableTypeHelper->containsUnresolvableType($parameterType)
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$messages[13],
						$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
					))->identifier('argument.unresolvableType')->line($argumentLine)->build();
				}

				if (
					$parameter instanceof ParameterReflectionWithPhpDocs
					&& $parameter->getClosureThisType() !== null
					&& ($argumentValue instanceof Expr\Closure || $argumentValue instanceof Expr\ArrowFunction)
					&& $argumentValue->static
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$messages[6],
						$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
						'bindable closure',
						'static closure',
					))
						->identifier('argument.staticClosure')
						->line($argumentLine)
						->build();
				}
			}

			if (
				!$this->checkArgumentsPassedByReference
				|| !$parameter->passedByReference()->yes()
			) {
				continue;
			}

			if ($this->nullsafeCheck->containsNullSafe($argumentValue)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					$messages[8],
					$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
				))
					->identifier('argument.byRef')
					->line($argumentLine)
					->build();
				continue;
			}

			if (
				$argumentValue instanceof Node\Expr\PropertyFetch
				|| $argumentValue instanceof Node\Expr\StaticPropertyFetch) {
				$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($argumentValue, $scope);
				foreach ($propertyReflections as $propertyReflection) {
					$nativePropertyReflection = $propertyReflection->getNativeReflection();
					if ($nativePropertyReflection === null) {
						continue;
					}
					if (!$nativePropertyReflection->isReadOnly()) {
						continue;
					}

					if ($nativePropertyReflection->isStatic()) {
						$propertyDescription = sprintf('static readonly property %s::$%s', $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyReflection->getName());
					} else {
						$propertyDescription = sprintf('readonly property %s::$%s', $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyReflection->getName());
					}

					$errors[] = RuleErrorBuilder::message(sprintf(
						'Parameter %s is passed by reference so it does not accept %s.',
						$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
						$propertyDescription,
					))->identifier('argument.byRef')->line($argumentLine)->build();
				}
			}

			if ($argumentValue instanceof Node\Expr\Variable
				|| $argumentValue instanceof Node\Expr\ArrayDimFetch
				|| $argumentValue instanceof Node\Expr\PropertyFetch
				|| $argumentValue instanceof Node\Expr\StaticPropertyFetch) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				$messages[8],
				$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
			))->identifier('argument.byRef')->line($argumentLine)->build();
		}

		if ($this->checkMissingTypehints && $parametersAcceptor instanceof ResolvedFunctionVariant) {
			$originalParametersAcceptor = $parametersAcceptor->getOriginalParametersAcceptor();
			$resolvedTypes = $parametersAcceptor->getResolvedTemplateTypeMap()->getTypes();
			if (count($resolvedTypes) > 0) {
				$returnTemplateTypes = [];
				TypeTraverser::map(
					$parametersAcceptor->getReturnTypeWithUnresolvableTemplateTypes(),
					static function (Type $type, callable $traverse) use (&$returnTemplateTypes): Type {
						while ($type instanceof ConditionalType && $type->isResolvable()) {
							$type = $type->resolve();
						}

						if ($type instanceof TemplateType) {
							$returnTemplateTypes[$type->getName()] = true;
							return $type;
						}

						return $traverse($type);
					},
				);

				$parameterTemplateTypes = [];
				foreach ($originalParametersAcceptor->getParameters() as $parameter) {
					TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$parameterTemplateTypes): Type {
						if ($type instanceof TemplateType) {
							$parameterTemplateTypes[$type->getName()] = true;
							return $type;
						}

						return $traverse($type);
					});
				}

				foreach ($resolvedTypes as $name => $type) {
					if (
						!($type instanceof ErrorType)
						&& (
							!$type instanceof NeverType
							|| $type->isExplicit()
						)
					) {
						continue;
					}

					if (!array_key_exists($name, $returnTemplateTypes)) {
						continue;
					}

					if (!array_key_exists($name, $parameterTemplateTypes)) {
						continue;
					}

					$errors[] = RuleErrorBuilder::message(sprintf($messages[9], $name))
						->identifier('argument.templateType')
						->line($funcCall->getStartLine())
						->tip('See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type')
						->build();
				}
			}

			if (
				!$this->unresolvableTypeHelper->containsUnresolvableType($originalParametersAcceptor->getReturnType())
				&& $this->unresolvableTypeHelper->containsUnresolvableType($parametersAcceptor->getReturnType())
			) {
				$errors[] = RuleErrorBuilder::message($messages[12])
					->identifier(sprintf('%s.unresolvableReturnType', $nodeType))
					->line($funcCall->getStartLine())
					->build();
			}
		}

		return $errors;
	}

	/**
	 * @param array<int, array{Expr, Type|null, bool, string|null, int}> $arguments
	 * @return array{list<IdentifierRuleError>, array<int, array{Expr, Type|null, bool, (string|null), int, (ParameterReflection|null), (ParameterReflection|null)}>}
	 */
	private function processArguments(
		ParametersAcceptor $parametersAcceptor,
		int $line,
		bool $isBuiltin,
		array $arguments,
		bool $hasNamedArguments,
		string $missingParameterMessage,
		string $unknownParameterMessage,
	): array
	{
		$parameters = $parametersAcceptor->getParameters();
		$originalParameters = $parametersAcceptor instanceof ResolvedFunctionVariant
			? $parametersAcceptor->getOriginalParametersAcceptor()->getParameters()
			: array_fill(0, count($parameters), null);
		$expandedParameters = [];
		$expandedOriginalParameters = [];
		$parametersByName = [];
		$originalParametersByName = [];
		$unusedParametersByName = [];
		$errors = [];
		$expandVariadicParam = count($arguments) - count($parameters);
		foreach ($parameters as $i => $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
			$originalParameter = $originalParameters[$i];
			$originalParametersByName[$parameter->getName()] = $originalParameter;
			$expandedParameters[] = $parameter;
			$expandedOriginalParameters[] = $originalParameter;

			if ($parameter->isVariadic()) {
				// This handles variadic parameter followed by a another parameter. This cannot happen in user-defined
				// code, but it does happen in built-in functions - e.g. array_intersect_uassoc. Currently, this condition
				// doesn't handle the case where the following parameter is optional (it may not be needed).
				if ($expandVariadicParam < 0 && array_key_exists($i + 1, $parameters)) {
					$expandVariadicParam++;
					array_pop($expandedParameters);
					array_pop($expandedOriginalParameters);
				} else {
					for (; $expandVariadicParam > 0; --$expandVariadicParam) {
						$expandedParameters[] = $parameter;
						$expandedOriginalParameters[] = $originalParameter;
					}
				}

				continue;
			}

			$unusedParametersByName[$parameter->getName()] = $parameter;
		}

		$parameters = $expandedParameters;
		$originalParameters = $expandedOriginalParameters;
		$newArguments = [];

		// TODO: check isVariadic usages in below code - they may not be necessary anymore.
		$namedArgumentAlreadyOccurred = false;
		foreach ($arguments as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine]) {
			if ($argumentName === null) {
				if (!isset($parameters[$i])) {
					if (!$parametersAcceptor->isVariadic() || count($parameters) === 0) {
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
						break;
					}

					$parameter = $parameters[count($parameters) - 1];
					$originalParameter = $originalParameters[count($originalParameters) - 1];
					if (!$parameter->isVariadic()) {
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
						break; // func_get_args
					}
				} else {
					$parameter = $parameters[$i];
					$originalParameter = $originalParameters[$i];
				}
			} elseif (array_key_exists($argumentName, $parametersByName)) {
				$namedArgumentAlreadyOccurred = true;
				$parameter = $parametersByName[$argumentName];
				$originalParameter = $originalParametersByName[$argumentName];
			} else {
				$namedArgumentAlreadyOccurred = true;

				$parametersCount = count($parameters);
				if (
					!$parametersAcceptor->isVariadic()
					|| $parametersCount <= 0
					|| $isBuiltin
				) {
					$errors[] = RuleErrorBuilder::message(sprintf($unknownParameterMessage, $argumentName))
						->identifier('argument.unknown')
						->line($argumentLine)
						->build();
					$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
					continue;
				}

				$parameter = $parameters[$parametersCount - 1];
				$originalParameter = $originalParameters[$parametersCount - 1];
			}

			if ($namedArgumentAlreadyOccurred && $argumentName === null && !$unpack) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by a positional argument.')
					->identifier('argument.positionalAfterNamed')
					->line($argumentLine)
					->nonIgnorable()
					->build();
				$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
				continue;
			}

			$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter, $originalParameter];

			if (
				$hasNamedArguments
				&& !$parameter->isVariadic()
				&& !array_key_exists($parameter->getName(), $unusedParametersByName)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf('Argument for parameter $%s has already been passed.', $parameter->getName()))
					->identifier('argument.duplicate')
					->line($argumentLine)
					->build();
				continue;
			}

			unset($unusedParametersByName[$parameter->getName()]);
		}

		if ($hasNamedArguments) {
			foreach ($unusedParametersByName as $parameter) {
				if ($parameter->isOptional()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf($missingParameterMessage, sprintf('%s (%s)', $parameter->getName(), $parameter->getType()->describe(VerbosityLevel::typeOnly()))))
					->identifier('argument.missing')
					->line($line)
					->build();
			}
		}

		return [$errors, $newArguments];
	}

	private function describeParameter(ParameterReflection $parameter, ?int $position): string
	{
		$parts = [];
		if ($position !== null) {
			$parts[] = '#' . $position;
		}

		$name = $parameter->getName();
		if ($name !== '') {
			$parts[] = ($parameter->isVariadic() ? '...$' : '$') . $name;
		}

		return implode(' ', $parts);
	}

}
