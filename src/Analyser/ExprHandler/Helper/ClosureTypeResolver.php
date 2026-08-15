<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PerFileAnalysisResettable;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\StatementContext;
use PHPStan\Analyser\ThrowPoint;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Parser\ImmediatelyInvokedClosureVisitor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NonAcceptingNeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_string;
use function spl_object_id;

#[AutowiredService]
final class ClosureTypeResolver implements PerFileAnalysisResettable
{

	private static int $resolveClosureTypeDepth = 0;

	/**
	 * Per-context resolved closure types, keyed by the closure node. Node
	 * attributes would persist on the parser cache's retained ASTs after the
	 * file's analysis ends - the per-file reset releases the Types and
	 * throw/impure points with the rest of the file's result graph.
	 *
	 * Keyed by the closure node's spl_object_id() - see
	 * TernaryHandler::$capturedResults for the lifetime/collision reasoning.
	 *
	 * @var array<int, array<string, array{returnType: Type, throwPoints: SimpleThrowPoint[], impurePoints: SimpleImpurePoint[], invalidateExpressions: InvalidateExprNode[], usedVariables: string[]}>>
	 */
	private array $cachedTypes = [];

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function resetFileAnalysisState(): void
	{
		$this->cachedTypes = [];
	}

	/**
	 * Resolves a closure/arrow function type by walking its body itself. Used by
	 * the paths that have NO prior walk to read return/yield types from - a
	 * closure asking its own type before its result is stored, and
	 * resolveCallableTypeForScope(). A self-by-ref closure legitimately re-walks
	 * here (the $resolveClosureTypeDepth guard answers that ask).
	 *
	 * Callers that have already walked the body (the closure/arrow handlers and
	 * the closure-as-call-arg store sites) feed the gathered returns/yields to
	 * buildClosureType() instead, which constructs the same ClosureType without
	 * a second walk.
	 */
	public function getClosureType(
		MutatingScope $scope,
		Node\Expr\Closure|ArrowFunction $expr,
		bool $shallow = false,
	): ClosureType
	{
		[$parameters, $isVariadic, $callableParameters, $nativeCallableParameters] = $this->buildParametersAndAcceptors($scope, $expr);

		// A shallow reflection is the closure/arrow function's signature without
		// walking its body: parameters plus the DECLARED return type. Used at scope
		// ENTRY (enterAnonymousFunction()/enterArrowFunction()) so entering a
		// closure/arrow scope never re-walks the body - the refined return type is
		// built afterwards from the single body walk's gathered returns and carried
		// on the node/rule scope (see NodeScopeResolver::processClosureNodeInternal()
		// and processArrowFunctionNode()).
		if ($shallow) {
			return new ClosureType(
				$parameters,
				$scope->getFunctionType($expr->returnType, false, false),
				$isVariadic,
				isStatic: TrinaryLogic::createFromBoolean($expr->static),
			);
		}

		$cachedTypes = $this->cachedTypes[spl_object_id($expr)] ?? [];
		$cacheKey = $this->closureContextCacheKey($scope, $expr, $callableParameters, $parameters);
		if (array_key_exists($cacheKey, $cachedTypes)) {
			return $this->createClosureTypeFromCache($expr, $parameters, $isVariadic, $cachedTypes[$cacheKey]);
		}
		if (self::$resolveClosureTypeDepth >= 2) {
			return new ClosureType(
				$parameters,
				$scope->getFunctionType($expr->returnType, false, false),
				$isVariadic,
				isStatic: TrinaryLogic::createFromBoolean($expr->static),
			);
		}

		if ($expr instanceof ArrowFunction) {
			$arrowScope = $scope->enterArrowFunctionWithoutReflection($expr, $callableParameters, $nativeCallableParameters);

			$arrowFunctionImpurePoints = [];
			$invalidateExpressions = [];
			self::$resolveClosureTypeDepth++;
			try {
				$arrowFunctionExprResult = $this->nodeScopeResolver->processExprNode(
					new Node\Stmt\Expression($expr->expr),
					$expr->expr,
					$arrowScope,
					new ExpressionResultStorage(),
					static function (Node $node, Scope $scope) use ($arrowScope, &$arrowFunctionImpurePoints, &$invalidateExpressions): void {
						if ($scope->getAnonymousFunctionReflection() !== $arrowScope->getAnonymousFunctionReflection()) {
							return;
						}

						if ($node instanceof InvalidateExprNode) {
							$invalidateExpressions[] = $node;
							return;
						}

						if (!$node instanceof PropertyAssignNode) {
							return;
						}

						$arrowFunctionImpurePoints[] = new ImpurePoint(
							$scope,
							$node,
							'propertyAssign',
							'property assignment',
							true,
						);
						$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
					},
					ExpressionContext::createDeep(),
				);
			} finally {
				self::$resolveClosureTypeDepth--;
			}
			$throwPoints = array_map(static fn ($throwPoint) => $throwPoint->toPublic(), $arrowFunctionExprResult->getThrowPoints());
			$impurePoints = array_merge($arrowFunctionImpurePoints, $arrowFunctionExprResult->getImpurePoints());

			// the body was processed just above; resolve the return type from its stored
			// result rather than reading the still-unprocessed body expression
			$returnType = $this->resolveArrowFunctionReturnType($scope, $arrowScope, $expr);

			return $this->assembleClosureType($scope, $expr, $parameters, $isVariadic, $returnType, $throwPoints, $impurePoints, $invalidateExpressions, [], $cacheKey);
		}

		self::$resolveClosureTypeDepth++;

		$closureScope = $scope->enterAnonymousFunctionWithoutReflection($expr, $callableParameters, $nativeCallableParameters);
		$closureReturnStatements = [];
		$closureYieldStatements = [];
		$closureExecutionEnds = [];
		$closureImpurePoints = [];
		$invalidateExpressions = [];

		try {
			$closureStatementResult = $this->nodeScopeResolver->processStmtNodes($expr, $expr->stmts, $closureScope, static function (Node $node, Scope $scope) use ($closureScope, &$closureReturnStatements, &$closureYieldStatements, &$closureExecutionEnds, &$closureImpurePoints, &$invalidateExpressions): void {
				if ($scope->getAnonymousFunctionReflection() !== $closureScope->getAnonymousFunctionReflection()) {
					return;
				}

				if ($node instanceof InvalidateExprNode) {
					$invalidateExpressions[] = $node;
					return;
				}

				if ($node instanceof PropertyAssignNode) {
					$closureImpurePoints[] = new ImpurePoint(
						$scope,
						$node,
						'propertyAssign',
						'property assignment',
						true,
					);
					$invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
					return;
				}

				if ($node instanceof ExecutionEndNode) {
					$closureExecutionEnds[] = $node;
					return;
				}

				if ($node instanceof Node\Stmt\Return_) {
					$closureReturnStatements[] = [$node, $scope];
				}

				if (!$node instanceof Yield_ && !$node instanceof YieldFrom) {
					return;
				}

				$closureYieldStatements[] = [$node, $scope];
			}, StatementContext::createTopLevel());
		} finally {
			self::$resolveClosureTypeDepth--;
		}

		$throwPoints = $closureStatementResult->getThrowPoints();
		$impurePoints = array_merge($closureImpurePoints, $closureStatementResult->getImpurePoints());

		return $this->buildClosureTypeFromClosureWalk(
			$scope,
			$expr,
			$parameters,
			$isVariadic,
			$closureReturnStatements,
			$closureYieldStatements,
			$closureExecutionEnds,
			$throwPoints,
			$impurePoints,
			$invalidateExpressions,
			$cacheKey,
		);
	}

