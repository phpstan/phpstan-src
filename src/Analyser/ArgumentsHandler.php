<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ExprHandler\AssignHandler;
use PHPStan\Analyser\ExprHandler\ClosureHandler;
use PHPStan\Analyser\ExprHandler\Helper\ClosureParameterResolver;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\Generics\TemplateArgumentObserver;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\Expr\NativeTypeExpr;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_filter;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function get_class;
use function is_string;
use function spl_object_id;
use function sprintf;
use function usort;

/**
 * Processes the arguments of a call for NodeScopeResolver: walks them in order
 * on the evolving scope, selects the parameters acceptor from the gathered
 * argument types, and applies what the callee's parameters imply (closure
 * parameter types and bound $this, by-ref and out types, invalidation).
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ArgumentsHandler.cpp')]
final class ArgumentsHandler
{

	/**
	 * @param ExtensionsCollection<FunctionParameterOutTypeExtension> $functionParameterOutTypeExtensions
	 * @param ExtensionsCollection<MethodParameterOutTypeExtension> $methodParameterOutTypeExtensions
	 * @param ExtensionsCollection<StaticMethodParameterOutTypeExtension> $staticMethodParameterOutTypeExtensions
	 * @param ExtensionsCollection<FunctionParameterClosureThisExtension> $functionParameterClosureThisExtensions
	 * @param ExtensionsCollection<MethodParameterClosureThisExtension> $methodParameterClosureThisExtensions
	 * @param ExtensionsCollection<StaticMethodParameterClosureThisExtension> $staticMethodParameterClosureThisExtensions
	 * @param ExtensionsCollection<FunctionParameterClosureTypeExtension> $functionParameterClosureTypeExtensions
	 * @param ExtensionsCollection<MethodParameterClosureTypeExtension> $methodParameterClosureTypeExtensions
	 * @param ExtensionsCollection<StaticMethodParameterClosureTypeExtension> $staticMethodParameterClosureTypeExtensions
	 */
	public function __construct(
		private TemplateArgumentObserver $templateArgumentObserver,
		private ExpressionResultFactory $expressionResultFactory,
		private ClosureProcessor $closureProcessor,
		#[AutowiredExtensions(of: FunctionParameterOutTypeExtension::class)]
		private ExtensionsCollection $functionParameterOutTypeExtensions,
		#[AutowiredExtensions(of: MethodParameterOutTypeExtension::class)]
		private ExtensionsCollection $methodParameterOutTypeExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterOutTypeExtension::class)]
		private ExtensionsCollection $staticMethodParameterOutTypeExtensions,
		#[AutowiredExtensions(of: FunctionParameterClosureThisExtension::class)]
		private ExtensionsCollection $functionParameterClosureThisExtensions,
		#[AutowiredExtensions(of: MethodParameterClosureThisExtension::class)]
		private ExtensionsCollection $methodParameterClosureThisExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterClosureThisExtension::class)]
		private ExtensionsCollection $staticMethodParameterClosureThisExtensions,
		#[AutowiredExtensions(of: FunctionParameterClosureTypeExtension::class)]
		private ExtensionsCollection $functionParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: MethodParameterClosureTypeExtension::class)]
		private ExtensionsCollection $methodParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterClosureTypeExtension::class)]
		private ExtensionsCollection $staticMethodParameterClosureTypeExtensions,
		#[AutowiredParameter(ref: '%exceptions.implicitThrows%')]
		private bool $implicitThrows,
		private AssignHandler $assignHandler,
		private ClosureTypeResolver $closureTypeResolver,
		private ClosureParameterResolver $closureParameterResolver,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 * @param (callable(MutatingScope): MutatingScope)|null $closureBindScopeFactory
	 */
	public function processArgs(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		$calleeReflection,
		?ExtendedMethodReflection $nakedMethodReflection,
		array $parametersAcceptors,
		?array $namedArgumentsVariants,
		CallLike $callLike,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?callable $closureBindScopeFactory = null,
	): ArgsResult
	{
		$args = $callLike->getArgs();

		// Evolving-scope arg types: gathered as each argument is processed on the
		// scope that evolves arg-to-arg. They select the FINAL resolved acceptor
		// (the call's return type, by-ref OUT types), which type-resolves generics
		// from the actual argument types.
		$gatheredTypes = [];
		$gatheredUnpack = false;
		$gatheredHasName = false;
		$gatheredArgTypeByIndex = [];

		// Metadata acceptor base - NO forward read. The per-argument resolution below picks the
		// count-correct variant (the by-ref/variadic STRUCTURE is variant-stable except where it is
		// keyed off the argument count, e.g. sscanf - and the count is known structurally) and
		// resolves generic parameter types from the args gathered so far; the call's return type
		// comes from the post-loop resolved acceptor.
		$metadataAcceptor = $parametersAcceptors[0] ?? null;

		// Whether selecting an acceptor is type-driven at all: multiple variants to
		// choose between, templates or conditionals to resolve from the arg types,
		// or named-argument variants. When it is not, the gathered arg types can
		// never influence the selected acceptor, so the faithful-return gather walk
		// of a closure/arrow argument (gatherClosureArgType()) would be pure waste -
		// its signature-only shallow type keeps the count/name bookkeeping correct.
		$typeDrivenAcceptorSelection = count($parametersAcceptors) > 1
			|| $namedArgumentsVariants !== null
			|| ($metadataAcceptor !== null && ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableType($metadataAcceptor));

		// Both predicates are hoisted out of the per-argument loop - they traverse
		// the acceptor's parameter types.
		$hasTemplateParameterType = $metadataAcceptor !== null
			&& ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType($metadataAcceptor);
		$argMetadataIsTypeDriven = count($parametersAcceptors) > 1 || $hasTemplateParameterType;

		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		/** @var list<array{InvalidateExprNode[], string[]}> $deferredInvalidateExpressions */
		$deferredInvalidateExpressions = [];
		/** @var ProcessClosureResult[] $deferredByRefClosureResults */
		$deferredByRefClosureResults = [];

		$processingOrder = array_keys($args);
		usort($processingOrder, static function (int $a, int $b) use ($args): int {
			$aOriginalArg = $args[$a]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$bOriginalArg = $args[$b]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$aValue = $aOriginalArg !== null ? $aOriginalArg->value : $args[$a]->value;
			$bValue = $bOriginalArg !== null ? $bOriginalArg->value : $args[$b]->value;
			$aIsClosure = $aValue instanceof Expr\Closure || $aValue instanceof Expr\ArrowFunction;
			$bIsClosure = $bValue instanceof Expr\Closure || $bValue instanceof Expr\ArrowFunction;
			if ($aIsClosure !== $bIsClosure) {
				// closures sort after non-closures so every sibling feeding an
				// intrinsic override / generic callable(T) is in scope first
				return $aIsClosure ? 1 : -1;
			}

			$aOriginal = $args[$a]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			$bOriginal = $args[$b]->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			if ($aOriginal === null && $bOriginal === null) {
				return $a <=> $b;
			}
			if ($aOriginal === null) {
				return 1;
			}
			if ($bOriginal === null) {
				return -1;
			}

			return $aOriginal->getStartTokenPos() <=> $bOriginal->getStartTokenPos();
		});

		$argResults = [];
		$byRefArguments = [];
		$countStableMetadataAcceptor = null;
		foreach ($processingOrder as $i) {
			$arg = $args[$i];

			if ($arg->value instanceof Expr\Closure || $arg->value instanceof Expr\ArrowFunction) {
				// Gather the closure/arrow type for the FINAL resolved acceptor on
				// the evolving scope, BEFORE the body is processed with a possibly
				// generic-resolved parameter injected, so the inferred return type
				// stays faithful to the closure's own declaration and its own
				// contribution (a TValue from its return) participates in the final
				// resolution (see gatherClosureArgType()).
				$originalArgForGather = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
				$gatheredArgTypeByIndex[$i] = $typeDrivenAcceptorSelection
					? $this->gatherClosureArgType($parametersAcceptors, $i, $arg->value, $scope)
					: $this->closureTypeResolver->getClosureType($scope, $arg->value, true, $storage);
				$this->addGatheredArgType($gatheredTypes, $gatheredUnpack, $gatheredHasName, $originalArgForGather, $i, $gatheredArgTypeByIndex[$i]);
			} elseif (
				$argMetadataIsTypeDriven
				&& !$arg->unpack
				&& $arg->value instanceof Expr\Array_
				&& $this->argConsumesResolvedParameterType($arg->value)
			) {
				// An array literal holding closures decides the templates of its own
				// parameter through its keys and its non-closure values - and those
				// very templates type the closures nested in it. Pin a SKELETON of the
				// array (declared closure signatures, scope-known leaves, mixed for
				// anything that needs a walk) so the per-argument resolution below sees
				// them; the walk that follows overwrites it with the real type.
				$gatheredArgTypeByIndex[$i] = $this->gatherArrayArgTypeSkeleton($nodeScopeResolver, $arg->value, $scope);
			}

			$argMetadataAcceptor = $metadataAcceptor;
			if ($metadataAcceptor !== null && $argMetadataIsTypeDriven) {
				if ($this->argConsumesResolvedParameterType($arg->value)) {
					// Resolve the acceptor for this argument from the args gathered SO FAR, padded to the
					// full argument count with mixed. Closures sort last and by-ref out-params follow the
					// args that pin them, so determining siblings are already processed; the mixed pad keeps
					// the argument COUNT correct so the by-ref/variadic variant stays stable (e.g. sscanf),
					// while processed siblings resolve a generic callable(T) parameter. No forward read.
					$paddedTypes = [];
					$paddedUnpack = false;
					$paddedHasName = false;
					foreach ($args as $j => $paddedArg) {
						$paddedOriginalArg = $paddedArg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $paddedArg;
						$this->addGatheredArgType($paddedTypes, $paddedUnpack, $paddedHasName, $paddedOriginalArg, $j, $gatheredArgTypeByIndex[$j] ?? new MixedType());
					}
					$argMetadataAcceptor = $this->selectArgsMetadataAcceptor($nodeScopeResolver, $args, $paddedTypes, $parametersAcceptors, $namedArgumentsVariants, $paddedHasName, $paddedUnpack, $scope);
				} else {
					// Only a closure/arrow function consumes the generic-RESOLVED
					// parameter type: its body is inferred from the resolved
					// callable(T) - directly, or through the in-function-call stack
					// when nested anywhere inside the argument. Every other argument
					// reads variant-stable facts off its parameter (by-ref flag,
					// callable bookkeeping), so one all-mixed count-stable selection
					// serves them all instead of a full template inference per argument.
					if ($countStableMetadataAcceptor === null) {
						$paddedTypes = [];
						$paddedUnpack = false;
						$paddedHasName = false;
						foreach ($args as $j => $paddedArg) {
							$paddedOriginalArg = $paddedArg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $paddedArg;
							$this->addGatheredArgType($paddedTypes, $paddedUnpack, $paddedHasName, $paddedOriginalArg, $j, new MixedType());
						}
						$countStableMetadataAcceptor = $this->selectArgsMetadataAcceptor($nodeScopeResolver, $args, $paddedTypes, $parametersAcceptors, $namedArgumentsVariants, $paddedHasName, $paddedUnpack, $scope);
					}
					$argMetadataAcceptor = $countStableMetadataAcceptor;
				}
			}
			$parameters = $argMetadataAcceptor !== null ? $argMetadataAcceptor->getParameters() : null;

			$assignByReference = false;
			$parameter = null;
			$parameterType = null;
			$parameterNativeType = null;
			if ($parameters !== null) {
				$matchedParameter = null;
				if ($arg->name !== null) {
					foreach ($parameters as $p) {
						if ($p->getName() === $arg->name->toString()) {
							$matchedParameter = $p;
							break;
						}
					}
				} elseif (isset($parameters[$i])) {
					$matchedParameter = $parameters[$i];
				}

				if ($matchedParameter !== null) {
					$assignByReference = $matchedParameter->passedByReference()->createsNewVariable();
					$parameterType = $matchedParameter->getType();

					if ($matchedParameter instanceof ExtendedParameterReflection) {
						$parameterNativeType = $matchedParameter->getNativeType();
					}
					$parameter = $matchedParameter;
				} elseif (count($parameters) > 0 && $argMetadataAcceptor->isVariadic()) {
					$lastParameter = array_last($parameters);
					$assignByReference = $lastParameter->passedByReference()->createsNewVariable();
					$parameterType = $lastParameter->getType();

					if ($lastParameter instanceof ExtendedParameterReflection) {
						$parameterNativeType = $lastParameter->getNativeType();
					}
					$parameter = $lastParameter;
				}
			}

			if ($parameter !== null && !$parameter->passedByReference()->no()) {
				$byRefArguments[spl_object_id($arg->value)] = true;
			}
			$lookForUnset = false;
			if ($assignByReference) {
				$isBuiltin = false;
				if ($calleeReflection instanceof FunctionReflection && $calleeReflection->isBuiltin()) {
					$isBuiltin = true;
				} elseif ($calleeReflection instanceof ExtendedMethodReflection && $calleeReflection->getDeclaringClass()->isBuiltin()) {
					$isBuiltin = true;
				}
				if (
					$isBuiltin
					|| ($parameterNativeType === null || !$parameterNativeType->isNull()->no())
				) {
					$scope = $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($scope, $arg->value);
					$lookForUnset = true;
				}
			}

			$originalArg = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg;
			if ($calleeReflection !== null) {
				$rememberTypes = !$originalArg->value instanceof Expr\Closure && !$originalArg->value instanceof Expr\ArrowFunction;
				$scope = $scope->pushInFunctionCall($calleeReflection, $parameter, $rememberTypes);
			}

			$nodeScopeResolver->callNodeCallback($nodeCallback, $originalArg, $scope, $storage);

			$originalScope = $scope;
			$scopeToPass = $scope;
			if ($i === 0 && $closureBindScopeFactory !== null && ($arg->value instanceof Expr\Closure || $arg->value instanceof Expr\ArrowFunction)) {
				$scopeToPass = $closureBindScopeFactory($scope);
			}

			if ($arg->value instanceof Expr\Closure) {

				$storedClosureArgResult = null;
				if ($nodeScopeResolver->isReturningStoredExpressionResults() || $nodeScopeResolver->isConsumingStoredExpressionResults()) {
					// an on-demand re-walk of the enclosing call must not re-run the
					// closure's whole by-ref convergence: consume the main walk's
					// stored result, or (when the body release already dropped it)
					// price the closure through getClosureType's per-node cache -
					// a single body walk on miss, none on repeat asks
					$storedClosureArgResult = $storage->findExpressionResult($arg->value);
					// consume mode alone (the nullsafe call's plain-twin walk) is the
					// FIRST and only walk of these arguments - nothing stored them, so
					// a miss means "walk it", not "price it silently": pricing skips
					// the body walk and never fires the closure's node callbacks
					if ($storedClosureArgResult === null && $nodeScopeResolver->isReturningStoredExpressionResults()) {
						$storedClosureArgResult = $this->expressionResultFactory->create(
							$scopeToPass,
							beforeScope: $scopeToPass,
							expr: $arg->value,
							hasYield: false,
							isAlwaysTerminating: false,
							throwPoints: [],
							impurePoints: [],
							type: $this->closureTypeResolver->getClosureType($scopeToPass, $arg->value, false, $storage),
							nativeType: $this->closureTypeResolver->getClosureType($scopeToPass->doNotTreatPhpDocTypesAsCertain(), $arg->value, false, $storage),
							typeCallback: null,
							specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
						);
						$nodeScopeResolver->storeExpressionResult($storage, $arg->value, $storedClosureArgResult);
					}
				}
				if ($storedClosureArgResult !== null) {
					$argResults[spl_object_id($arg->value)] = $storedClosureArgResult;
				} else {
					$restoreThisScope = null;
					if (
						$closureBindScopeFactory === null
						&& $parameter instanceof ExtendedParameterReflection
						&& !$arg->value->static
					) {
						$closureThisType = $this->resolveClosureThisType($callLike, $calleeReflection, $parameter, $scopeToPass);
						if ($closureThisType !== null) {
							$restoreThisScope = $scopeToPass;
							$scopeToPass = $scopeToPass->assignVariable('this', $closureThisType, new ObjectWithoutClassType(), TrinaryLogic::createYes())
								->withClosureBindScopeClasses($closureThisType->getObjectClassNames());
						}
					}

					if ($parameter !== null) {
						$overwritingParameterType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass);

						if ($overwritingParameterType !== null) {
							$parameterType = $overwritingParameterType;

							// resolve the native flavour through the same extension on the
							// natively-promoted scope, so the closure parameters keep
							// their native precision too
							$overwritingParameterNativeType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass->doNotTreatPhpDocTypesAsCertain());
							if ($overwritingParameterNativeType !== null) {
								$parameterNativeType = $overwritingParameterNativeType;
							}
						}
					}

					$closureResult = $this->closureProcessor->processClosureNode($nodeScopeResolver, $stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $context, $parameterType, $parameterNativeType);
					if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
						$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable(), $throwPoint->isFromThrowExpr()) : InternalThrowPoint::createImplicit($scope, $arg->value), $closureResult->getThrowPoints()));
						$impurePoints = array_merge($impurePoints, $closureResult->getImpurePoints());
					}

					$storedClosureResult = $this->expressionResultFactory->create(
						$closureResult->getScope(),
						$scopeToPass,
						$arg->value,
						variableFlow: ClosureHandler::getVariableFlow($arg->value),
						hasYield: false,
						isAlwaysTerminating: false,
						throwPoints: [],
						impurePoints: [],
						type: $this->closureTypeResolver->buildClosureTypeForClosure(
							$scopeToPass,
							$arg->value,
							$closureResult->getGatheredReturnStatements(),
							$closureResult->getGatheredYieldStatements(),
							$closureResult->getExecutionEnds(),
							$closureResult->getThrowPoints(),
							$closureResult->getClosureTypeImpurePoints(),
							$closureResult->getInvalidateExpressions(),
							false,
							$storage,
						),
						// the native flavour reads the stored native types off the same
						// single body walk - no second walk on the promoted scope
						nativeType: $this->closureTypeResolver->buildClosureTypeForClosure(
							$scopeToPass,
							$arg->value,
							$closureResult->getGatheredReturnStatements(),
							$closureResult->getGatheredYieldStatements(),
							$closureResult->getExecutionEnds(),
							$closureResult->getThrowPoints(),
							$closureResult->getClosureTypeImpurePoints(),
							$closureResult->getInvalidateExpressions(),
							true,
							$storage,
						),
						typeCallback: null,
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					);
					$nodeScopeResolver->storeExpressionResult($storage, $arg->value, $storedClosureResult);
					// the closure node's own callback fires after its result is
					// stored, mirroring processExprNodeInternal() - callback-side
					// getType() answers from the stored result
					$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
					// the arg result must be the properly-typed stored result -
					// ArgsResult readers price array_push() & co. from it
					$argResults[spl_object_id($arg->value)] = $storedClosureResult;

					$uses = [];
					foreach ($arg->value->uses as $use) {
						if (!is_string($use->var->name)) {
							continue;
						}

						$uses[] = $use->var->name;
					}

					$scope = $closureResult->getScope();
					$deferredByRefClosureResults[] = $closureResult;
					// Prefer the invalidate expressions collected on the ClosureType -
					// they also cover writes the closure's own body walk observed,
					// unlike $closureResult->getInvalidateExpressions().
					$closureExprType = $storedClosureResult->getType();
					$invalidateExpressions = $closureExprType instanceof ClosureType
						? $closureExprType->getInvalidateExpressions()
						: $closureResult->getInvalidateExpressions();
					if ($restoreThisScope !== null) {
						$nodeFinder = new NodeFinder();
						$cb = static fn ($expr) => $expr instanceof Variable && $expr->name === 'this';
						foreach ($invalidateExpressions as $j => $invalidateExprNode) {
							$foundThis = $nodeFinder->findFirst([$invalidateExprNode->getExpr()], $cb);
							if ($foundThis === null) {
								continue;
							}

							unset($invalidateExpressions[$j]);
						}
						$invalidateExpressions = array_values($invalidateExpressions);
						$scope = $scope->restoreThis($restoreThisScope);
					}

					if ($this->shouldInvalidateCallbackExpressions($parameter)) {
						$deferredInvalidateExpressions[] = [$invalidateExpressions, $uses];
					}
				}
			} elseif ($arg->value instanceof Expr\ArrowFunction) {

				$storedClosureArgResult = null;
				if ($nodeScopeResolver->isReturningStoredExpressionResults() || $nodeScopeResolver->isConsumingStoredExpressionResults()) {
					// see the Closure branch above - consume or price via the cache
					$storedClosureArgResult = $storage->findExpressionResult($arg->value);
					// consume mode alone (the nullsafe call's plain-twin walk) is the
					// first and only walk of the argument - a miss means walk it
					if ($storedClosureArgResult === null && $nodeScopeResolver->isReturningStoredExpressionResults()) {
						$storedClosureArgResult = $this->expressionResultFactory->create(
							$scopeToPass,
							beforeScope: $scopeToPass,
							expr: $arg->value,
							hasYield: false,
							isAlwaysTerminating: false,
							throwPoints: [],
							impurePoints: [],
							type: $this->closureTypeResolver->getClosureType($scopeToPass, $arg->value, false, $storage),
							nativeType: $this->closureTypeResolver->getClosureType($scopeToPass->doNotTreatPhpDocTypesAsCertain(), $arg->value, false, $storage),
							typeCallback: null,
							specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
						);
						$nodeScopeResolver->storeExpressionResult($storage, $arg->value, $storedClosureArgResult);
					}
				}
				if ($storedClosureArgResult !== null) {
					$argResults[spl_object_id($arg->value)] = $storedClosureArgResult;
				} else {
					if (
						$closureBindScopeFactory === null
						&& $parameter instanceof ExtendedParameterReflection
						&& !$arg->value->static
					) {
						$closureThisType = $this->resolveClosureThisType($callLike, $calleeReflection, $parameter, $scopeToPass);
						if ($closureThisType !== null) {
							$scopeToPass = $scopeToPass->assignVariable('this', $closureThisType, new ObjectWithoutClassType(), TrinaryLogic::createYes())
								->withClosureBindScopeClasses($closureThisType->getObjectClassNames());
						}
					}

					if ($parameter !== null) {
						$overwritingParameterType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass);

						if ($overwritingParameterType !== null) {
							$parameterType = $overwritingParameterType;

							// resolve the native flavour through the same extension on the
							// natively-promoted scope, so the closure parameters keep
							// their native precision too
							$overwritingParameterNativeType = $this->getParameterTypeFromParameterClosureTypeExtension($callLike, $calleeReflection, $parameter, $scopeToPass->doNotTreatPhpDocTypesAsCertain());
							if ($overwritingParameterNativeType !== null) {
								$parameterNativeType = $overwritingParameterNativeType;
							}
						}
					}

					$arrowFunctionResult = $this->closureProcessor->processArrowFunctionNode($nodeScopeResolver, $stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $parameterType, $parameterNativeType, $context);
					$arrowFunctionExprResult = $arrowFunctionResult->getExpressionResult();
					if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
						$throwPoints = array_merge($throwPoints, array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable(), $throwPoint->isFromThrowExpr()) : InternalThrowPoint::createImplicit($scope, $arg->value), $arrowFunctionExprResult->getThrowPoints()));
						$impurePoints = array_merge($impurePoints, $arrowFunctionExprResult->getImpurePoints());
					}
					$arrowFunctionScope = $arrowFunctionResult->getArrowFunctionScope();
					// both flavours are built from the single body walk (see
					// ArrowFunctionHandler); the built type also answers the
					// invalidate-expressions read below without re-walking the
					// still-unstored node through Scope::getType()
					$arrowFunctionType = $this->closureTypeResolver->buildClosureTypeForArrowFunction(
						$scopeToPass,
						$arg->value,
						$arrowFunctionScope,
						$arrowFunctionResult->getClosureTypeThrowPoints(),
						$arrowFunctionResult->getClosureTypeImpurePoints(),
						$arrowFunctionResult->getInvalidateExpressions(),
						false,
						$storage,
					);
					$storedArrowResult = $this->expressionResultFactory->create(
						$arrowFunctionExprResult->getScope(),
						variableFlow: $arrowFunctionExprResult->getVariableFlow(),
						beforeScope: $scopeToPass,
						expr: $arg->value,
						hasYield: $arrowFunctionExprResult->hasYield(),
						isAlwaysTerminating: $arrowFunctionExprResult->isAlwaysTerminating(),
						throwPoints: $arrowFunctionExprResult->getThrowPoints(),
						impurePoints: $arrowFunctionExprResult->getImpurePoints(),
						type: $arrowFunctionType,
						nativeType: $this->closureTypeResolver->buildClosureTypeForArrowFunction(
							$scopeToPass,
							$arg->value,
							$arrowFunctionScope,
							$arrowFunctionResult->getClosureTypeThrowPoints(),
							$arrowFunctionResult->getClosureTypeImpurePoints(),
							$arrowFunctionResult->getInvalidateExpressions(),
							true,
							$storage,
						),
						typeCallback: null,
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					);
					$nodeScopeResolver->storeExpressionResult($storage, $arg->value, $storedArrowResult);
					// the arrow function node's own callback fires after its result
					// is stored, mirroring processExprNodeInternal() - callback-side
					// getType() answers from the stored result
					$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, $arg->value, $scopeToPass, $storage, $context);
					// the arg result must be the properly-typed stored result, not
					// the body walk's placeholder (whose typeCallback answers mixed) -
					// ArgsResult readers price array_push() & co. from it
					$argResults[spl_object_id($arg->value)] = $storedArrowResult;
					if ($this->shouldInvalidateCallbackExpressions($parameter)) {
						$deferredInvalidateExpressions[] = [$arrowFunctionType->getInvalidateExpressions(), $arrowFunctionType->getUsedVariables()];
					}
				}
				$scope = $scope->addTemplateArgumentConstraints($argResults[spl_object_id($arg->value)]->getScope()->getTemplateArgumentConstraints());
			} else {
				$enterExpressionAssignForByRef = $assignByReference && $arg->value instanceof ArrayDimFetch && $arg->value->dim === null;
				if ($enterExpressionAssignForByRef) {
					$scopeToPass = $scopeToPass->enterExpressionAssign($arg->value);
				}
				$argContext = $context->enterDeep();
				if (!$arg->unpack && $arg->value instanceof Expr\Array_) {
					$argContext = $argContext->enterPassedToType($parameterType, $parameterNativeType);
				}
				$exprResult = $nodeScopeResolver->processExprNode($stmt, $arg->value, $scopeToPass, $storage, $nodeCallback, $argContext);
				$argResults[spl_object_id($arg->value)] = $exprResult;
				$exprType = $exprResult->getType();
				$throwPoints = array_merge($throwPoints, $exprResult->getThrowPoints());
				$impurePoints = array_merge($impurePoints, $exprResult->getImpurePoints());
				$isAlwaysTerminating = $isAlwaysTerminating || $exprResult->isAlwaysTerminating();
				$scope = $exprResult->getScope();
				if ($enterExpressionAssignForByRef) {
					$scope = $scope->exitExpressionAssign($arg->value);
				}
				$hasYield = $hasYield || $exprResult->hasYield();

				if ($exprType->isCallable()->yes()) {
					$acceptors = $exprType->getCallableParametersAcceptors($scope);
					if (count($acceptors) === 1) {
						if ($this->shouldInvalidateCallbackExpressions($parameter)) {
							$deferredInvalidateExpressions[] = [$acceptors[0]->getInvalidateExpressions(), $acceptors[0]->getUsedVariables()];
						}
						if ($this->callCallbackImmediately($parameter, $parameterType, $calleeReflection)) {
							$callableThrowPoints = array_map(static fn (SimpleThrowPoint $throwPoint) => $throwPoint->isExplicit() ? InternalThrowPoint::createExplicit($scope, $throwPoint->getType(), $arg->value, $throwPoint->canContainAnyThrowable(), $throwPoint->isFromThrowExpr()) : InternalThrowPoint::createImplicit($scope, $arg->value), $acceptors[0]->getThrowPoints());
							if (!$this->implicitThrows) {
								$callableThrowPoints = array_values(array_filter($callableThrowPoints, static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit()));
							}
							$throwPoints = array_merge($throwPoints, $callableThrowPoints);
							$impurePoints = array_merge($impurePoints, array_map(static fn (SimpleImpurePoint $impurePoint) => new ImpurePoint($scope, $arg->value, $impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $acceptors[0]->getImpurePoints()));
						}
					}
				}

				$gatheredArgTypeByIndex[$i] = $exprResult->getType();
				$this->addGatheredArgType($gatheredTypes, $gatheredUnpack, $gatheredHasName, $originalArg, $i, $gatheredArgTypeByIndex[$i]);
				$templateArgumentFrame = $nodeScopeResolver->observingTemplateArgumentFrame($scope);
				if ($templateArgumentFrame !== null && $parameter !== null) {
					// the metadata acceptor is resolved against the arguments gathered
					// before this one, so a template this argument itself decides is
					// still its bound there - observe the declared parameter type,
					// where such a template is uninformative and the receiver's
					// class-level arguments are already in place
					$scope = $scope->addTemplateArgumentConstraints($this->templateArgumentObserver->collectArgument(
						$this->findOriginalParameterType($argMetadataAcceptor, $parameter) ?? $parameter->getType(),
						$gatheredArgTypeByIndex[$i],
						($calleeReflection instanceof FunctionReflection || $calleeReflection instanceof ExtendedMethodReflection) && $calleeReflection->isPure()->yes(),
					));
				}
			}

			if ($assignByReference && $lookForUnset) {
				$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $arg->value);
			}

			if ($calleeReflection !== null) {
				$scope = $scope->popInFunctionCall();
			}

			if ($i !== 0 || $closureBindScopeFactory === null) {
				continue;
			}

			$scope = $scope->restoreOriginalScopeAfterClosureBind($originalScope);
		}

		foreach ($deferredInvalidateExpressions as [$invalidateExpressions, $uses]) {
			$scope = $this->closureProcessor->processImmediatelyCalledCallable($scope, $invalidateExpressions, $uses);
		}

		foreach ($deferredByRefClosureResults as $deferredClosureResult) {
			$scope = $deferredClosureResult->applyByRefUseScope($scope);
		}

		// Type-driven resolved acceptor: the arg types gathered on the evolving
		// scope select (and generic-resolve) the acceptor that drives the call's
		// return type. Intrinsic overrides are applied on the final scope,
		// mirroring the original selectFromArgs().
		// When the selection is not type-driven, the single acceptor IS the
		// resolved acceptor - the fast path selectFromArgs() used to take.
		$resolvedAcceptor = null;
		if ($parametersAcceptors !== []) {
			$resolvedAcceptor = $typeDrivenAcceptorSelection
				? $this->selectArgsMetadataAcceptor($nodeScopeResolver, $args, $gatheredTypes, $parametersAcceptors, $namedArgumentsVariants, $gatheredHasName, $gatheredUnpack, $scope)
				: $metadataAcceptor;
		}

		if ($resolvedAcceptor !== null && $nodeScopeResolver->observingTemplateArgumentFrame($scope) !== null) {
			$scope = $scope->addTemplateArgumentConstraints($this->templateArgumentObserver->collectCall(
				$callLike,
				$resolvedAcceptor,
				$gatheredTypes,
				$callLike instanceof New_ && $calleeReflection instanceof MethodReflection ? $calleeReflection->getDeclaringClass()->getTemplateTypeMap() : null,
			));
		}

		// The by-ref OUT writeback reads the metadata acceptor: it is selected from
		// the full argument count (stable variant). When that single acceptor still
		// carries templates (fast path), its OUT types need generic-resolving from the
		// now-complete gathered arg types - the post-loop $resolvedAcceptor is exactly
		// that (same variant, resolved); otherwise the metadata acceptor is already resolved.
		$writebackAcceptor = $metadataAcceptor;
		if (
			$metadataAcceptor !== null
			&& $argMetadataIsTypeDriven
		) {
			$writebackAcceptor = $resolvedAcceptor;
		}
		$writebackParameters = $writebackAcceptor !== null ? $writebackAcceptor->getParameters() : null;
		if ($writebackParameters !== null) {
			foreach ($args as $i => $arg) {
				$assignByReference = false;
				$currentParameter = null;
				if (isset($writebackParameters[$i])) {
					$currentParameter = $writebackParameters[$i];
				} elseif (count($writebackParameters) > 0 && $writebackAcceptor->isVariadic()) {
					$currentParameter = array_last($writebackParameters);
				}

				if ($currentParameter !== null) {
					$assignByReference = $currentParameter->passedByReference()->createsNewVariable();
				}

				if ($assignByReference) {
					$argValue = $arg->value;
					if (!$argValue instanceof Variable || $argValue->name !== 'this') {
						$paramOutType = $this->getParameterOutExtensionsType($callLike, $calleeReflection, $currentParameter, $scope);
						if ($paramOutType !== null) {
							$byRefType = $paramOutType;
						} elseif (
							$currentParameter instanceof ExtendedParameterReflection
							&& $currentParameter->getOutType() !== null
						) {
							$byRefType = $currentParameter->getOutType();
						} elseif (
							$calleeReflection instanceof MethodReflection
							&& !$calleeReflection->getDeclaringClass()->isBuiltin()
						) {
							$byRefType = $currentParameter->getType();
						} elseif (
							$calleeReflection instanceof FunctionReflection
							&& !$calleeReflection->isBuiltin()
						) {
							$byRefType = $currentParameter->getType();
						} else {
							$byRefType = new MixedType();
						}

						// what the call writes back is described by PHPDoc (@param, @param-out,
						// a parameter-out extension) - natively only the parameter's own
						// type declaration is guaranteed
						$byRefNativeType = $currentParameter instanceof ExtendedParameterReflection
							? $currentParameter->getNativeType()
							: $byRefType;

						$scope = $this->assignHandler->processVirtualAssign(
							$nodeScopeResolver,
							$scope,
							$storage,
							$stmt,
							$argValue,
							new NativeTypeExpr($byRefType, $byRefNativeType),
							$nodeCallback,
						)->getScope();
						$scope = $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope, $argValue);
					}
				} elseif ($calleeReflection !== null && $calleeReflection->hasSideEffects()->yes()) {
					$argType = $this->readArgResult($argResults, $arg->value)->getTypeOnScope($scope, false);
					if (!$argType->isObject()->no()) {
						$nakedReturnType = null;
						if ($nakedMethodReflection !== null) {
							$nakedParametersAcceptor = $this->selectArgsAcceptor(
								$gatheredTypes,
								$nakedMethodReflection->getVariants(),
								$nakedMethodReflection->getNamedArgumentsVariants(),
								$gatheredHasName,
								$gatheredUnpack,
							);
							$nakedReturnType = $nakedParametersAcceptor->getReturnType();
						}
						if (
							$nakedReturnType === null
							|| !(new ThisType($nakedMethodReflection->getDeclaringClass()))->isSuperTypeOf($nakedReturnType)->yes()
							|| $nakedMethodReflection->isPure()->no()
						) {
							$nodeScopeResolver->callNodeCallback($nodeCallback, new InvalidateExprNode($arg->value), $scope, $storage);
							$scope = $scope->invalidateExpression($arg->value, true);
						}
					} elseif (!(new ResourceType())->isSuperTypeOf($argType)->no()) {
						$nodeScopeResolver->callNodeCallback($nodeCallback, new InvalidateExprNode($arg->value), $scope, $storage);
						$scope = $scope->invalidateExpression($arg->value, true);
					}
				}
			}
		}

		// not storing this, it's scope after processing all args
		return new ArgsResult(
			$this->expressionResultFactory->create(
				$scope,
				$scope,
				$callLike,
				$hasYield,
				$isAlwaysTerminating,
				$throwPoints,
				$impurePoints,
				typeCallback: static fn () => new MixedType(),
				specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
			),
			$resolvedAcceptor,
			$argResults,
			$byRefArguments,
		);
	}

	/**
	 * Ports the gather-keying of ParametersAcceptorSelector::selectFromArgs():
	 * indexes the gathered arg type by name (sets $hasName) vs position, and
	 * expands unpacked constant arrays / falls back to the iterable value type
	 * (sets $unpack), so selectFromTypes() picks the matching variant.
	 *
	 * @param array<int|string, Type> $types
	 */
	private function addGatheredArgType(array &$types, bool &$unpack, bool &$hasName, Node\Arg $originalArg, int $i, Type $type): void
	{
		if ($originalArg->name !== null) {
			$index = $originalArg->name->toString();
			$hasName = true;
		} else {
			$index = $i;
		}

		if ($originalArg->unpack) {
			$unpack = true;
			$constantArrays = $type->getConstantArrays();
			if (count($constantArrays) > 0) {
				foreach ($constantArrays as $constantArray) {
					$values = $constantArray->getValueTypes();
					foreach ($constantArray->getKeyTypes() as $j => $keyType) {
						$valueType = $values[$j];
						$valueIndex = $keyType->getValue();
						if (is_string($valueIndex)) {
							$hasName = true;
						} else {
							$valueIndex = $i + $j;
						}

						$types[$valueIndex] = isset($types[$valueIndex])
							? TypeCombinator::union($types[$valueIndex], $valueType)
							: $valueType;
					}
				}
			} else {
				$types[$index] = $type->getIterableValueType();
			}
		} else {
			$types[$index] = $type;
		}
	}

	/**
	 * Whether processing this argument consumes the generic-RESOLVED parameter
	 * type: a closure/arrow function does - its parameters and body scope are
	 * typed from the resolved callable(T) - whether it IS the argument or is
	 * nested anywhere inside it (the enclosing parameter is pushed on the
	 * in-function-call stack and the nested closure types itself from there).
	 * Every other argument only reads variant-stable facts off its parameter.
	 */
	private function argConsumesResolvedParameterType(Expr $value): bool
	{
		if ($value instanceof Expr\Closure || $value instanceof Expr\ArrowFunction) {
			return true;
		}

		// cached on the node - args are re-processed across convergence passes
		$cached = $value->getAttribute('phpstanArgContainsClosure');
		if ($cached !== null) {
			return $cached;
		}

		$contains = (new NodeFinder())->findFirst(
			[$value],
			static fn (Node $node): bool => $node instanceof Expr\Closure || $node instanceof Expr\ArrowFunction,
		) !== null;
		$value->setAttribute('phpstanArgContainsClosure', $contains);

		return $contains;
	}

	/**
	 * Resolves the type of a closure/arrow function argument for the generic
	 * gather, mirroring ParametersAcceptorSelector::selectFromArgs(): the closure
	 * type is read with the RAW (un-generic-resolved) acceptor parameter pushed
	 * onto the in-function-call stack, so its body sees the template parameter
	 * (effectively mixed for an untyped param) rather than a parameter already
	 * resolved from sibling args. That keeps the inferred return type (the U in
	 * callable(T): U) faithful to the closure's own declaration.
	 *
	 * @param ParametersAcceptor[] $parametersAcceptors
	 */
	private function gatherClosureArgType(array $parametersAcceptors, int $i, Expr $closureExpr, MutatingScope $scope): Type
	{
		$rawParameter = null;
		if (count($parametersAcceptors) === 1) {
			$rawParameters = $parametersAcceptors[0]->getParameters();
			if (isset($rawParameters[$i])) {
				$rawParameter = $rawParameters[$i];
			} elseif (count($rawParameters) > 0 && $parametersAcceptors[0]->isVariadic()) {
				$rawParameter = array_last($rawParameters);
			}
		}

		if ($rawParameter !== null) {
			$scope = $scope->pushInFunctionCall(null, $rawParameter, false);
		}

		return $this->closureParameterResolver->resolveCallableTypeForScope($closureExpr, $scope);
	}

	/**
	 * A structural stand-in for an array literal argument that holds closures,
	 * built without walking anything: a nested array literal recurses, a closure /
	 * arrow function contributes its DECLARED signature
	 * (ClosureTypeResolver::getDeclaredClosureType()), and every other key/value
	 * is priced by the scope state it is already tracked as, falling back to the
	 * constant-expression resolver (literals, ::class, constants, concatenation)
	 * and ultimately to mixed. Widening a slot to mixed is safe: a template is
	 * never resolved below its bound, so the skeleton can only ever sharpen the
	 * resolution.
	 *
	 * Unlike gatherClosureArgType() this type never reaches $gatheredTypes: it
	 * exists solely to resolve the parameter type the nested closures are typed
	 * from. The argument's real type replaces it once the walk is done.
	 */
	private function gatherArrayArgTypeSkeleton(NodeScopeResolver $nodeScopeResolver, Expr\Array_ $expr, MutatingScope $scope): Type
	{
		$initializerContext = InitializerExprContext::fromScope($scope);
		$getType = function (Expr $inner) use (&$getType, $nodeScopeResolver, $scope, $initializerContext): Type {
			if ($inner instanceof Expr\Closure || $inner instanceof Expr\ArrowFunction) {
				return $this->closureTypeResolver->getDeclaredClosureType($scope, $inner);
			}

			if ($inner instanceof Expr\Array_) {
				return $this->initializerExprTypeResolver->getArrayType($inner, $getType);
			}

			return $nodeScopeResolver->findScopeStateType($inner, $scope)
				?? $this->initializerExprTypeResolver->getType($inner, $initializerContext);
		};

		return $this->initializerExprTypeResolver->getArrayType($expr, $getType);
	}

	/**
	 * @param array<int|string, Type> $types
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 */
	private function selectArgsAcceptor(array $types, array $parametersAcceptors, ?array $namedArgumentsVariants, bool $hasName, bool $unpack): ParametersAcceptor
	{
		return $hasName && $namedArgumentsVariants !== null
			? ParametersAcceptorSelector::selectFromTypes($types, $namedArgumentsVariants, $unpack)
			: ParametersAcceptorSelector::selectFromTypes($types, $parametersAcceptors, $unpack);
	}

	/**
	 * Applies the intrinsic argument overrides (array_map/filter/walk/find,
	 * curl_setopt, implode, Closure::bind) on the arg-to-arg evolved scope via
	 * the non-reprocessing readers, then type-selects the metadata acceptor over
	 * the arg types gathered so far. The overrides read sibling arg types - which
	 * closures-last ordering keeps in scope/$gatheredTypes before any closure.
	 *
	 * @param Node\Arg[] $args
	 * @param array<int|string, Type> $gatheredTypes
	 * @param ParametersAcceptor[] $parametersAcceptors
	 * @param ParametersAcceptor[]|null $namedArgumentsVariants
	 */
	private function selectArgsMetadataAcceptor(NodeScopeResolver $nodeScopeResolver, array $args, array $gatheredTypes, array $parametersAcceptors, ?array $namedArgumentsVariants, bool $hasName, bool $unpack, MutatingScope $scope): ParametersAcceptor
	{
		$overridden = ParametersAcceptorSelector::applyIntrinsicArgOverrides(
			$args,
			$parametersAcceptors,
			$namedArgumentsVariants,
			$scope,
			static fn (Expr $e): Type => $nodeScopeResolver->readTypeOfMaybeStored($e, $scope),
			static fn (Expr $e): Type => $nodeScopeResolver->readTypeOfMaybeStored($e, $scope->doNotTreatPhpDocTypesAsCertain()),
			static fn (Type $t): Type => $scope->getIterableValueType($t),
			static fn (Type $t): Type => $scope->getIterableKeyType($t),
		);

		return $this->selectArgsAcceptor($gatheredTypes, $overridden, $namedArgumentsVariants, $hasName, $unpack);
	}

	/**
	 * Arguments normalization (reordering, default-filling) can drop an original
	 * argument from the call processArgs() iterates - duplicate, unknown-named or
	 * extra arguments in an invalid call. The parameters check still asks their
	 * types to report the error, so process them too (their result is stored).
	 * A NoopNodeCallback keeps the dropped arguments out of rule processing,
	 * matching the behaviour when this guard is off.
	 */
	public function processDroppedArgs(
		NodeScopeResolver $nodeScopeResolver,
		Node\Stmt $stmt,
		CallLike $originalCall,
		CallLike $normalizedCall,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		ExpressionContext $context,
	): void
	{
		if ($originalCall === $normalizedCall) {
			return;
		}

		$keptValueIds = [];
		foreach ($normalizedCall->getArgs() as $normalizedArg) {
			$keptValueIds[spl_object_id($normalizedArg->value)] = true;
		}

		foreach ($originalCall->getArgs() as $originalArg) {
			if (isset($keptValueIds[spl_object_id($originalArg->value)])) {
				continue;
			}

			$nodeScopeResolver->processExprNode($stmt, $originalArg->value, $scope, $storage, new NoopNodeCallback(), $context->enterDeep()->withoutTemplateArgumentResolution());
		}
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function callCallbackImmediately(?ParameterReflection $parameter, ?Type $parameterType, $calleeReflection): bool
	{
		$parameterCallableType = null;
		if ($parameterType !== null && $calleeReflection instanceof FunctionReflection) {
			$parameterCallableType = TypeUtils::findCallableType($parameterType);
		}

		if ($parameter instanceof ExtendedParameterReflection) {
			$parameterCallImmediately = $parameter->isImmediatelyInvokedCallable();
			if ($parameterCallImmediately->maybe()) {
				$callCallbackImmediately = $parameterCallableType !== null;
			} else {
				$callCallbackImmediately = $parameterCallImmediately->yes();
			}
		} else {
			$callCallbackImmediately = $parameterCallableType !== null;
		}

		return $callCallbackImmediately;
	}

	/**
	 * A callback passed as an argument escapes the current scope and may be invoked,
	 * so its mutations have to invalidate the outer scope - unless the parameter is
	 * explicitly marked as later-invoked, in which case the callback only runs after
	 * the current function returns and its mutations are not visible here yet.
	 */
	private function shouldInvalidateCallbackExpressions(?ParameterReflection $parameter): bool
	{
		if ($parameter instanceof ExtendedParameterReflection) {
			return !$parameter->isImmediatelyInvokedCallable()->no();
		}

		return true;
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function getParameterTypeFromParameterClosureTypeExtension(CallLike $callLike, $calleeReflection, ParameterReflection $parameter, MutatingScope $scope): ?Type
	{
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterClosureTypeExtensions->getAll() as $functionParameterClosureTypeExtension) {
				if ($functionParameterClosureTypeExtension->isFunctionSupported($calleeReflection, $parameter)) {
					return $functionParameterClosureTypeExtension->getTypeFromFunctionCall($calleeReflection, $callLike, $parameter, $scope);
				}
			}
		} elseif ($calleeReflection instanceof MethodReflection) {
			if ($callLike instanceof StaticCall) {
				foreach ($this->staticMethodParameterClosureTypeExtensions->getAll() as $staticMethodParameterClosureTypeExtension) {
					if ($staticMethodParameterClosureTypeExtension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $staticMethodParameterClosureTypeExtension->getTypeFromStaticMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof New_ && $callLike->class instanceof Name) {
				$staticCall = new StaticCall(
					$callLike->class,
					new Identifier('__construct'),
					$callLike->getArgs(),
				);
				foreach ($this->staticMethodParameterClosureTypeExtensions->getAll() as $staticMethodParameterClosureTypeExtension) {
					if ($staticMethodParameterClosureTypeExtension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $staticMethodParameterClosureTypeExtension->getTypeFromStaticMethodCall($calleeReflection, $staticCall, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof MethodCall) {
				foreach ($this->methodParameterClosureTypeExtensions->getAll() as $methodParameterClosureTypeExtension) {
					if ($methodParameterClosureTypeExtension->isMethodSupported($calleeReflection, $parameter)) {
						return $methodParameterClosureTypeExtension->getTypeFromMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	private function getParameterOutExtensionsType(CallLike $callLike, $calleeReflection, ParameterReflection $currentParameter, MutatingScope $scope): ?Type
	{
		$paramOutTypes = [];
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterOutTypeExtensions->getAll() as $functionParameterOutTypeExtension) {
				if (!$functionParameterOutTypeExtension->isFunctionSupported($calleeReflection, $currentParameter)) {
					continue;
				}

				$resolvedType = $functionParameterOutTypeExtension->getParameterOutTypeFromFunctionCall($calleeReflection, $callLike, $currentParameter, $scope);
				if ($resolvedType === null) {
					continue;
				}
				$paramOutTypes[] = $resolvedType;
			}
		} elseif ($callLike instanceof MethodCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->methodParameterOutTypeExtensions->getAll() as $methodParameterOutTypeExtension) {
				if (!$methodParameterOutTypeExtension->isMethodSupported($calleeReflection, $currentParameter)) {
					continue;
				}

				$resolvedType = $methodParameterOutTypeExtension->getParameterOutTypeFromMethodCall($calleeReflection, $callLike, $currentParameter, $scope);
				if ($resolvedType === null) {
					continue;
				}
				$paramOutTypes[] = $resolvedType;
			}
		} elseif ($callLike instanceof StaticCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->staticMethodParameterOutTypeExtensions->getAll() as $staticMethodParameterOutTypeExtension) {
				if (!$staticMethodParameterOutTypeExtension->isStaticMethodSupported($calleeReflection, $currentParameter)) {
					continue;
				}

				$resolvedType = $staticMethodParameterOutTypeExtension->getParameterOutTypeFromStaticMethodCall($calleeReflection, $callLike, $currentParameter, $scope);
				if ($resolvedType === null) {
					continue;
				}
				$paramOutTypes[] = $resolvedType;
			}
		}

		if (count($paramOutTypes) === 1) {
			return $paramOutTypes[0];
		}

		if (count($paramOutTypes) > 1) {
			return TypeCombinator::union(...$paramOutTypes);
		}

		return null;
	}

	/**
	 * @param FunctionReflection|MethodReflection|null $calleeReflection
	 */
	private function resolveClosureThisType(
		?CallLike $call,
		$calleeReflection,
		ParameterReflection $parameter,
		MutatingScope $scope,
	): ?Type
	{
		if ($call instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterClosureThisExtensions->getAll() as $extension) {
				if (! $extension->isFunctionSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromFunctionCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		} elseif ($call instanceof StaticCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->staticMethodParameterClosureThisExtensions->getAll() as $extension) {
				if (! $extension->isStaticMethodSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromStaticMethodCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		} elseif ($call instanceof MethodCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->methodParameterClosureThisExtensions->getAll() as $extension) {
				if (! $extension->isMethodSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getClosureThisTypeFromMethodCall($calleeReflection, $call, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}
		}

		if ($parameter instanceof ExtendedParameterReflection) {
			return $parameter->getClosureThisType();
		}

		return null;
	}

	/**
	 * The parameter type an argument is observed against: the declared one with
	 * the template types the call already decided substituted - the receiver's
	 * class-level arguments, a template an earlier argument inferred - while a
	 * template still open (ErrorType in the resolved map, typically the one this
	 * very argument decides) stays a TemplateType, which the observer ignores.
	 * The resolved parameter type would carry such a template's bound instead.
	 */
	private function findOriginalParameterType(ParametersAcceptor $acceptor, ParameterReflection $parameter): ?Type
	{
		if (!$acceptor instanceof ResolvedFunctionVariant) {
			return $parameter->getType();
		}
		$originalParameters = $acceptor->getOriginalParametersAcceptor()->getParameters();
		foreach ($acceptor->getParameters() as $index => $resolvedParameter) {
			if ($resolvedParameter !== $parameter) {
				continue;
			}
			if (!isset($originalParameters[$index])) {
				return null;
			}
			$originalType = $originalParameters[$index]->getType();
			if (!$originalType->hasTemplateOrLateResolvableType()) {
				return $originalType;
			}
			$decided = [];
			foreach ($acceptor->getResolvedTemplateTypeMap()->getTypes() as $name => $type) {
				if ($type instanceof ErrorType) {
					continue;
				}
				$decided[$name] = $type;
			}

			return TemplateTypeHelper::resolveTemplateTypes(
				$originalType,
				new TemplateTypeMap($decided),
				$acceptor->getCallSiteVarianceMap(),
				TemplateTypeVariance::createContravariant(),
			);
		}

		return null;
	}

	/**
	 * The result processArgs() captured for a call argument. Unlike the storage
	 * lookup this reads the argument's authoritative result: a closure/arrow
	 * function argument's captured result is the properly-typed one, not the
	 * placeholder its body walk stored.
	 *
	 * @param array<int, ExpressionResult> $argResults
	 */
	private function readArgResult(array $argResults, Expr $argValue): ExpressionResult
	{
		$result = $argResults[spl_object_id($argValue)] ?? null;
		if ($result === null) {
			throw new ShouldNotHappenException(sprintf(
				'%s on line %d has no captured ExpressionResult - it was not processed as an argument by processArgs().',
				get_class($argValue),
				$argValue->getStartLine(),
			));
		}

		return $result;
	}

}
