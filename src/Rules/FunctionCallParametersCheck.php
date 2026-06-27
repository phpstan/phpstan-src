<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Rules\Methods\NamedArgumentParameterMethodCallsCollector;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_fill;
use function array_key_exists;
use function array_last;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function lcfirst;
use function max;
use function sprintf;

#[AutowiredService]
final class FunctionCallParametersCheck
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NullsafeCheck $nullsafeCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter(ref: '%checkFunctionArgumentTypes%')]
		private bool $checkArgumentTypes,
		#[AutowiredParameter]
		private bool $checkArgumentsPassedByReference,
		#[AutowiredParameter]
		private bool $checkExtraArguments,
		#[AutowiredParameter]
		private bool $checkMissingTypehints,
	)
	{
	}

	/**
	 * @param 'attribute'|'callable'|'method'|'staticMethod'|'function'|'new' $nodeType
	 * @param array{class-string, string}|null $renamedNamedArgumentParameterData
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		ParametersAcceptor $parametersAcceptor,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		bool $isBuiltin,
		Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $funcCall,
		string $nodeType,
		TrinaryLogic $acceptsNamedArguments,
		string $singleInsufficientParameterMessage,
		string $pluralInsufficientParametersMessage,
		string $singleInsufficientParameterInVariadicFunctionMessage,
		string $pluralInsufficientParametersInVariadicFunctionMessage,
		string $singleInsufficientParameterWithOptionalParametersMessage,
		string $pluralInsufficientParametersWithOptionalParametersMessage,
		string $wrongArgumentTypeMessage,
		string $voidReturnTypeUsed,
		string $parameterPassedByReferenceMessage,
		string $unresolvableTemplateTypeMessage,
		string $missingParameterMessage,
		string $unknownParameterMessage,
		string $unresolvableReturnTypeMessage,
		string $unresolvableParameterTypeMessage,
		string $namedArgumentMessage,
		string $invalidConstantMessage,
		string $exclusiveConstantsMessage,
		string $bitmaskNotAllowedMessage,
		?array $renamedNamedArgumentParameterData,
	): array
	{
		if ($funcCall instanceof Node\Expr\MethodCall || $funcCall instanceof Node\Expr\StaticCall || $funcCall instanceof Node\Expr\FuncCall) {
			$funcCallLine = $funcCall->name->getStartLine();
		} else {
			$funcCallLine = $funcCall->getStartLine();
		}

		$functionParametersMinCount = 0;
		$functionParametersMaxCount = 0;
		$allowedConstantsTypes = [];
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if (
				$parameter instanceof ExtendedParameterReflection
				&& $parameter->getAllowedConstants() !== null
			) {
				$allowedConstantsTypes[] = $parameter->getType();
			}
			if (!$parameter->isOptional()) {
				$functionParametersMinCount++;
			}

			$functionParametersMaxCount++;
		}

		$allowedConstantsType = null;
		if (count($allowedConstantsTypes) > 0) {
			$allowedConstantsType = TypeCombinator::union(...$allowedConstantsTypes);
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
		foreach ($args as $arg) {
			$argumentName = null;
			if ($arg->name !== null) {
				$hasNamedArguments = true;
				$argumentName = $arg->name->toString();
			}

			if ($hasNamedArguments && $arg->unpack) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by an unpacked (...) argument.')
					->identifier('argument.unpackAfterNamed')
					->line($arg->getStartLine())
					->nonIgnorable()
					->build();
			}
			if ($hasUnpackedArgument && !$arg->unpack) {
				if ($argumentName === null || !$scope->getPhpVersion()->supportsNamedArgumentAfterUnpackedArgument()->yes()) {
					$errors[] = RuleErrorBuilder::message('Unpacked argument (...) cannot be followed by a non-unpacked argument.')
						->identifier('argument.nonUnpackAfterUnpacked')
						->line($arg->getStartLine())
						->nonIgnorable()
						->build();
				}
			}
			if ($arg->unpack) {
				$hasUnpackedArgument = true;
			}
			if ($arg->unpack) {
				$type = $scope->getType($arg->value);
				$arrays = $type->getConstantArrays();
				if (count($arrays) > 0) {
					$maxKeys = null;
					foreach ($arrays as $array) {
						if ($array->isUnsealed()->yes()) {
							$maxKeys = 0;
							break;
						}
						$countType = $array->getArraySize();
						if ($countType instanceof ConstantIntegerType) {
							$keysCount = $countType->getValue();
						} elseif ($countType instanceof IntegerRangeType) {
							$keysCount = $countType->getMax();
							if ($keysCount === null) {
								throw new ShouldNotHappenException();
							}
						} else {
							throw new ShouldNotHappenException();
						}
						if ($maxKeys !== null && $keysCount >= $maxKeys) {
							continue;
						}

						$maxKeys = $keysCount;
					}

					for ($j = 0; $j < $maxKeys; $j++) {
						$types = [];
						$commonKey = null;
						$isOptionalKey = false;
						foreach ($arrays as $constantArray) {
							$isOptionalKey = in_array($j, $constantArray->getOptionalKeys(), true);
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
						if ($isOptionalKey && $keyArgumentName === null) {
							continue;
						}

						$arguments[] = [
							$arg->value,
							TypeCombinator::union(...$types),
							false,
							$keyArgumentName,
							$arg->getStartLine(),
						];
					}

					if (count($arguments) === 0 && $type->isIterableAtLeastOnce()->yes()) {
						$arguments[] = [
							$arg->value,
							$type->getIterableValueType(),
							true,
							null,
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

		if ($hasNamedArguments && !$scope->getPhpVersion()->supportsNamedArguments()->yes() && !(bool) $funcCall->getAttribute('isAttribute', false)) {
			$errors[] = RuleErrorBuilder::message('Named arguments are supported only on PHP 8.0 and later.')
				->identifier('argument.namedNotSupported')
				->line($funcCallLine)
				->nonIgnorable()
				->build();
		}

		if (!$hasNamedArguments) {
			$invokedParametersCount = count($arguments);
			foreach ($arguments as [$argumentValue, $argumentValueType, $unpack, $argumentName]) {
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
						$invokedParametersCount === 1 ? $singleInsufficientParameterMessage : $pluralInsufficientParametersMessage,
						$invokedParametersCount,
						$functionParametersMinCount,
					))
						->identifier('arguments.count')
						->line($funcCallLine)
						->build();
				} elseif ($functionParametersMaxCount === -1 && $invokedParametersCount < $functionParametersMinCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $singleInsufficientParameterInVariadicFunctionMessage : $pluralInsufficientParametersInVariadicFunctionMessage,
						$invokedParametersCount,
						$functionParametersMinCount,
					))
						->identifier('arguments.count')
						->line($funcCallLine)
						->build();
				} elseif ($functionParametersMaxCount !== -1) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $singleInsufficientParameterWithOptionalParametersMessage : $pluralInsufficientParametersWithOptionalParametersMessage,
						$invokedParametersCount,
						$functionParametersMinCount,
						$functionParametersMaxCount,
					))
						->identifier('arguments.count')
						->line($funcCallLine)
						->build();
				}
			}
		}

		if (
			!$funcCall instanceof Node\Expr\New_
			&& !$scope->isInFirstLevelStatement()
			&& $scope->getKeepVoidType($funcCall)->isVoid()->yes()
		) {
			$errors[] = RuleErrorBuilder::message($voidReturnTypeUsed)
				->identifier(sprintf('%s.void', $nodeType))
				->line($funcCallLine)
				->build();
		}

		[$addedErrors, $argumentsWithParameters] = $this->processArguments($parametersAcceptor, $funcCallLine, $isBuiltin, $arguments, $hasNamedArguments, $missingParameterMessage, $unknownParameterMessage);
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
					$rememberTypes = !$argumentValue instanceof Expr\Closure && !$argumentValue instanceof Expr\ArrowFunction;
					$scope = $scope->pushInFunctionCall(null, $parameter, $rememberTypes);
				}
				$argumentValueType = $scope->getType($argumentValue);

				if ($scope instanceof MutatingScope) {
					$scope = $scope->popInFunctionCall();
				}
			}

			if (!$acceptsNamedArguments->yes()) {
				if ($argumentName !== null) {
					$errors[] = RuleErrorBuilder::message(sprintf($namedArgumentMessage, sprintf('named argument $%s', $argumentName)))
						->identifier('argument.named')
						->line($argumentLine)
						->build();
				} elseif ($unpack) {
					$unpackedArrayType = $scope->getType($argumentValue);
					$hasStringKey = $unpackedArrayType->getIterableKeyType()->isString();
					if (!$hasStringKey->no()) {
						$errors[] = RuleErrorBuilder::message(sprintf($namedArgumentMessage, sprintf('unpacked array with %s', $hasStringKey->yes() ? 'string key' : 'possibly string key')))
							->identifier('argument.named')
							->line($argumentLine)
							->build();
					}
				}
			} elseif ($argumentName !== null && $renamedNamedArgumentParameterData !== null) {
				$scope->emitCollectedData(NamedArgumentParameterMethodCallsCollector::class, array_merge(
					$renamedNamedArgumentParameterData,
					[$parameter->getName(), $argumentLine],
				));
			}

			if ($this->checkArgumentTypes) {
				$parameterType = TypeUtils::resolveLateResolvableTypes($parameter->getType());

				if (
					!$parameter->passedByReference()->createsNewVariable()
					|| (!$isBuiltin && !$argumentValueType instanceof ErrorType)
				) {
					// @see https://github.com/php/php-src/issues/21568#issuecomment-4148832540
					$isStrictTypes = $scope->isDeclareStrictTypes()
						&& (!$isBuiltin || !$parameterType->isCallable()->yes());
					$accepts = $this->ruleLevelHelper->accepts($parameterType, $argumentValueType, $isStrictTypes);

					if (!$accepts->result) {
						$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $argumentValueType);
						$errors[] = RuleErrorBuilder::message(sprintf(
							$wrongArgumentTypeMessage,
							$this->describeParameter($parameter, $argumentName ?? $i + 1),
							$parameterType->describe($verbosityLevel),
							$argumentValueType->describe($verbosityLevel),
						))
							->identifier('argument.type')
							->line($argumentLine)
							->acceptsReasonsTip($accepts->reasons)
							->build();
					} elseif ($argumentValue instanceof Expr\Ternary && $argumentValueType instanceof MixedType) {
						foreach ($this->getTernaryBranchTypes($argumentValue, $scope) as $branchType) {
							$branchAccepts = $this->ruleLevelHelper->accepts($parameterType, $branchType, $isStrictTypes);
							if ($branchAccepts->result) {
								continue;
							}

							$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $branchType);
							$errors[] = RuleErrorBuilder::message(sprintf(
								$wrongArgumentTypeMessage,
								$this->describeParameter($parameter, $argumentName ?? $i + 1),
								$parameterType->describe($verbosityLevel),
								$branchType->describe($verbosityLevel),
							))
								->identifier('argument.type')
								->line($argumentLine)
								->acceptsReasonsTip($branchAccepts->reasons)
								->build();
						}
					}
				}

				$unresolvableParameterType = $this->unresolvableTypeHelper->getUnresolvableType($parameterType);
				if (
					$originalParameter !== null
					&& $this->unresolvableTypeHelper->getUnresolvableType($originalParameter->getType()) === null
					&& $unresolvableParameterType !== null
				) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						$unresolvableParameterTypeMessage,
						$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
					))->identifier('argument.unresolvableType')->line($argumentLine);
					foreach ($unresolvableParameterType->reasons as $reason) {
						$errorBuilder->addTip($reason);
					}
					$errors[] = $errorBuilder->build();
				}

				if (
					$parameter instanceof ExtendedParameterReflection
					&& $parameter->getClosureThisType() !== null
					&& ($argumentValue instanceof Expr\Closure || $argumentValue instanceof Expr\ArrowFunction)
					&& $argumentValue->static
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$wrongArgumentTypeMessage,
						$this->describeParameter($parameter, $argumentName === null ? $i + 1 : null),
						'bindable closure',
						'static closure',
					))
						->identifier('argument.staticClosure')
						->line($argumentLine)
						->build();
				}

				if (
					$parameter instanceof ExtendedParameterReflection
					&& $scope->getPhpVersion()->supportsNamedArguments()->yes()
				) {
					$constantReflections = $this->resolveConstantReflections($argumentValue, $scope);
					if ($constantReflections !== null) {
						if ($parameter->getAllowedConstants() !== null) {
							$result = $parameter->checkAllowedConstants($constantReflections);
							foreach ($result->getDisallowedConstants() as $disallowedConstant) {
								$errors[] = RuleErrorBuilder::message(sprintf(
									$invalidConstantMessage,
									$disallowedConstant->describe(),
									lcfirst($this->describeParameter($parameter, $argumentName ?? $i + 1)),
								))
									->identifier('argument.invalidConstant')
									->line($argumentLine)
									->build();
							}
							foreach ($result->getViolatedExclusiveGroups() as $group) {
								$errors[] = RuleErrorBuilder::message(sprintf(
									$exclusiveConstantsMessage,
									implode(', ', $group),
									lcfirst($this->describeParameter($parameter, $argumentName ?? $i + 1)),
								))
									->identifier('argument.exclusiveConstants')
									->line($argumentLine)
									->build();
							}
							if ($result->isBitmaskNotAllowed()) {
								$errors[] = RuleErrorBuilder::message(sprintf(
									$bitmaskNotAllowedMessage,
									lcfirst($this->describeParameter($parameter, $argumentName ?? $i + 1)),
								))
									->identifier('argument.bitmaskNotAllowed')
									->line($argumentLine)
									->build();
							}
						} elseif ($isBuiltin && $allowedConstantsType !== null && $allowedConstantsType->isSuperTypeOf($parameterType)->yes()) {
							foreach ($constantReflections as $constantReflection) {
								if ($constantReflection->isBuiltin()->no()) {
									continue;
								}
								$errors[] = RuleErrorBuilder::message(sprintf(
									$invalidConstantMessage,
									$constantReflection->describe(),
									lcfirst($this->describeParameter($parameter, $argumentName ?? $i + 1)),
								))
									->identifier('argument.invalidConstant')
									->line($argumentLine)
									->build();
							}
						}
					}
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
					$parameterPassedByReferenceMessage,
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

					if ($nativePropertyReflection->isReadOnly()) {
						if ($nativePropertyReflection->isStatic()) {
							$errorFormat = 'static readonly property %s::$%s';
						} else {
							$errorFormat = 'readonly property %s::$%s';
						}
					} elseif ($nativePropertyReflection->isReadOnlyByPhpDoc()) {
						if ($nativePropertyReflection->isStatic()) {
							$errorFormat = 'static @readonly property %s::$%s';
						} else {
							$errorFormat = '@readonly property %s::$%s';
						}
					} else {
						continue;
					}

					$propertyDescription = sprintf($errorFormat, $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyReflection->getName());

					$errors[] = RuleErrorBuilder::message(sprintf(
						'%s is passed by reference so it does not accept %s.',
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

			if ($this->callReturnsByReference($argumentValue, $scope)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				$parameterPassedByReferenceMessage,
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

						if ($type instanceof TemplateType && $type->getDefault() === null) {
							$returnTemplateTypes[$type->getName()] = true;
							return $type;
						}

						return $traverse($type);
					},
				);

				$parameterTemplateTypes = [];
				foreach ($originalParametersAcceptor->getParameters() as $parameter) {
					TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$parameterTemplateTypes): Type {
						if ($type instanceof TemplateType && $type->getDefault() === null) {
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

					$errors[] = RuleErrorBuilder::message(sprintf($unresolvableTemplateTypeMessage, $name))
						->identifier('argument.templateType')
						->line($funcCallLine)
						->tip('See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type')
						->build();
				}
			}

			$unresolvableReturnType = $this->unresolvableTypeHelper->getUnresolvableType($parametersAcceptor->getReturnType());
			if (
				$this->unresolvableTypeHelper->getUnresolvableType($originalParametersAcceptor->getReturnType()) === null
				&& $unresolvableReturnType !== null
			) {
				$errorBuilder = RuleErrorBuilder::message($unresolvableReturnTypeMessage)
					->identifier(sprintf('%s.unresolvableReturnType', $nodeType))
					->line($funcCallLine);
				foreach ($unresolvableReturnType->reasons as $reason) {
					$errorBuilder->addTip($reason);
				}
				$errors[] = $errorBuilder->build();
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
		$parametersByName = [];
		$originalParametersByName = [];
		$unusedParametersByName = [];
		$errors = [];
		$isNativelyVariadic = false;
		foreach ($parameters as $i => $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
			$originalParametersByName[$parameter->getName()] = $originalParameters[$i];

			if ($parameter->isVariadic()) {
				$isNativelyVariadic = true;
				continue;
			}

			$unusedParametersByName[$parameter->getName()] = $parameter;
		}

		$newArguments = [];

		$namedArgumentAlreadyOccurred = false;
		$namedArgumentsForVariadicParameter = [];
		foreach ($arguments as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine]) {
			if ($argumentName === null) {
				if (!isset($parameters[$i])) {
					if (!$parametersAcceptor->isVariadic() || count($parameters) === 0) {
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
						break;
					}

					$parameter = array_last($parameters);
					$originalParameter = array_last($originalParameters);
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

				if (!$isNativelyVariadic || $isBuiltin) {
					$errors[] = RuleErrorBuilder::message(sprintf($unknownParameterMessage, $argumentName))
						->identifier('argument.unknown')
						->line($argumentLine)
						->build();
					$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
					continue;
				}

				$parametersCount = count($parameters);
				$parameter = $parameters[$parametersCount - 1];
				$originalParameter = $originalParameters[$parametersCount - 1];

				if (isset($namedArgumentsForVariadicParameter[$argumentName])) {
					$errors[] = RuleErrorBuilder::message(sprintf('Named parameter $%s overwrites previous argument.', $argumentName))
						->identifier('argument.duplicate')
						->line($argumentLine)
						->build();
				}
				$namedArgumentsForVariadicParameter[$argumentName] = true;
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

	private function describeParameter(ParameterReflection $parameter, int|string|null $positionOrNamed): string
	{
		$parts = [];
		if (is_int($positionOrNamed)) {
			$parts[] = 'Parameter #' . $positionOrNamed;
		} elseif ($parameter->isVariadic() && is_string($positionOrNamed)) {
			$parts[] = 'Named argument ' . $positionOrNamed . ' for variadic parameter';
		} else {
			$parts[] = 'Parameter';
		}

		$name = $parameter->getName();
		if ($name !== '') {
			$parts[] = ($parameter->isVariadic() ? '...$' : '$') . $name;
		}

		return implode(' ', $parts);
	}

	/**
	 * Collects the leaf types of a ternary's branches, each resolved in the scope
	 * narrowed by the controlling condition. Nested ternaries are flattened so every
	 * value the expression can produce is represented by its own (un-normalized) type.
	 *
	 * The else branch is narrowed by the negated condition (`filterByTruthyValue` of
	 * `!cond`) rather than `filterByFalseyValue($cond)`, mirroring how
	 * TernaryHandler::specifyTypes models the else branch. Some conditions (e.g.
	 * `is_resource()`, whose stub only declares `@phpstan-assert-if-true`) narrow
	 * asymmetrically, so the falsey scope would otherwise diverge from the type the
	 * ternary actually produces and report spurious branch types.
	 *
	 * @return list<Type>
	 */
	private function getTernaryBranchTypes(Expr\Ternary $ternary, Scope $scope): array
	{
		$truthyScope = $scope->filterByTruthyValue($ternary->cond);
		$falseyScope = $scope->filterByTruthyValue(new Expr\BooleanNot($ternary->cond));

		if ($ternary->if === null) {
			$ifTypes = [TypeCombinator::removeFalsey($truthyScope->getType($ternary->cond))];
		} elseif ($ternary->if instanceof Expr\Ternary) {
			$ifTypes = $this->getTernaryBranchTypes($ternary->if, $truthyScope);
		} else {
			$ifTypes = [$truthyScope->getType($ternary->if)];
		}

		if ($ternary->else instanceof Expr\Ternary) {
			$elseTypes = $this->getTernaryBranchTypes($ternary->else, $falseyScope);
		} else {
			$elseTypes = [$falseyScope->getType($ternary->else)];
		}

		return array_merge($ifTypes, $elseTypes);
	}

	/**
	 * @return list<ConstantReflection>|null Null when the expression is not a constant or bitmask of constants
	 */
	private function resolveConstantReflections(Expr $expr, Scope $scope): ?array
	{
		if ($expr instanceof Expr\ConstFetch) {
			$lowerName = $expr->name->toLowerString();
			if (in_array($lowerName, ['null', 'true', 'false'], true)) {
				return null;
			}

			if (!$this->reflectionProvider->hasConstant($expr->name, $scope)) {
				return null;
			}

			return [$this->reflectionProvider->getConstant($expr->name, $scope)];
		}

		if ($expr instanceof Expr\ClassConstFetch) {
			if (!$expr->class instanceof Node\Name) {
				return null;
			}
			if (!$expr->name instanceof Node\Identifier) {
				return null;
			}

			$className = $scope->resolveName($expr->class);
			if (!$this->reflectionProvider->hasClass($className)) {
				return null;
			}

			$classReflection = $this->reflectionProvider->getClass($className);
			if (!$classReflection->hasConstant($expr->name->name)) {
				return null;
			}

			return [$classReflection->getConstant($expr->name->name)];
		}

		if ($expr instanceof Expr\BinaryOp\BitwiseOr) {
			$left = $this->resolveConstantReflections($expr->left, $scope);
			$right = $this->resolveConstantReflections($expr->right, $scope);
			if ($left === null || $right === null) {
				return null;
			}

			return [...$left, ...$right];
		}

		return null;
	}

	private function callReturnsByReference(Expr $expr, Scope $scope): bool
	{
		if ($expr instanceof Node\Expr\MethodCall) {
			if (!$expr->name instanceof Node\Identifier) {
				return false;
			}
			$calledOnType = $scope->getType($expr->var);
			$methodReflection = $scope->getMethodReflection($calledOnType, $expr->name->name);
			if ($methodReflection === null) {
				return false;
			}
			return $methodReflection->returnsByReference()->yes();
		}

		if ($expr instanceof Node\Expr\StaticCall) {
			if (!$expr->name instanceof Node\Identifier) {
				return false;
			}
			if ($expr->class instanceof Node\Name) {
				$calledOnType = $scope->resolveTypeByName($expr->class);
			} else {
				$calledOnType = $scope->getType($expr->class);
			}
			$methodReflection = $scope->getMethodReflection($calledOnType, $expr->name->name);
			if ($methodReflection === null) {
				return false;
			}
			return $methodReflection->returnsByReference()->yes();
		}

		if ($expr instanceof Node\Expr\FuncCall) {
			if ($expr->name instanceof Node\Name) {
				if ($this->reflectionProvider->hasFunction($expr->name, $scope)) {
					$functionReflection = $this->reflectionProvider->getFunction($expr->name, $scope);
					return $functionReflection->returnsByReference()->yes();
				}
			}
		}

		return false;
	}

}