	/**
	 * Constructs a closure type from data the engine already gathered while
	 * walking the body once (see NodeScopeResolver::processClosureNode()),
	 * without a second walk. The return/yield expression types are read from
	 * their stored results.
	 *
	 * @param list<array{Node\Stmt\Return_, Scope}> $returnStatements
	 * @param list<array{Yield_|YieldFrom, Scope}> $yieldStatements
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param InternalThrowPoint[] $throwPoints the single body walk's internal throw points
	 * @param ImpurePoint[] $impurePoints already merged (property-assign impure points + statement result impure points)
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	public function buildClosureTypeForClosure(
		MutatingScope $scope,
		Node\Expr\Closure $expr,
		array $returnStatements,
		array $yieldStatements,
		array $executionEnds,
		array $throwPoints,
		array $impurePoints,
		array $invalidateExpressions,
		bool $native = false,
	): ClosureType
	{
		if ($this->bodyWalkHasOwnParameterTypes($expr)) {
			return $this->getClosureType($native ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope, $expr);
		}

		[$parameters, $isVariadic, $callableParameters, $nativeCallableParameters] = $this->buildParametersAndAcceptors($scope, $expr);

		return $this->buildClosureTypeFromClosureWalk(
			$scope,
			$expr,
			$parameters,
			$isVariadic,
			$returnStatements,
			$yieldStatements,
			$executionEnds,
			array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->toPublic(), $throwPoints),
			$impurePoints,
			$invalidateExpressions,
			// the flavour-correct key both keeps the native build from clobbering
			// the phpdoc cache slot and lets a later getClosureType() ask on the
			// promoted scope answer from this build instead of re-walking
			$this->closureContextCacheKey(
				$native ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope,
				$expr,
				$native ? $nativeCallableParameters : $callableParameters,
				$parameters,
			),
			$native,
		);
	}

	/**
	 * Constructs an arrow function type from data the engine already gathered
	 * while walking the body once (see NodeScopeResolver::
	 * processArrowFunctionNode()), without a second walk. The return/yield
	 * expression types are read from their stored results on $arrowScope.
	 *
	 * @param ThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints already merged (property-assign impure points + expression result impure points)
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	public function buildClosureTypeForArrowFunction(
		MutatingScope $scope,
		ArrowFunction $expr,
		MutatingScope $arrowScope,
		array $throwPoints,
		array $impurePoints,
		array $invalidateExpressions,
		bool $native = false,
	): ClosureType
	{
		if ($this->bodyWalkHasOwnParameterTypes($expr)) {
			return $this->getClosureType($native ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope, $expr);
		}

		[$parameters, $isVariadic, $callableParameters, $nativeCallableParameters] = $this->buildParametersAndAcceptors($scope, $expr);

		$returnType = $this->resolveArrowFunctionReturnType($scope, $arrowScope, $expr, $native);

		// the flavour-correct key both keeps the native build from clobbering the
		// phpdoc cache slot and lets a later getClosureType() ask on the promoted
		// scope answer from this build instead of re-walking
		return $this->assembleClosureType($scope, $expr, $parameters, $isVariadic, $returnType, $throwPoints, $impurePoints, $invalidateExpressions, [], $this->closureContextCacheKey(
			$native ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope,
			$expr,
			$native ? $nativeCallableParameters : $callableParameters,
			$parameters,
		));
	}

	/**
	 * Whether getClosureType() would walk the body with different parameter types
	 * than NodeScopeResolver's single walk (processClosureNode()/
	 * processArrowFunctionNode()) did. array_map() callbacks and immediately
	 * invoked closures get their parameter types from the array element type /
	 * the invocation arguments in getClosureType(), whereas the single walk types
	 * them from the closure's passed-to callable type - so the return type read
	 * from the gathered scopes would differ, and getClosureType() must re-walk.
	 */
	/**
	 * The expression roots this closure's type can read from the enclosing
	 * scope: '$this' and the use()d variables for closures, '$this' and
	 * every body variable that is not a parameter for arrow functions. Null
	 * when the body accesses variables dynamically ($$name, compact(),
	 * get_defined_vars()) and the whole scope must key the cache.
	 *
	 * @return list<string>|null
	 */
	/**
	 * The cache key of everything this closure's type can depend on: the
	 * free-variable slice of the scope plus the parameter types the caller
	 * feeds in - array_map style callers type the same closure node per
	 * element through the callable parameters.
	 *
	 * @param array<ParameterReflection>|null $callableParameters
	 * @param array<ParameterReflection> $parameters
	 */
	private function closureContextCacheKey(MutatingScope $scope, Node\Expr\Closure|ArrowFunction $expr, ?array $callableParameters, array $parameters): string
	{
		$parts = [];
		foreach ($callableParameters ?? $parameters as $parameter) {
			$parts[] = $parameter->getType()->describe(VerbosityLevel::cache());
		}

		return $scope->getClosureScopeCacheKey($this->freeVariableRoots($expr)) . '/' . implode('|', $parts) . ($scope->nativeTypesPromoted ? '/native' : '/phpdoc');
	}

