<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\AnalysedFileDependencyCollector;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\GenericParametersAcceptorResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PartialFunctionApplicationParameters;
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
use PHPStan\Type\Generic\AbsorbedTemplateArgumentType;
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
use function array_last;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function lcfirst;
use function max;
use function sprintf;
use function ucfirst;

#[AutowiredService]
final class FunctionCallParametersCheck
{

	/**
	 * Marks a call node that was synthesized from a different node (e.g. the callback call of call_user_func()),
	 * its arguments do not correspond to the arguments of the node in the file, so it cannot be fixed.
	 */
	public const NOT_FIXABLE_CALL_ATTRIBUTE = 'phpstanNotFixableCall';

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NullsafeCheck $nullsafeCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private ReflectionProvider $reflectionProvider,
		private ParameterClosureTypeHelper $parameterClosureTypeHelper,
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
		FunctionReflection|MethodReflection|null $calleeReflection = null,
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
		$parametersWithAllowedConstants = [];
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if (
				$parameter instanceof ExtendedParameterReflection
				&& $parameter->getAllowedConstants() !== null
			) {
				$allowedConstantsTypes[] = $parameter->getType();
				$parametersWithAllowedConstants[] = $parameter;
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

		/** @var array<int, array{Expr|Node\ArgPlaceholder, Type|null, bool, string|null, int}> $arguments */
		$arguments = [];
		$isPartialApplication = $funcCall->isPartialFunctionApplication();
		$args = $isPartialApplication ? $funcCall->getRawArgs() : $funcCall->getArgs();
		$hasNamedArguments = false;
		$hasUnpackedArgument = false;
		$hasVariadicPlaceholder = false;
		$errors = [];
		foreach ($args as $arg) {
			if ($hasVariadicPlaceholder) {
				// reported by checkPartialApplicationSyntax()
				break;
			}
			if ($arg instanceof Node\VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}
			if ($arg instanceof Node\ArgPlaceholder) {
				$argumentName = null;
				if ($arg->name !== null) {
					$hasNamedArguments = true;
					$argumentName = $arg->name->toString();
				}
				$arguments[] = [$arg, null, false, $argumentName, $arg->getStartLine()];
				continue;
			}

			if ($isPartialApplication && $arg->unpack) {
				// reported by checkPartialApplicationSyntax()
				continue;
			}

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

		if (
			$hasNamedArguments
			&& !$scope->getPhpVersion()->supportsNamedArguments()->yes()
			&& !(bool) $funcCall->getAttribute(AttributesCheck::IS_ATTRIBUTE, false)
		) {
			$errors[] = RuleErrorBuilder::message('Named arguments are supported only on PHP 8.0 and later.')
				->identifier('argument.namedNotSupported')
				->line($funcCallLine)
				->nonIgnorable()
				->build();
		}

		if (!$hasNamedArguments) {
			$invokedParametersCount = count($arguments);
			foreach ($arguments as [2 => $unpack]) {
				if ($unpack) {
					$invokedParametersCount = max($functionParametersMinCount, $functionParametersMaxCount);
					break;
				}
			}

			if (
				// ... of a partial application fills in the parameters left
				($invokedParametersCount < $functionParametersMinCount && !$hasVariadicPlaceholder)
				// a partial application with too many arguments throws when it is created
				|| (($this->checkExtraArguments || $isPartialApplication) && $functionParametersMaxCount !== -1 && $invokedParametersCount > $functionParametersMaxCount)
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
			&& !$isPartialApplication
			&& !$scope->isInFirstLevelStatement()
			&& $scope->getKeepVoidType($funcCall)->isVoid()->yes()
		) {
			$errors[] = RuleErrorBuilder::message($voidReturnTypeUsed)
				->identifier(sprintf('%s.void', $nodeType))
				->line($funcCallLine)
				->build();
		}

		[$addedErrors, $argumentsWithParameters] = $this->processArguments($parametersAcceptor, $funcCallLine, $isBuiltin, $arguments, $hasNamedArguments, !$hasVariadicPlaceholder, $missingParameterMessage, $unknownParameterMessage);
		foreach ($addedErrors as $error) {
			$errors[] = $error;
		}

		if (!$this->checkArgumentTypes && !$this->checkArgumentsPassedByReference) {
			return $errors;
		}

		$positionalArgumentsFix = null;
		foreach ($argumentsWithParameters as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter, $originalParameter]) {
			if ($argumentValue instanceof Node\ArgPlaceholder) {
				// a placeholder has no value, it becomes a parameter of the closure
				continue;
			}

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
					$errorBuilder = RuleErrorBuilder::message(sprintf($namedArgumentMessage, sprintf('named argument $%s', $argumentName)))
						->identifier('argument.named')
						->line($argumentLine);
					$positionalArgumentsFix ??= $this->createPositionalArgumentsFix($parametersAcceptor, $funcCall);
					if ($positionalArgumentsFix !== false) {
						// every named argument of the call reports the same fix, the identical changes are applied once
						$errorBuilder->fixNode($funcCall, $positionalArgumentsFix);
					}
					$errors[] = $errorBuilder->build();
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
				// whether the call is reported depends on the subclasses, which can be anywhere
				$scope->emitCollectedData(AnalysedFileDependencyCollector::class, null);
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
					if (
						!$accepts->result
						&& $calleeReflection !== null
						&& ($argumentValue instanceof Expr\Closure || $argumentValue instanceof Expr\ArrowFunction)
						&& $scope instanceof DependencyTracker
					) {
						// the closure body was analysed with the type from a parameter closure type extension, see ArgumentsHandler
						$closureParameterType = $this->parameterClosureTypeHelper->getParameterType($funcCall, $calleeReflection, $parameter, $scope);
						if ($closureParameterType !== null) {
							$closureAccepts = $this->ruleLevelHelper->accepts($closureParameterType, $argumentValueType, $isStrictTypes);
							if ($closureAccepts->result) {
								$accepts = $closureAccepts;
							}
						}
					}

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
					}
				}

				// only the parameter of a resolved generic acceptor has an original
				// type to tell an unresolvable type this call made from a declared one
				$unresolvableParameterType = $originalParameter !== null && !$isPartialApplication ? $this->unresolvableTypeHelper->getUnresolvableType($parameterType) : null;
				if (
					$unresolvableParameterType !== null
					&& $this->unresolvableTypeHelper->getUnresolvableType($originalParameter->getType()) === null
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
								$allowedForParameter = false;
								foreach ($parametersWithAllowedConstants as $parameterWithAllowedConstants) {
									if ($parameterWithAllowedConstants->checkAllowedConstants([$constantReflection])->isOk()) {
										$allowedForParameter = true;
										break;
									}
								}
								if (!$allowedForParameter) {
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

		if ($this->checkMissingTypehints && !$isPartialApplication && $parametersAcceptor instanceof ResolvedFunctionVariant) {
			$originalParametersAcceptor = $parametersAcceptor->getOriginalParametersAcceptor();
			$resolvedTypes = $parametersAcceptor->getResolvedTemplateTypeMap()->getTypes();
			if (!$isBuiltin && count($resolvedTypes) > 0) {
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

					// The parameter type declares the template next to a member that accepts
					// the given argument on its own, so the caller has nothing to fix.
					if ($type instanceof AbsorbedTemplateArgumentType) {
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
				$unresolvableReturnType !== null
				&& $this->unresolvableTypeHelper->getUnresolvableType($originalParametersAcceptor->getReturnType()) === null
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
	 * The placements of placeholders PHP refuses to compile, whatever the callee is.
	 *
	 * @return list<IdentifierRuleError>
	 */
	public function checkPartialApplicationSyntax(Node\Expr\CallLike $call): array
	{
		$errors = [];
		$hasVariadicPlaceholder = false;
		foreach ($call->getRawArgs() as $arg) {
			if ($hasVariadicPlaceholder) {
				// PHP judges the argument right after the variadic placeholder
				if ($arg instanceof Node\VariadicPlaceholder) {
					$errorBuilder = RuleErrorBuilder::message('Variadic placeholder may only appear once.')
						->identifier('argument.duplicateVariadicPlaceholder');
				} else {
					$errorBuilder = RuleErrorBuilder::message('Variadic placeholder must be last.')
						->identifier('argument.variadicPlaceholderNotLast');
				}
				$errors[] = $errorBuilder
					->line($arg->getStartLine())
					->nonIgnorable()
					->build();
				break;
			}
			if ($arg instanceof Node\VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}
			if (!$arg instanceof Node\Arg || !$arg->unpack) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message('Cannot combine partial application and unpacking.')
				->identifier('argument.unpackInPartialApplication')
				->line($arg->getStartLine())
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

	/**
	 * Checks the bound arguments and placeholders of a partial function application
	 * against the callee: PHP throws when such a closure is created from arguments
	 * that do not fit.
	 *
	 * @param 'callable'|'method'|'staticMethod'|'function' $nodeType
	 * @param string $calleeDescription like "function foo" or "method Foo::bar()", escaped for sprintf()
	 * @return list<IdentifierRuleError>
	 */
	public function checkPartialApplication(
		ParametersAcceptor $parametersAcceptor,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		bool $isBuiltin,
		Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall $call,
		string $nodeType,
		TrinaryLogic $acceptsNamedArguments,
		string $calleeDescription,
		FunctionReflection|MethodReflection|null $calleeReflection,
	): array
	{
		if (PartialFunctionApplicationParameters::isMagicMethod($calleeReflection)) {
			// the __call() trampoline it calls only has a variadic parameter
			$errors = [];
			foreach ($call->getRawArgs() as $arg) {
				if (!$arg instanceof Node\ArgPlaceholder || $arg->name === null) {
					continue;
				}
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot use named placeholder for unknown or variadic parameter $%s.', $arg->name->toString()))
					->identifier('argument.unknown')
					->line($arg->getStartLine())
					->build();
			}
			if ($errors !== []) {
				return $errors;
			}
		}

		// templates are resolved from the bound arguments, as in a call;
		// the ones only the placeholders determine stay unresolved
		$argTypes = [];
		$position = 0;
		foreach ($call->getRawArgs() as $arg) {
			if ($arg instanceof Node\VariadicPlaceholder) {
				break;
			}
			if ($arg instanceof Node\ArgPlaceholder) {
				if ($arg->name === null) {
					$position++;
				}
				continue;
			}
			if ($arg->unpack) {
				continue;
			}
			if ($arg->name !== null) {
				$argTypes[$arg->name->toString()] = $scope->getType($arg->value);
				continue;
			}
			$argTypes[$position++] = $scope->getType($arg->value);
		}
		if (!$parametersAcceptor->getTemplateTypeMap()->isEmpty()) {
			$parametersAcceptor = GenericParametersAcceptorResolver::resolve($argTypes, $parametersAcceptor);
		}

		$applied = ucfirst($calleeDescription) . ' partially applied with';

		return $this->check(
			$parametersAcceptor,
			$scope,
			$isBuiltin,
			$call,
			$nodeType,
			$acceptsNamedArguments,
			$applied . ' %d argument, %d required.',
			$applied . ' %d arguments, %d required.',
			$applied . ' %d argument, at least %d required.',
			$applied . ' %d arguments, at least %d required.',
			$applied . ' %d argument, %d-%d required.',
			$applied . ' %d arguments, %d-%d required.',
			'%s of ' . $calleeDescription . ' expects %s, %s given.',
			'Result of ' . $calleeDescription . ' (void) is used.',
			'%s of ' . $calleeDescription . ' is passed by reference, so it expects variables only.',
			'Unable to resolve the template type %s in partial application of ' . $calleeDescription,
			'Missing parameter $%s in partial application of ' . $calleeDescription . '.',
			'Unknown parameter $%s in partial application of ' . $calleeDescription . '.',
			'Return type of partial application of ' . $calleeDescription . ' contains unresolvable type.',
			'%s of ' . $calleeDescription . ' contains unresolvable type.',
			$applied . ' %s, but it\'s not allowed because of @no-named-arguments.',
			'Constant %s is not allowed for %s of ' . $calleeDescription . '.',
			'Constants %s cannot be combined for %s of ' . $calleeDescription . '.',
			'Combining constants with | is not allowed for %s of ' . $calleeDescription . '.',
			null,
			$calleeReflection,
		);
	}

	/**
	 * @param array<int, array{Expr|Node\ArgPlaceholder, Type|null, bool, string|null, int}> $arguments
	 * @param bool $checkMissingParameters false when ... of a partial application fills in the parameters left
	 * @return array{list<IdentifierRuleError>, array<int, array{Expr|Node\ArgPlaceholder, Type|null, bool, (string|null), int, (ParameterReflection|null), (ParameterReflection|null)}>}
	 */
	private function processArguments(
		ParametersAcceptor $parametersAcceptor,
		int $line,
		bool $isBuiltin,
		array $arguments,
		bool $hasNamedArguments,
		bool $checkMissingParameters,
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
			} elseif (
				$argumentValue instanceof Node\ArgPlaceholder
				&& (!array_key_exists($argumentName, $parametersByName) || $parametersByName[$argumentName]->isVariadic())
			) {
				$namedArgumentAlreadyOccurred = true;
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot use named placeholder for unknown or variadic parameter $%s.', $argumentName))
					->identifier('argument.unknown')
					->line($argumentLine)
					->build();
				$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
				continue;
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

		// PHP refuses to fill in a default it does not know: for a parameter
		// skipped by a later named argument, or left to ... of a partial application
		$lastPassedPosition = -1;
		foreach ($parameters as $i => $parameter) {
			if ($parameter->isVariadic() || array_key_exists($parameter->getName(), $unusedParametersByName)) {
				continue;
			}
			$lastPassedPosition = $i;
		}
		foreach ($parameters as $i => $parameter) {
			if (
				!array_key_exists($parameter->getName(), $unusedParametersByName)
				|| !$parameter->getDefaultValue() instanceof ErrorType
				|| ($checkMissingParameters && (!$hasNamedArguments || !$parameter->isOptional() || $i > $lastPassedPosition))
			) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Argument #%d ($%s) must be passed explicitly, because the default value is not known.', $i + 1, $parameter->getName()))
				->identifier('argument.unknownDefaultValue')
				->line($line)
				->build();
		}

		if ($hasNamedArguments && $checkMissingParameters) {
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

	/**
	 * Rewrites the call to pass every argument positionally, skipped optional parameters get their default values.
	 *
	 * @return (Closure(Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_): (Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_))|false
	 */
	private function createPositionalArgumentsFix(
		ParametersAcceptor $parametersAcceptor,
		Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $funcCall,
	): Closure|false
	{
		if (
			// the call was created from an attribute, it is not in the file
			(bool) $funcCall->getAttribute(AttributesCheck::IS_ATTRIBUTE, false)
			|| (bool) $funcCall->getAttribute(self::NOT_FIXABLE_CALL_ATTRIBUTE, false)
		) {
			return false;
		}

		$parameters = $parametersAcceptor->getParameters();
		$parameterPositions = [];
		foreach ($parameters as $i => $parameter) {
			$parameterPositions[$parameter->getName()] = $i;
		}

		/** @var array<int, int> $argIndexByPosition */
		$argIndexByPosition = [];
		$lastPosition = 0;
		foreach ($funcCall->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				return false;
			}
			if ($arg->name === null) {
				$argIndexByPosition[$i] = $i;
				$lastPosition = max($lastPosition, $i);
				continue;
			}

			$position = $parameterPositions[$arg->name->toString()] ?? null;
			if (
				$position === null
				// a named argument that is collected by the variadic parameter keeps its string key
				|| $parameters[$position]->isVariadic()
				|| array_key_exists($position, $argIndexByPosition)
			) {
				return false;
			}
			$argIndexByPosition[$position] = $i;
			$lastPosition = max($lastPosition, $position);
		}

		/** @var array<int, Expr> $defaultValues */
		$defaultValues = [];
		for ($position = 0; $position < $lastPosition; $position++) {
			if (array_key_exists($position, $argIndexByPosition)) {
				continue;
			}

			$parameter = $parameters[$position];
			$defaultValue = $parameter->getDefaultValue();
			if (!$parameter->isOptional() || !$parameter->passedByReference()->no() || $defaultValue === null) {
				return false;
			}

			$defaultValueExpr = $this->createDefaultValueExpr($defaultValue);
			if ($defaultValueExpr === null) {
				return false;
			}
			$defaultValues[$position] = $defaultValueExpr;
		}

		return static function (Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $call) use ($argIndexByPosition, $defaultValues, $lastPosition): Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ {
			$callArgs = $call->getArgs();
			$args = [];
			for ($position = 0; $position <= $lastPosition; $position++) {
				if (!array_key_exists($position, $argIndexByPosition)) {
					$args[] = new Node\Arg(clone $defaultValues[$position]);
					continue;
				}

				$arg = clone $callArgs[$argIndexByPosition[$position]];
				$arg->name = null;
				$args[] = $arg;
			}
			$call->args = $args;

			return $call;
		};
	}

	private function createDefaultValueExpr(Type $type): ?Expr
	{
		if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			return new Expr\Array_([]);
		}

		$scalarValues = $type->getConstantScalarValues();
		if (count($scalarValues) !== 1) {
			return null;
		}

		$scalarValue = $scalarValues[0];
		if ($scalarValue === null) {
			return new Expr\ConstFetch(new Node\Name('null'));
		}
		if (is_bool($scalarValue)) {
			return new Expr\ConstFetch(new Node\Name($scalarValue ? 'true' : 'false'));
		}
		if (is_int($scalarValue)) {
			return new Node\Scalar\Int_($scalarValue);
		}
		if (is_float($scalarValue)) {
			return new Node\Scalar\Float_($scalarValue);
		}

		return new Node\Scalar\String_($scalarValue);
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

	/**
	 * When no variant accepts the number of arguments, they are combined into
	 * one whose parameters missing from some variant are optional, so mt_rand()
	 * with the variants () and ($min, $max) would accept a single argument.
	 * Such a count is checked against the variant closest to it instead.
	 * The same applies to the members of a union of closures, such as mt_rand(...).
	 *
	 * @param Node\Arg[] $args
	 * @param list<ParametersAcceptor> $variants
	 */
	public static function getVariantForArgumentsCount(array $args, array $variants, ParametersAcceptor $selectedParametersAcceptor): ?ParametersAcceptor
	{
		if (count($variants) < 2) {
			return null;
		}

		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return null;
			}
		}

		$argsCount = count($args);
		if (!self::acceptsArgumentsCount($selectedParametersAcceptor, $argsCount)) {
			return null;
		}

		$closestVariant = null;
		$closestDistance = null;
		foreach ($variants as $variant) {
			if (self::acceptsArgumentsCount($variant, $argsCount)) {
				return null;
			}

			[$minCount, $maxCount] = self::getArgumentsCountRange($variant);
			$isInsufficient = $argsCount < $minCount;
			$distance = $isInsufficient ? $minCount - $argsCount : $argsCount - (int) $maxCount;
			if ($closestDistance !== null && ($distance > $closestDistance || ($distance === $closestDistance && !$isInsufficient))) {
				continue;
			}

			$closestVariant = $variant;
			$closestDistance = $distance;
		}

		return $closestVariant;
	}

	private static function acceptsArgumentsCount(ParametersAcceptor $parametersAcceptor, int $argsCount): bool
	{
		[$minCount, $maxCount] = self::getArgumentsCountRange($parametersAcceptor);

		return $argsCount >= $minCount && ($maxCount === null || $argsCount <= $maxCount);
	}

	/**
	 * @return array{int, int|null}
	 */
	private static function getArgumentsCountRange(ParametersAcceptor $parametersAcceptor): array
	{
		$minCount = 0;
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if ($parameter->isOptional()) {
				continue;
			}

			$minCount++;
		}

		return [$minCount, $parametersAcceptor->isVariadic() ? null : count($parametersAcceptor->getParameters())];
	}

}