	/**
	 * The expression roots this closure's type can read from the enclosing
	 * scope - null when the body accesses variables dynamically and the
	 * whole scope must key the cache.
	 *
	 * @return list<string>|null
	 */
	private function freeVariableRoots(Node\Expr\Closure|ArrowFunction $expr): ?array
	{
		/** @var list<string>|false|null $cached */
		$cached = $expr->getAttribute('phpstanFreeVariableRoots', false);
		if ($cached !== false) {
			return $cached;
		}

		$roots = [];
		if (!$expr->static) {
			$roots['$this'] = true;
		}

		if ($expr instanceof Node\Expr\Closure) {
			foreach ($expr->uses as $use) {
				if (!is_string($use->var->name)) {
					$expr->setAttribute('phpstanFreeVariableRoots', null);
					return null;
				}
				$roots['$' . $use->var->name] = true;
			}
		} else {
			$paramNames = [];
			foreach ($expr->params as $param) {
				if (!($param->var instanceof Node\Expr\Variable) || !is_string($param->var->name)) {
					continue;
				}

				$paramNames['$' . $param->var->name] = true;
			}
			$finder = new NodeFinder();
			foreach ($finder->findInstanceOf([$expr->expr], Node\Expr\Variable::class) as $variable) {
				if (!is_string($variable->name)) {
					$expr->setAttribute('phpstanFreeVariableRoots', null);
					return null;
				}
				$name = '$' . $variable->name;
				if (isset($paramNames[$name])) {
					continue;
				}
				$roots[$name] = true;
			}
			foreach ($finder->findInstanceOf([$expr->expr], Node\Expr\FuncCall::class) as $call) {
				if ($call->name instanceof Node\Name && in_array($call->name->toLowerString(), ['compact', 'extract', 'get_defined_vars'], true)) {
					$expr->setAttribute('phpstanFreeVariableRoots', null);
					return null;
				}
			}
		}

		$rootList = array_keys($roots);
		$expr->setAttribute('phpstanFreeVariableRoots', $rootList);

		return $rootList;
	}

	private function bodyWalkHasOwnParameterTypes(Node\Expr\Closure|ArrowFunction $expr): bool
	{
		return $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME) !== null
			|| $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME) !== null;
	}

	/**
	 * @param list<NativeParameterReflection> $parameters
	 * @param list<array{Node\Stmt\Return_, Scope}> $returnStatements
	 * @param list<array{Yield_|YieldFrom, Scope}> $yieldStatements
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param ThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	private function buildClosureTypeFromClosureWalk(
		MutatingScope $scope,
		Node\Expr\Closure $expr,
		array $parameters,
		bool $isVariadic,
		array $returnStatements,
		array $yieldStatements,
		array $executionEnds,
		array $throwPoints,
		array $impurePoints,
		array $invalidateExpressions,
		?string $cacheKey = null,
		bool $native = false,
	): ClosureType
	{
		$onlyNeverExecutionEnds = $this->deriveOnlyNeverExecutionEnds($executionEnds);

		// like resolveArrowFunctionReturnType(): the single walk stored both
		// flavours on the gathered scopes, so the native flavour just reads the
		// stored native types - no second body walk on the promoted scope
		$returnTypes = [];
		$hasNull = false;
		foreach ($returnStatements as [$returnNode, $returnScope]) {
			if ($returnNode->expr === null) {
				$hasNull = true;
				continue;
			}

			$readScope = $returnScope->toWalkScope();
			if ($native) {
				$readScope = $readScope->doNotTreatPhpDocTypesAsCertain();
			}
			$returnTypes[] = $readScope->getType($returnNode->expr);
		}

		if (count($returnTypes) === 0) {
			if ($onlyNeverExecutionEnds === true && !$hasNull) {
				$returnType = new NonAcceptingNeverType();
			} else {
				$returnType = new VoidType();
			}
		} else {
			if ($onlyNeverExecutionEnds === true) {
				$returnTypes[] = new NonAcceptingNeverType();
			}
			if ($hasNull) {
				$returnTypes[] = new NullType();
			}
			$returnType = TypeCombinator::union(...$returnTypes);
		}

		if (count($yieldStatements) > 0) {
			$keyTypes = [];
			$valueTypes = [];
			foreach ($yieldStatements as [$yieldNode, $yieldScope]) {
				$readScope = $yieldScope->toWalkScope();
				if ($native) {
					$readScope = $readScope->doNotTreatPhpDocTypesAsCertain();
				}
				if ($yieldNode instanceof Yield_) {
					if ($yieldNode->key === null) {
						$keyTypes[] = new IntegerType();
					} else {
						$keyTypes[] = $readScope->getType($yieldNode->key);
					}

					if ($yieldNode->value === null) {
						$valueTypes[] = new NullType();
					} else {
						$valueTypes[] = $readScope->getType($yieldNode->value);
					}

					continue;
				}

				$yieldFromType = $readScope->getType($yieldNode->expr);
				$keyTypes[] = $readScope->getIterableKeyType($yieldFromType);
				$valueTypes[] = $readScope->getIterableValueType($yieldFromType);
			}

			$returnType = new GenericObjectType(Generator::class, [
				TypeCombinator::union(...$keyTypes),
				TypeCombinator::union(...$valueTypes),
				new MixedType(),
				$returnType,
			]);
		} else {
			if ($expr->returnType !== null) {
				$nativeReturnType = $scope->getFunctionType($expr->returnType, false, false);
				$returnType = MutatingScope::intersectButNotNever($nativeReturnType, $returnType);
			}
		}

		$usedVariables = [];
		foreach ($expr->uses as $use) {
			if (!is_string($use->var->name)) {
				continue;
			}

			$usedVariables[] = $use->var->name;
		}

		foreach ($expr->uses as $use) {
			if (!$use->byRef) {
				continue;
			}

			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'functionCall',
				'call to a Closure with by-ref use',
				true,
			);
			break;
		}

		return $this->assembleClosureType($scope, $expr, $parameters, $isVariadic, $returnType, $throwPoints, $impurePoints, $invalidateExpressions, $usedVariables, $cacheKey);
	}

	private function resolveArrowFunctionReturnType(
		MutatingScope $scope,
		MutatingScope $arrowScope,
		ArrowFunction $expr,
		bool $native = false,
	): Type
	{
		// Unlike a closure (whose native type equals its phpdoc type), an arrow
		// function's native return type is the body expression's native type
		// (e.g. fn () => methodReturningPositiveInt() is Closure(): int natively,
		// Closure(): int<1, max> in phpdoc). The body was already processed in the
		// single walk, so the native flavour just reads the stored native types off
		// the same arrowScope - no second walk.
		$readScope = $native ? $arrowScope->doNotTreatPhpDocTypesAsCertain() : $arrowScope;

		if ($expr->expr instanceof Yield_ || $expr->expr instanceof YieldFrom) {
			$yieldNode = $expr->expr;

			if ($yieldNode instanceof Yield_) {
				if ($yieldNode->key === null) {
					$keyType = new IntegerType();
				} else {
					$keyType = $readScope->getType($yieldNode->key);
				}

				if ($yieldNode->value === null) {
					$valueType = new NullType();
				} else {
					$valueType = $readScope->getType($yieldNode->value);
				}
			} else {
				$yieldFromType = $readScope->getType($yieldNode->expr);
				$keyType = $readScope->getIterableKeyType($yieldFromType);
				$valueType = $readScope->getIterableValueType($yieldFromType);
			}

			return new GenericObjectType(Generator::class, [
				$keyType,
				$valueType,
				new MixedType(),
				new VoidType(),
			]);
		}

		$returnType = $readScope->getKeepVoidType($expr->expr);
		if ($expr->returnType !== null) {
			$nativeReturnType = $scope->getFunctionType($expr->returnType, false, false);
			$returnType = MutatingScope::intersectButNotNever($nativeReturnType, $returnType);
		}

		return $returnType;
	}

	/**
	 * Whether every execution end of the closure body is a "never" terminator
	 * (throw/exit) rather than a return: null when there were no execution ends,
	 * false once a return (or non-terminating end) is seen.
	 *
	 * @param list<ExecutionEndNode> $executionEnds
	 */
	private function deriveOnlyNeverExecutionEnds(array $executionEnds): ?bool
	{
		$onlyNeverExecutionEnds = null;
		foreach ($executionEnds as $node) {
			if ($node->getStatementResult()->isAlwaysTerminating()) {
				foreach ($node->getStatementResult()->getExitPoints() as $exitPoint) {
					if ($exitPoint->getStatement() instanceof Node\Stmt\Return_) {
						$onlyNeverExecutionEnds = false;
						continue;
					}

					if ($onlyNeverExecutionEnds === null) {
						$onlyNeverExecutionEnds = true;
					}

					break;
				}

				if (count($node->getStatementResult()->getExitPoints()) === 0) {
					if ($onlyNeverExecutionEnds === null) {
						$onlyNeverExecutionEnds = true;
					}
				}
			} else {
				$onlyNeverExecutionEnds = false;
			}
		}

		return $onlyNeverExecutionEnds;
	}

	/**
	 * Builds the closure/arrow function's declared parameters (independent of the
	 * body walk) and the callable parameter acceptors derived from the call site.
	 *
	 * @return array{list<NativeParameterReflection>, bool, ParameterReflection[]|null, ParameterReflection[]|null}
	 */
	private function buildParametersAndAcceptors(
		MutatingScope $scope,
		Node\Expr\Closure|ArrowFunction $expr,
	): array
	{
		$parameters = [];
		$isVariadic = false;
		$firstOptionalParameterIndex = null;
		foreach ($expr->params as $i => $param) {
			$isOptionalCandidate = $param->default !== null || $param->variadic;

			if ($isOptionalCandidate) {
				if ($firstOptionalParameterIndex === null) {
					$firstOptionalParameterIndex = $i;
				}
			} else {
				$firstOptionalParameterIndex = null;
			}
		}

		foreach ($expr->params as $i => $param) {
			if ($param->variadic) {
				$isVariadic = true;
			}
			if (!$param->var instanceof Variable || !is_string($param->var->name)) {
				throw new ShouldNotHappenException();
			}
			$parameters[] = new NativeParameterReflection(
				$param->var->name,
				$firstOptionalParameterIndex !== null && $i >= $firstOptionalParameterIndex,
				$scope->getFunctionType($param->type, $scope->isParameterValueNullable($param), false),
				$param->byRef
					? PassedByReference::createCreatesNewVariable()
					: PassedByReference::createNo(),
				$param->variadic,
				// a default is a constant expression - price it without a scope
				// walk, the same way parameter defaults are priced elsewhere
				$param->default !== null ? $this->initializerExprTypeResolver->getType($param->default, InitializerExprContext::fromScope($scope)) : null,
			);
		}

		$callableParameters = null;
		$nativeCallableParameters = null;
		$arrayMapArgs = $expr->getAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME);
		$immediatelyInvokedArgs = $expr->getAttribute(ImmediatelyInvokedClosureVisitor::ARGS_ATTRIBUTE_NAME);
		if ($arrayMapArgs !== null) {
			$callableParameters = [];
			$nativeCallableParameters = [];
			foreach ($arrayMapArgs as $funcCallArg) {
				$callableParameters[] = new DummyParameter('item', $scope->getType($funcCallArg->value)->getIterableValueType(), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
				$nativeCallableParameters[] = new DummyParameter('item', $scope->getNativeType($funcCallArg->value)->getIterableValueType(), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
			}
		} elseif ($immediatelyInvokedArgs !== null) {
			foreach ($immediatelyInvokedArgs as $immediatelyInvokedArg) {
				$callableParameters[] = new DummyParameter('item', $scope->getType($immediatelyInvokedArg->value), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
				$nativeCallableParameters[] = new DummyParameter('item', $scope->getNativeType($immediatelyInvokedArg->value), optional: false, passedByReference: PassedByReference::createNo(), variadic: false, defaultValue: null);
			}
		} else {
			$inFunctionCallsStackCount = count($scope->inFunctionCallsStack);
			if ($inFunctionCallsStackCount > 0) {
				[, $inParameter] = $scope->inFunctionCallsStack[$inFunctionCallsStackCount - 1];
				if ($inParameter !== null) {
					$callableParameters = $this->nodeScopeResolver->createCallableParameters($scope, $expr, null, $inParameter->getType());
					$nativeType = $inParameter instanceof ExtendedParameterReflection ? $inParameter->getNativeType() : $inParameter->getType();
					$nativeCallableParameters = $this->nodeScopeResolver->createNativeCallableParameters($scope, $expr, null, $nativeType);
				}
			}
		}

		return [$parameters, $isVariadic, $callableParameters, $nativeCallableParameters];
	}

	/**
	 * @param list<NativeParameterReflection> $parameters
	 * @param array{returnType: Type, throwPoints: SimpleThrowPoint[], impurePoints: SimpleImpurePoint[], invalidateExpressions: InvalidateExprNode[], usedVariables: string[]} $cachedClosureData
	 */
	private function createClosureTypeFromCache(
		Node\Expr\Closure|ArrowFunction $expr,
		array $parameters,
		bool $isVariadic,
		array $cachedClosureData,
	): ClosureType
	{
		$mustUseReturnValue = TrinaryLogic::createNo();
		foreach ($expr->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'nodiscard') {
					$mustUseReturnValue = TrinaryLogic::createYes();
					break;
				}
			}
		}

		return new ClosureType(
			$parameters,
			$cachedClosureData['returnType'],
			$isVariadic,
			TemplateTypeMap::createEmpty(),
			TemplateTypeMap::createEmpty(),
			TemplateTypeVarianceMap::createEmpty(),
			throwPoints: $cachedClosureData['throwPoints'],
			impurePoints: $cachedClosureData['impurePoints'],
			invalidateExpressions: $cachedClosureData['invalidateExpressions'],
			usedVariables: $cachedClosureData['usedVariables'],
			acceptsNamedArguments: TrinaryLogic::createYes(),
			mustUseReturnValue: $mustUseReturnValue,
			isStatic: TrinaryLogic::createFromBoolean($expr->static),
		);
	}

	/**
	 * Constructs the final ClosureType from the resolved return type and the
	 * gathered throw/impure/invalidate points. Adds the by-ref-parameter impure
	 * point, populates the per-scope phpdoc-type cache (closures only), and
	 * resolves the #[NoDiscard] attribute.
	 *
	 * @param list<NativeParameterReflection> $parameters
	 * @param ThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 * @param string[] $usedVariables
	 */
	private function assembleClosureType(
		MutatingScope $scope,
		Node\Expr\Closure|ArrowFunction $expr,
		array $parameters,
		bool $isVariadic,
		Type $returnType,
		array $throwPoints,
		array $impurePoints,
		array $invalidateExpressions,
		array $usedVariables,
		?string $cacheKey = null,
	): ClosureType
	{
		foreach ($parameters as $parameter) {
			if ($parameter->passedByReference()->no()) {
				continue;
			}

			$impurePoints[] = new ImpurePoint(
				$scope,
				$expr,
				'functionCall',
				'call to a Closure with by-ref parameter',
				true,
			);
		}

		$throwPointsForClosureType = array_map(static fn (ThrowPoint $throwPoint) => $throwPoint->isExplicit() ? SimpleThrowPoint::createExplicit($throwPoint->getType(), $throwPoint->canContainAnyThrowable()) : SimpleThrowPoint::createImplicit(), $throwPoints);
		$impurePointsForClosureType = array_map(static fn (ImpurePoint $impurePoint) => new SimpleImpurePoint($impurePoint->getIdentifier(), $impurePoint->getDescription(), $impurePoint->isCertain()), $impurePoints);

		$cachedTypes = $this->cachedTypes[spl_object_id($expr)] ?? [];
		$cacheKey ??= $this->closureContextCacheKey($scope, $expr, null, $parameters);
		$cachedTypes[$cacheKey] = [
			'returnType' => $returnType,
			'throwPoints' => $throwPointsForClosureType,
			'impurePoints' => $impurePointsForClosureType,
			'invalidateExpressions' => $invalidateExpressions,
			'usedVariables' => $usedVariables,
		];
		$this->cachedTypes[spl_object_id($expr)] = $cachedTypes;

		$mustUseReturnValue = TrinaryLogic::createNo();
		foreach ($expr->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'nodiscard') {
					$mustUseReturnValue = TrinaryLogic::createYes();
					break;
				}
			}
		}

		return new ClosureType(
			$parameters,
			$returnType,
			$isVariadic,
			TemplateTypeMap::createEmpty(),
			TemplateTypeMap::createEmpty(),
			TemplateTypeVarianceMap::createEmpty(),
			throwPoints: $throwPointsForClosureType,
			impurePoints: $impurePointsForClosureType,
			invalidateExpressions: $invalidateExpressions,
			usedVariables: $usedVariables,
			acceptsNamedArguments: TrinaryLogic::createYes(),
			mustUseReturnValue: $mustUseReturnValue,
			isStatic: TrinaryLogic::createFromBoolean($expr->static),
		);
	}

}
