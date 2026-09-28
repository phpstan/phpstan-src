<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Goto_;
use PHPStan\Analyser\Generics\ClosureSignatureInference;
use PHPStan\Analyser\Generics\StaticVariableInference;
use PHPStan\Analyser\Generics\TemplateArgumentConstraints;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Analyser\Generics\TemplateArgumentObserver;
use PHPStan\Analyser\Generics\TemplateArgumentResolver;
use PHPStan\Analyser\Generics\TemplateArgumentStats;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\PropertyHookStatementNode;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Parser\GotoLabelVisitor;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_column;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_slice;
use function count;
use function getenv;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function min;
use function spl_object_id;
use function sprintf;

/**
 * Walks statement lists for NodeScopeResolver - goto convergence, unreachable
 * statements, the two-pass function-like body walk - and applies statement-level
 * PHPDocs (@var, @throws). A single statement is dispatched to its StmtHandler
 * by NodeScopeResolver::processStmtNode().
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/StatementsHandler.cpp')]
final class StatementsHandler
{

	/** The last walk inferring the `static` variables walked a statement: [kind, recording from, recording to] */
	private const STATIC_WALK_WALKED = 0;

	/** It carried a statement's recorded effect over: [kind, statement index, 0] */
	private const STATIC_WALK_CARRIED = 1;

	/** It converged with the observation pass at a statement - the rest stands: [kind, statement index, 0] */
	private const STATIC_WALK_REST = 2;

	private const MENTIONED_VARIABLES_ATTRIBUTE = 'templateArgumentMentionedVariables';

	/** PHPSTAN_TEMPLATE_ARGUMENTS_DEBUG=1 prints every second-pass re-walk/replay decision. */
	private bool $debugTemplateArguments;

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private TemplateArgumentObserver $templateArgumentObserver,
		private TemplateArgumentResolver $templateArgumentResolver,
		private Container $container,
		private StaticVariableInference $staticVariableInference,
		#[AutowiredParameter(ref: '%featureToggles.unresolvedTemplateArguments%')]
		private bool $unresolvedTemplateArguments,
	)
	{
		$this->debugTemplateArguments = getenv('PHPSTAN_TEMPLATE_ARGUMENTS_DEBUG') === '1';
	}

	/**
	 * @param Node[] $nodes
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processNodesWithStorage(
		NodeScopeResolver $nodeScopeResolver,
		array $nodes,
		MutatingScope $scope,
		ExpressionResultStorage $expressionResultStorage,
		callable $nodeCallback,
	): void
	{
		$alreadyTerminated = false;
		$exitPoints = [];

		$stmts = [];
		$stmtToNodeIndex = [];
		foreach ($nodes as $i => $node) {
			if (!($node instanceof Node\Stmt)) {
				continue;
			}

			$stmtToNodeIndex[count($stmts)] = $i;
			$stmts[] = $node;
		}

		$dummyParent = new Node\Stmt\Nop();
		foreach ($stmts as $si => $node) {
			if ($alreadyTerminated && !($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\Label)) {
				continue;
			}

			$nestedLabelNames = $node->getAttribute(GotoLabelVisitor::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE);
			if ($nestedLabelNames !== null) {
				$scope = $this->resolveBackwardGotoScope(
					$nodeScopeResolver,
					$dummyParent,
					[$node],
					$scope,
					$expressionResultStorage,
					StatementContext::createDeep(),
					static fn (string $name): bool => isset($nestedLabelNames[$name]),
					false,
				);
			}

			$statementResult = $nodeScopeResolver->processStmtNode($node, $scope, $expressionResultStorage, $nodeCallback, StatementContext::createTopLevel());
			$scope = $statementResult->getScope();

			if ($node instanceof Node\Stmt\Label) {
				$labelName = $node->name->toString();

				[$scope, $alreadyTerminated, $exitPoints] = $this->mergeForwardGotoExitPoints(
					$labelName,
					$scope,
					$alreadyTerminated,
					$exitPoints,
				);

				if ($alreadyTerminated) {
					continue;
				}

				if ($node->getAttribute(GotoLabelVisitor::HAS_BACKWARD_GOTO_ATTRIBUTE) === true) {
					$scope = $this->resolveBackwardGotoScope(
						$nodeScopeResolver,
						$dummyParent,
						array_slice($stmts, $si + 1),
						$scope,
						$expressionResultStorage,
						StatementContext::createDeep(),
						static fn (string $name): bool => $name === $labelName,
						true,
					);
				}
			}

			$exitPoints = array_merge($exitPoints, $statementResult->getExitPoints());

			if ($alreadyTerminated || !$statementResult->isAlwaysTerminating()) {
				continue;
			}

			$alreadyTerminated = true;
			$nextStmts = $this->getNextUnreachableStatements(array_slice($nodes, $stmtToNodeIndex[$si] + 1), true);
			$this->processUnreachableStatement($nodeScopeResolver, $nextStmts, $scope, $expressionResultStorage, $nodeCallback);
		}
	}

	/**
	 * @param Node\Stmt[] $bodyStmts
	 * @param Closure(string): bool $gotoNameMatcher
	 */
	private function resolveBackwardGotoScope(
		NodeScopeResolver $nodeScopeResolver,
		Node $parentNode,
		array $bodyStmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		StatementContext $context,
		Closure $gotoNameMatcher,
		bool $mergeBodyScopeEachIteration,
	): MutatingScope
	{
		$bodyScope = $scope;
		$count = 0;
		$prevEntryScope = null;
		do {
			$prevScope = $bodyScope;
			if ($mergeBodyScopeEachIteration) {
				$bodyScope = $bodyScope->mergeWith($scope);
			}
			if ($prevEntryScope !== null && $bodyScope->equals($prevEntryScope)) {
				// walking is deterministic in the entry scope - an unchanged entry
				// reproduces the previous pass's exit, so the verification walk is skipped
				$bodyScope = $prevScope;
				break;
			}
			$prevEntryScope = $bodyScope;
			$tempStorage = $storage->duplicate();
			$bodyScopeResult = $nodeScopeResolver->processStmtNodesInternal(
				$parentNode,
				$bodyStmts,
				$bodyScope,
				$tempStorage,
				new NoopNodeCallback(),
				$context->withoutTemplateArgumentResolution(),
			);

			$gotoScope = null;
			foreach ($bodyScopeResult->getExitPoints() as $ep) {
				$epStmt = $ep->getStatement();
				if (!($epStmt instanceof Goto_) || !$gotoNameMatcher($epStmt->name->toString())) {
					continue;
				}

				$gotoScope = $gotoScope === null ? $ep->getScope() : $gotoScope->mergeWith($ep->getScope());
			}

			if ($gotoScope !== null) {
				$bodyScope = $scope->mergeWith($gotoScope);
			}

			if ($bodyScope->equals($prevScope)) {
				break;
			}

			if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
				$bodyScope = $prevScope->generalizeWith($bodyScope);
			}
			$count++;
		} while ($count < NodeScopeResolver::LOOP_SCOPE_ITERATIONS);

		return $bodyScope;
	}

	/**
	 * @param InternalStatementExitPoint[] $exitPoints
	 * @return array{MutatingScope, bool, list<InternalStatementExitPoint>}
	 */
	private function mergeForwardGotoExitPoints(
		string $labelName,
		MutatingScope $scope,
		bool $alreadyTerminated,
		array $exitPoints,
	): array
	{
		$newExitPoints = [];
		foreach ($exitPoints as $exitPoint) {
			$exitStmt = $exitPoint->getStatement();
			if ($exitStmt instanceof Goto_ && $exitStmt->name->toString() === $labelName) {
				if ($alreadyTerminated) {
					$scope = $exitPoint->getScope();
					$alreadyTerminated = false;
				} else {
					$scope = $scope->mergeWith($exitPoint->getScope());
				}
			} else {
				$newExitPoints[] = $exitPoint;
			}
		}

		return [$scope, $alreadyTerminated, $newExitPoints];
	}

	/**
	 * @param Node\Stmt[] $nextStmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processUnreachableStatement(NodeScopeResolver $nodeScopeResolver, array $nextStmts, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback): void
	{
		if ($nextStmts === []) {
			return;
		}

		$unreachableStatement = null;
		$nextStatements = [];

		foreach ($nextStmts as $key => $nextStmt) {
			if ($key === 0) {
				$unreachableStatement = $nextStmt;
				continue;
			}

			$nextStatements[] = $nextStmt;
		}

		if (!$unreachableStatement instanceof Node\Stmt) {
			return;
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, new UnreachableStatementNode($unreachableStatement, $nextStatements), $scope, $storage);
	}

	/**
	 * @param array<Node> $nodes
	 * @return list<Node\Stmt>
	 */
	private function getNextUnreachableStatements(array $nodes, bool $earlyBinding): array
	{
		$stmts = [];
		$isPassedUnreachableStatement = false;
		foreach ($nodes as $node) {
			if ($node instanceof Node\Stmt\Label) {
				break;
			}
			if ($earlyBinding && ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\HaltCompiler)) {
				continue;
			}
			if ($isPassedUnreachableStatement && $node instanceof Node\Stmt) {
				$stmts[] = $node;
				continue;
			}
			if ($node instanceof Node\Stmt\Nop || $node instanceof Node\Stmt\InlineHTML) {
				continue;
			}
			if (!$node instanceof Node\Stmt) {
				continue;
			}
			$stmts[] = $node;
			$isPassedUnreachableStatement = true;
		}
		return $stmts;
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function doProcessStmtNodes(
		NodeScopeResolver $nodeScopeResolver,
		Node $parentNode,
		array $stmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$stmtCount = count($stmts);
		$shouldCheckLastStatement = $parentNode instanceof Node\Stmt\Function_
			|| $parentNode instanceof Node\Stmt\ClassMethod
			|| $parentNode instanceof PropertyHookStatementNode
			|| $parentNode instanceof Expr\Closure;

		if (
			$shouldCheckLastStatement
			&& $stmtCount > 0
			&& $this->unresolvedTemplateArguments
			&& $context->shouldResolveTemplateArguments()
		) {
			return $this->processBodyStmtNodesTwoPass($nodeScopeResolver, $parentNode, $stmts, $scope, $storage, $nodeCallback, $context);
		}

		$state = new StatementListWalkState($scope);
		foreach ($stmts as $i => $stmt) {
			$this->processStatementStep($nodeScopeResolver, $parentNode, $stmts, $i, $stmt, $state, $storage, $nodeCallback, $context, $shouldCheckLastStatement);
		}

		$statementResult = $state->toResult();
		if ($stmtCount === 0 && $shouldCheckLastStatement) {
			$returnTypeNode = $parentNode->getReturnType();
			if ($parentNode instanceof Expr\Closure) {
				$parentNode = new Node\Stmt\Expression($parentNode, $parentNode->getAttributes());
			}
			// the body is empty - the statement above is a synthetic wrapper around
			// the closure, not an expression statement that was processed
			$nodeScopeResolver->callNodeCallback($nodeCallback, new ExecutionEndNode(
				$parentNode,
				$statementResult->toPublic(),
				$returnTypeNode !== null,
			), $scope, $storage);
		}

		return $statementResult;
	}

	/**
	 * One statement of a statement list, advancing $state past it.
	 *
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processStatementStep(
		NodeScopeResolver $nodeScopeResolver,
		Node $parentNode,
		array $stmts,
		int $i,
		Node\Stmt $stmt,
		StatementListWalkState $state,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
		bool $shouldCheckLastStatement,
	): void
	{
		if ($state->alreadyTerminated && !($stmt instanceof Node\Stmt\Function_ || $stmt instanceof Node\Stmt\ClassLike || $stmt instanceof Node\Stmt\Label)) {
			return;
		}

		$isLast = $i === count($stmts) - 1;

		$nestedLabelNames = $stmt->getAttribute(GotoLabelVisitor::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE);
		if ($nestedLabelNames !== null && $context->isTopLevel()) {
			$state->scope = $this->resolveBackwardGotoScope(
				$nodeScopeResolver,
				$parentNode,
				[$stmt],
				$state->scope,
				$storage,
				$context->enterDeep(),
				static fn (string $name): bool => isset($nestedLabelNames[$name]),
				false,
			);
		}

		$statementResult = $nodeScopeResolver->processStmtNode(
			$stmt,
			$state->scope,
			$storage,
			$nodeCallback,
			$context,
		);
		$state->variableFlows[$i] = $statementResult->getVariableFlow();
		$state->scope = $statementResult->getScope();
		$state->hasYield = $state->hasYield || $statementResult->hasYield();

		if ($stmt instanceof Node\Stmt\Label) {
			$labelName = $stmt->name->toString();

			[$state->scope, $state->alreadyTerminated, $state->exitPoints] = $this->mergeForwardGotoExitPoints(
				$labelName,
				$state->scope,
				$state->alreadyTerminated,
				$state->exitPoints,
			);

			if ($state->alreadyTerminated) {
				return;
			}

			if ($stmt->getAttribute(GotoLabelVisitor::HAS_BACKWARD_GOTO_ATTRIBUTE) === true && $context->isTopLevel()) {
				$state->scope = $this->resolveBackwardGotoScope(
					$nodeScopeResolver,
					$parentNode,
					array_slice($stmts, $i + 1),
					$state->scope,
					$storage,
					$context->enterDeep(),
					static fn (string $name): bool => $name === $labelName,
					true,
				);
			}
		}

		if ($shouldCheckLastStatement && $isLast) {
			$hasDeclaredReturnType = ($parentNode instanceof Node\FunctionLike || $parentNode instanceof PropertyHookStatementNode)
				&& $parentNode->getReturnType() !== null;
			$endStatements = $statementResult->getEndStatements();
			if (count($endStatements) > 0) {
				foreach ($endStatements as $endStatement) {
					$endStatementResult = $endStatement->getResult();
					$nodeScopeResolver->callNodeCallback($nodeCallback, new ExecutionEndNode(
						$endStatement->getStatement(),
						(new InternalStatementResult(
							$endStatementResult->getScope(),
							$state->hasYield,
							$endStatementResult->isAlwaysTerminating(),
							$endStatementResult->getExitPoints(),
							$endStatementResult->getThrowPoints(),
							$endStatementResult->getImpurePoints(),
						))->toPublic(),
						$hasDeclaredReturnType,
						$this->readEndStatementExprResult($endStatement->getStatement(), $storage),
					), $endStatementResult->getScope(), $storage);
				}
			} else {
				$nodeScopeResolver->callNodeCallback($nodeCallback, new ExecutionEndNode(
					$stmt,
					(new InternalStatementResult(
						$state->scope,
						$state->hasYield,
						$statementResult->isAlwaysTerminating(),
						$statementResult->getExitPoints(),
						$statementResult->getThrowPoints(),
						$statementResult->getImpurePoints(),
					))->toPublic(),
					$hasDeclaredReturnType,
					$this->readEndStatementExprResult($stmt, $storage),
				), $state->scope, $storage);
			}
		}

		$state->exitPoints = array_merge($state->exitPoints, $statementResult->getExitPoints());
		$state->throwPoints = array_merge($state->throwPoints, $statementResult->getThrowPoints());
		$state->impurePoints = array_merge($state->impurePoints, $statementResult->getImpurePoints());

		if ($state->alreadyTerminated || !$statementResult->isAlwaysTerminating()) {
			return;
		}

		$state->alreadyTerminated = true;
		$nextStmts = $this->getNextUnreachableStatements(array_slice($stmts, $i + 1), $parentNode instanceof Node\Stmt\Namespace_);
		$this->processUnreachableStatement($nodeScopeResolver, $nextStmts, $state->scope, $storage, $nodeCallback);
	}

	/**
	 * The result of the expression an ending statement evaluated, for
	 * ExecutionEndNode. Null when the statement is not an expression statement,
	 * and when its expression was not processed into this storage - an end
	 * statement is collected from wherever execution ended, which can be a
	 * nested walk whose results never reach the frame the end node is built in.
	 */
	private function readEndStatementExprResult(Node\Stmt $stmt, ExpressionResultStorage $storage): ?ExpressionResult
	{
		if (!$stmt instanceof Node\Stmt\Expression) {
			return null;
		}

		return $storage->findExpressionResult($stmt->expr);
	}

	/**
	 * A function-like body under the unresolvedTemplateArguments toggle is
	 * walked in two passes. The observation pass walks every statement
	 * recording rule-facing emissions and threading immutable constraints
	 * through the scopes returned by expressions and statements. A body that
	 * created no unresolved template argument simply replays the recording.
	 * Otherwise TemplateArgumentResolver builds a new resolved frame for the
	 * second pass, which re-walks only the statements the resolutions can
	 * influence. Recorded scopes retain their original collection context.
	 *
	 * The outer gatherer frames (the method's return statements, execution
	 * ends, impure points) are suspended during the observation pass and fed
	 * by the replay and the re-walk, so each emission reaches them once.
	 *
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processBodyStmtNodesTwoPass(
		NodeScopeResolver $nodeScopeResolver,
		Node $parentNode,
		array $stmts,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		StatementContext $context,
	): InternalStatementResult
	{
		$statementStartTokenPositions = [];
		foreach ($stmts as $stmt) {
			$statementStartTokenPositions[] = $stmt->getStartTokenPos();
		}
		$parentFrame = $scope->getCurrentTemplateArgumentFrame();
		$parentConstraints = $scope->getTemplateArgumentConstraints();
		$frame = new TemplateArgumentFrame($parentFrame, closureSignatureBody: $parentNode, closureSignatureStmts: $stmts);
		if (TemplateArgumentStats::$enabled) {
			TemplateArgumentStats::increment('bodiesWalked');
			TemplateArgumentStats::increment('statementsTotal', count($stmts));
		}
		$scope = $scope->withTemplateArgumentFrame($frame)->withTemplateArgumentConstraints(null);
		$recording = new RecordingNodeCallback();
		$state = new StatementListWalkState($scope);
		/** @var list<array{StatementListWalkState, int}> $entries the state and recording offset before each statement, plus the final ones */
		$entries = [];
		$observationContext = $context->withoutTemplateArgumentResolution();
		$suspendedGatherers = $nodeScopeResolver->suspendNodeGatherers();
		try {
			foreach ($stmts as $i => $stmt) {
				$entries[$i] = [clone $state, $recording->count()];
				$this->processStatementStep($nodeScopeResolver, $parentNode, $stmts, $i, $stmt, $state, $storage, $recording, $observationContext, true);
			}
		} finally {
			$nodeScopeResolver->restoreNodeGatherers($suspendedGatherers);
		}
		$stmtCount = count($stmts);
		$entries[$stmtCount] = [clone $state, $recording->count()];
		$constraints = $state->scope->getTemplateArgumentConstraints() ?? TemplateArgumentConstraints::createEmpty();
		$staticSites = $this->staticVariableInference->getSites($parentNode, $stmts);
		$staticVariableTypes = [];
		$staticVariableConditionalExpressions = [];
		$staticStatementIndexes = [];
		$staticWalk = null;
		if ($staticSites !== []) {
			foreach ($staticSites as [, $index]) {
				$staticStatementIndexes[$index] = true;
			}
			[$staticVariableTypes, $staticVariableConditionalExpressions, $constraints, $staticWalk] = $this->inferStaticVariableTypes($nodeScopeResolver, $parentNode, $stmts, $staticSites, $staticStatementIndexes, $parentFrame, $entries, $recording, $storage, $observationContext);
		}
		$frame = $this->templateArgumentResolver->resolve($constraints, $parentFrame, $statementStartTokenPositions, $parentNode, $stmts);
		if ($staticVariableTypes !== []) {
			$frame = $frame->withStaticVariableTypes($staticVariableTypes, $staticStatementIndexes, $staticVariableConditionalExpressions);
		}
		if (
			$staticWalk !== null
			&& $constraints->isEmpty()
			&& !$this->staticVariableInference->hasFunctionLikeFrom($parentNode, $stmts, $staticWalk[0])
		) {
			return $this->replayStaticVariableWalk($nodeScopeResolver, $stmts, $entries, $recording, $staticWalk, $frame, $parentFrame, $parentConstraints, $storage, $nodeCallback, $scope);
		}
		if ($frame->isObservingClosures()) {
			$frame = $this->observeClosureSignatures($nodeScopeResolver, $parentNode, $stmts, $frame, $entries, $storage, $observationContext, $statementStartTokenPositions);
		}

		$firstSiteStatementIndex = $frame->firstSiteStatementIndex();
		if ($firstSiteStatementIndex === null) {
			$nodeScopeResolver->replayRecordingRange($recording, 0, $recording->count(), $nodeCallback, $storage, $scope);

			$state->scope = $state->scope->withTemplateArgumentFrame($parentFrame)->withTemplateArgumentConstraints($parentConstraints);
			return $state->toResult();
		}

		if (TemplateArgumentStats::$enabled) {
			TemplateArgumentStats::increment('bodiesWithSites');
			TemplateArgumentStats::increment('statementsReplayed', $firstSiteStatementIndex);
		}
		// the second pass: the statements before the first site stand as
		// recorded; from there on a statement is re-walked only when it
		// created a site or mentions a variable whose tracked state the
		// resolutions changed - the rest replay their recording and carry
		// their recorded effect onto the re-walked scope
		$nodeScopeResolver->replayRecordingRange($recording, 0, $entries[$firstSiteStatementIndex][1], $nodeCallback, $storage, $scope);
		$hasLabels = $this->containsLabels($stmts);
		$state = clone $entries[$firstSiteStatementIndex][0];
		$state->scope = $state->scope->withTemplateArgumentFrame($frame)->withTemplateArgumentConstraints(null);
		for ($i = $firstSiteStatementIndex; $i < $stmtCount; $i++) {
			[$recordedEntry, $offset] = $entries[$i];
			[$recordedExit, $nextOffset] = $entries[$i + 1];
			$differingRoots = $state->alreadyTerminated === $recordedEntry->alreadyTerminated
				? $state->scope->getDifferingVariableRoots($recordedEntry->scope)
				: null;
			if ($differingRoots === [] && !$frame->hasSiteAtOrAfter($i)) {
				// converged with the observation pass: the rest of its recording stands
				if (TemplateArgumentStats::$enabled) {
					TemplateArgumentStats::increment('earlyExits');
					TemplateArgumentStats::increment('statementsReplayed', $stmtCount - $i);
				}
				$nodeScopeResolver->replayRecordingRange($recording, $offset, $recording->count(), $nodeCallback, $storage, $scope);
				$this->appendRecordedStatementResults($state, $recordedEntry, $entries[$stmtCount][0]);
				// the recorded end scope, with what this pass collected
				$state->scope = $entries[$stmtCount][0]->scope->withTemplateArgumentConstraints($state->scope->getTemplateArgumentConstraints());
				$this->processDeferredByRefClosureBodies($nodeScopeResolver, $frame, $state, $storage, $nodeCallback);

				$state->scope = $state->scope->withTemplateArgumentFrame($parentFrame)->withTemplateArgumentConstraints($parentConstraints);
				return $state->toResult();
			}

			$stmt = $stmts[$i];
			$reWalk = $differingRoots === null
				|| $hasLabels
				|| $frame->ownsSiteInStatement($i)
				|| $this->statementMentionsAnyVariable($stmt, $differingRoots);
			if ($this->debugTemplateArguments) {
				echo sprintf(
					"[template-arguments] %s:%d statement %d: %s (differing: %s)\n",
					$scope->getFile(),
					$stmt->getStartLine(),
					$i,
					$reWalk ? 're-walk' : 'replay',
					$differingRoots === null ? 'non-variable key' : implode(', ', $differingRoots),
				);
			}
			if ($reWalk) {
				if (TemplateArgumentStats::$enabled) {
					TemplateArgumentStats::increment('statementsReWalked');
				}
				$this->processStatementStep($nodeScopeResolver, $parentNode, $stmts, $i, $stmt, $state, $storage, $nodeCallback, $context, true);
				continue;
			}

			if (TemplateArgumentStats::$enabled) {
				TemplateArgumentStats::increment('statementsReplayed');
			}
			$nodeScopeResolver->replayRecordingRange($recording, $offset, $nextOffset, $nodeCallback, $storage, $scope);
			$this->appendRecordedStatementResults($state, $recordedEntry, $recordedExit);
			$state->scope = $state->scope->withRecordedStatementDelta($recordedEntry->scope, $recordedExit->scope);
		}
		$this->processDeferredByRefClosureBodies($nodeScopeResolver, $frame, $state, $storage, $nodeCallback);

		$state->scope = $state->scope->withTemplateArgumentFrame($parentFrame)->withTemplateArgumentConstraints($parentConstraints);
		return $state->toResult();
	}

	/**
	 * The types the body's `static` variables take (see
	 * StaticVariableInference): the observation pass walked the body with their
	 * defaults; from the first `static` statement on, the body is observed again
	 * with the types collected so far - walking, like the second pass, only the
	 * statements that read a variable whose type changed - until the type at
	 * each `static` statement holds every type the variable takes after it,
	 * generalized like a loop's variables. The facts of the last walk are the
	 * ones the template arguments and closure signatures are resolved from; the
	 * walk itself is returned too, see replayStaticVariableWalk().
	 *
	 * The variables of a run of `static` statements (see
	 * StaticVariableInference::getRuns()) hold one of the states a call left
	 * them in together, so the walks also carry how their types depend on each
	 * other after the run - until those conditional expressions converge too,
	 * or are given up.
	 *
	 * @param Node\Stmt[] $stmts
	 * @param non-empty-list<array{Expr\Variable, int, string}> $staticSites
	 * @param array<int, true> $staticStatementIndexes
	 * @param array<int, array{StatementListWalkState, int}> $entries
	 * @return array{array<int, array{Expr\Variable, Type, Type}>, array<int, array{Node\Stmt\Static_, array<string, ConditionalExpressionHolder[]>}>, TemplateArgumentConstraints, array{int, StatementListWalkState, RecordingNodeCallback, ExpressionResultStorage, list<array{int, int, int}>}}
	 */
	private function inferStaticVariableTypes(
		NodeScopeResolver $nodeScopeResolver,
		Node $parentNode,
		array $stmts,
		array $staticSites,
		array $staticStatementIndexes,
		?TemplateArgumentFrame $parentFrame,
		array $entries,
		RecordingNodeCallback $recording,
		ExpressionResultStorage $storage,
		StatementContext $context,
	): array
	{
		$stmtCount = count($stmts);
		$bodyScope = $entries[0][0]->scope;
		$names = [];
		$start = $stmtCount;
		foreach ($staticSites as [, $index, $name]) {
			$names[$name] = true;
			$start = min($start, $index);
		}
		$names = array_keys($names);
		$runs = $this->staticVariableInference->getRuns($parentNode, $stmts);
		$types = $this->collectStaticVariableTypes($names, $this->collectStaticVariableScopes($bodyScope, $recording, $entries[$stmtCount][0], []));
		$conditionalExpressions = $runs !== []
			? $this->collectStaticVariableConditionalExpressions($runs, $this->collectStaticVariableStateScopes($bodyScope, $recording, $entries[$stmtCount][0], []), $storage, null, [], $types)
			: [];
		$hasLabels = $this->containsLabels($stmts);
		$count = 0;
		$conditionalExpressionsCount = 0;
		while (true) {
			$siteTypes = [];
			foreach ($staticSites as [$var, , $name]) {
				[$type, $nativeType] = $types[$name];
				$siteTypes[spl_object_id($var)] = [$var, $type, $nativeType];
			}
			$frame = (new TemplateArgumentFrame($parentFrame, closureSignatureBody: $parentNode, closureSignatureStmts: $stmts))->withStaticVariableTypes($siteTypes, $staticStatementIndexes, $conditionalExpressions);

			$state = clone $entries[$start][0];
			$state->scope = $state->scope->withTemplateArgumentFrame($frame);
			$walkStorage = $storage->duplicate();
			$walkRecording = new RecordingNodeCallback();
			/** @var list<array{int, int, int}> $walkLog see replayStaticVariableWalk() */
			$walkLog = [];
			/** @var list<MutatingScope> $replayedScopes */
			$replayedScopes = [];
			/** @var list<MutatingScope> $carriedOverScopes the entry scopes of the statements not walked again */
			$carriedOverScopes = [];
			$suspendedGatherers = $nodeScopeResolver->suspendNodeGatherers();
			$pushedScope = $state->scope;
			$pushedScope->pushExpressionResultStorage($walkStorage);
			try {
				for ($i = $start; $i < $stmtCount; $i++) {
					$recordedEntry = $entries[$i][0];
					$differingRoots = $state->alreadyTerminated === $recordedEntry->alreadyTerminated
						? $state->scope->getDifferingVariableRoots($recordedEntry->scope)
						: null;
					if ($differingRoots === [] && !$frame->hasSiteAtOrAfter($i)) {
						// converged with the observation pass: the rest of it stands
						$state->scope = $this->withRecordedConstraints($state->scope, $recordedEntry->scope, $entries[$stmtCount][0]->scope);
						$this->appendRecordedStatementResults($state, $recordedEntry, $entries[$stmtCount][0]);
						$walkLog[] = [self::STATIC_WALK_REST, $i, 0];
						break;
					}

					$stmt = $stmts[$i];
					if (
						$differingRoots === null
						|| $hasLabels
						|| $frame->ownsSiteInStatement($i)
						|| $this->statementMentionsAnyVariable($stmt, $differingRoots)
					) {
						$from = $walkRecording->count();
						$this->processStatementStep($nodeScopeResolver, $parentNode, $stmts, $i, $stmt, $state, $walkStorage, $walkRecording, $context, true);
						$walkLog[] = [self::STATIC_WALK_WALKED, $from, $walkRecording->count()];
						$replayedScopes[] = $state->scope;
						continue;
					}

					// the statement does not read what changed: the variables keep
					// their types through it
					$walkLog[] = [self::STATIC_WALK_CARRIED, $i, 0];
					$replayedScopes[] = $state->scope;
					$carriedOverScopes[] = $state->scope;
					$recordedExit = $entries[$i + 1][0];
					$this->appendRecordedStatementResults($state, $recordedEntry, $recordedExit);
					$state->scope = $this->withRecordedConstraints(
						$state->scope->withRecordedStatementDelta($recordedEntry->scope, $recordedExit->scope),
						$recordedEntry->scope,
						$recordedExit->scope,
					);
				}
			} finally {
				$pushedScope->popExpressionResultStorage();
				$nodeScopeResolver->restoreNodeGatherers($suspendedGatherers);
			}
			$constraints = $state->scope->getTemplateArgumentConstraints() ?? TemplateArgumentConstraints::createEmpty();

			$walkTypes = $this->collectStaticVariableTypes($names, $this->collectStaticVariableScopes($bodyScope, $walkRecording, $state, $replayedScopes));
			$stateScopes = $runs !== [] ? $this->collectStaticVariableStateScopes($bodyScope, $walkRecording, $state, $carriedOverScopes) : [];
			$walkConditionalExpressions = $runs !== []
				? $this->collectStaticVariableConditionalExpressions($runs, $stateScopes, $storage, $types, $conditionalExpressions, $types)
				: [];
			$typesConverged = true;
			foreach ($names as $name) {
				if (
					$types[$name][0]->isSuperTypeOf($walkTypes[$name][0])->yes()
					&& $types[$name][1]->isSuperTypeOf($walkTypes[$name][1])->yes()
				) {
					continue;
				}
				$typesConverged = false;
				break;
			}
			$conditionalExpressionsConverged = self::equalStaticVariableConditionalExpressions($conditionalExpressions, $walkConditionalExpressions);
			$count++;
			if ($typesConverged && $conditionalExpressionsConverged) {
				break;
			}
			if ($typesConverged) {
				// the conditional expressions follow the types
				$conditionalExpressionsCount++;
				if ($conditionalExpressionsCount >= NodeScopeResolver::LOOP_SCOPE_ITERATIONS) {
					$runs = [];
					$walkConditionalExpressions = [];
				}
			} elseif ($count >= NodeScopeResolver::LOOP_SCOPE_ITERATIONS) {
				if ($runs === []) {
					break;
				}
				// the conditional expressions keep changing: the walks go on
				// without them - what they narrowed the types to is joined with
				// what the variables take without them
				$runs = [];
				$walkConditionalExpressions = [];
				$count = 0;
			}

			$joinedTypes = $this->joinStaticVariableTypes($bodyScope, $names, $types, $walkTypes, $count > NodeScopeResolver::GENERALIZE_AFTER_ITERATION);
			if ($runs !== [] && !$typesConverged) {
				// the next walk enters with the joined types - the conditions
				// cover them
				$walkConditionalExpressions = $this->collectStaticVariableConditionalExpressions($runs, $stateScopes, $storage, $types, $conditionalExpressions, $joinedTypes);
			}
			$types = $joinedTypes;
			$conditionalExpressions = $walkConditionalExpressions;
		}

		$siteTypes = [];
		foreach ($staticSites as [$var, , $name]) {
			[$type, $nativeType] = $types[$name];
			$siteTypes[spl_object_id($var)] = [$var, $type, $nativeType];
		}

		// every way out of the loop leaves the last walk made with the types
		// and conditional expressions returned
		return [$siteTypes, $conditionalExpressions, $constraints, [$start, $state, $walkRecording, $walkStorage, $walkLog]];
	}

	/**
	 * The second pass of a body whose only sites are its `static` statements
	 * and which observed no template argument or closure signature: it walks
	 * the statements from the first `static` one with the resolved types, as
	 * the last walk inferring them did - with no marker to resolve, a walk
	 * that observes computes what one that does not observe computes. That
	 * walk's recording and results stand for it. A closure or class in these
	 * statements is walked differently by the second pass (resolving its own
	 * template arguments), so the second pass walks those bodies itself.
	 *
	 * @param Node\Stmt[] $stmts
	 * @param array<int, array{StatementListWalkState, int}> $entries
	 * @param array{int, StatementListWalkState, RecordingNodeCallback, ExpressionResultStorage, list<array{int, int, int}>} $staticWalk
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function replayStaticVariableWalk(
		NodeScopeResolver $nodeScopeResolver,
		array $stmts,
		array $entries,
		RecordingNodeCallback $recording,
		array $staticWalk,
		TemplateArgumentFrame $frame,
		?TemplateArgumentFrame $parentFrame,
		?TemplateArgumentConstraints $parentConstraints,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		MutatingScope $scope,
	): InternalStatementResult
	{
		[$start, $state, $walkRecording, $walkStorage, $walkLog] = $staticWalk;
		$stmtCount = count($stmts);
		if (TemplateArgumentStats::$enabled) {
			TemplateArgumentStats::increment('bodiesWithSites');
			TemplateArgumentStats::increment('staticVariableWalksReplayed');
		}
		$nodeScopeResolver->replayRecordingRange($recording, 0, $entries[$start][1], $nodeCallback, $storage, $scope);
		$storage->mergeResults($walkStorage);
		foreach ($walkLog as [$kind, $a, $b]) {
			if ($kind === self::STATIC_WALK_WALKED) {
				$nodeScopeResolver->replayRecordingRange($walkRecording, $a, $b, $nodeCallback, $storage, $scope);
				continue;
			}
			if ($kind === self::STATIC_WALK_CARRIED) {
				$nodeScopeResolver->replayRecordingRange($recording, $entries[$a][1], $entries[$a + 1][1], $nodeCallback, $storage, $scope);
				continue;
			}
			$nodeScopeResolver->replayRecordingRange($recording, $entries[$a][1], $recording->count(), $nodeCallback, $storage, $scope);
			// the recorded end scope, as the second pass takes it
			$state->scope = $entries[$stmtCount][0]->scope;
		}
		$this->processDeferredByRefClosureBodies($nodeScopeResolver, $frame, $state, $storage, $nodeCallback);

		$state->scope = $state->scope->withTemplateArgumentFrame($parentFrame)->withTemplateArgumentConstraints($parentConstraints);
		return $state->toResult();
	}

	/**
	 * The scopes the walk recorded in the body (not in the function-likes
	 * nested in it), at its end, returns and throws, and $moreScopes - where the
	 * body leaves its `static` variables, or runs itself again.
	 *
	 * @param list<MutatingScope> $moreScopes
	 * @return list<MutatingScope>
	 */
	private function collectStaticVariableScopes(MutatingScope $bodyScope, RecordingNodeCallback $recording, StatementListWalkState $endState, array $moreScopes): array
	{
		$scopes = $moreScopes;
		$scopes[] = $endState->scope;
		foreach ($endState->exitPoints as $exitPoint) {
			$scopes[] = $exitPoint->getScope();
		}
		foreach ($endState->throwPoints as $throwPoint) {
			$scopes[] = $throwPoint->getScope();
		}
		foreach ($recording->getPairs() as [, $scope]) {
			$scopes[] = $scope;
		}

		$bodyFunction = $bodyScope->getFunction();
		$bodyReflection = $bodyScope->getAnonymousFunctionReflection();
		$bodyScopes = [];
		$seen = [];
		foreach ($scopes as $scope) {
			if (!$scope instanceof MutatingScope) {
				continue;
			}
			$id = spl_object_id($scope);
			if (isset($seen[$id])) {
				continue;
			}
			$seen[$id] = true;
			if ($scope->getAnonymousFunctionReflection() !== $bodyReflection || $scope->getFunction() !== $bodyFunction) {
				continue;
			}
			$bodyScopes[] = $scope;
		}

		return $bodyScopes;
	}

	/**
	 * The scopes of the body where a call can leave its `static` variables for
	 * the next one: its end, returns and explicit throws, the calls that can
	 * run it again (see StaticVariableInference::canRunUserCode()), and
	 * $moreScopes.
	 *
	 * @param list<MutatingScope> $moreScopes
	 * @return list<MutatingScope>
	 */
	private function collectStaticVariableStateScopes(MutatingScope $bodyScope, RecordingNodeCallback $recording, StatementListWalkState $endState, array $moreScopes): array
	{
		$scopes = $moreScopes;
		$scopes[] = $endState->scope;
		foreach ($endState->exitPoints as $exitPoint) {
			$scopes[] = $exitPoint->getScope();
		}
		foreach ($endState->throwPoints as $throwPoint) {
			if (!$throwPoint->isExplicit()) {
				continue;
			}
			$scopes[] = $throwPoint->getScope();
		}
		foreach ($recording->getPairs() as [$node, $scope]) {
			if (!$scope instanceof MutatingScope || !$this->staticVariableInference->canRunUserCode($node, $scope)) {
				continue;
			}
			$scopes[] = $scope;
		}

		$bodyFunction = $bodyScope->getFunction();
		$bodyReflection = $bodyScope->getAnonymousFunctionReflection();
		$bodyScopes = [];
		$seen = [];
		foreach ($scopes as $scope) {
			$id = spl_object_id($scope);
			if (isset($seen[$id])) {
				continue;
			}
			$seen[$id] = true;
			if ($scope->getAnonymousFunctionReflection() !== $bodyReflection || $scope->getFunction() !== $bodyFunction) {
				continue;
			}
			$bodyScopes[] = $scope;
		}

		return $bodyScopes;
	}

	/**
	 * The [phpdoc, native] types the variables take in the scopes (see
	 * collectStaticVariableScopes()).
	 *
	 * @param list<string> $names
	 * @param list<MutatingScope> $scopes
	 * @return array<string, array{Type, Type}>
	 */
	private function collectStaticVariableTypes(array $names, array $scopes): array
	{
		$typesByName = [];
		foreach ($names as $name) {
			$typesByName[$name] = [[], []];
		}
		foreach ($scopes as $scope) {
			foreach ($names as $name) {
				if ($scope->hasVariableType($name)->no()) {
					continue;
				}
				$typesByName[$name][0][] = $scope->getVariableType($name);
				$typesByName[$name][1][] = $scope->doNotTreatPhpDocTypesAsCertain()->getVariableType($name);
			}
		}

		$types = [];
		foreach ($typesByName as $name => [$phpDocTypes, $nativeTypes]) {
			$types[$name] = $phpDocTypes === []
				? [new NeverType(), new NeverType()]
				: [TypeCombinator::union(...$phpDocTypes), TypeCombinator::union(...$nativeTypes)];
		}

		return $types;
	}

	/**
	 * How the types of the variables of each run of `static` statements depend
	 * on each other right after the run: they hold their defaults, or the state
	 * a call left them in together - in one of the scopes where every variable
	 * of the run is bound (see collectStaticVariableStateScopes()). A scope
	 * where they still hold what the walk entered the run with ($types and
	 * $walkedConditionalExpressions) adds no state.
	 *
	 * For each type a variable holds in some of the states, what another
	 * variable holds in none of the other states - of everything it can hold -
	 * becomes the condition of a conditional expression, like the guards
	 * MutatingScope::mergeWith() derives from the branches it merges.
	 *
	 * @param list<array{Node\Stmt\Static_, array<string, Expr|null>}> $runs
	 * @param list<MutatingScope> $scopes
	 * @param array<string, array{Type, Type}>|null $types
	 * @param array<int, array{Node\Stmt\Static_, array<string, ConditionalExpressionHolder[]>}> $walkedConditionalExpressions
	 * @param array<string, array{Type, Type}> $enteredTypes the types the next walk enters the run with
	 * @return array<int, array{Node\Stmt\Static_, array<string, ConditionalExpressionHolder[]>}>
	 */
	private function collectStaticVariableConditionalExpressions(array $runs, array $scopes, ExpressionResultStorage $storage, ?array $types, array $walkedConditionalExpressions, array $enteredTypes): array
	{
		$conditionalExpressions = [];
		foreach ($runs as [$stmt, $defaults]) {
			$names = array_keys($defaults);
			$defaultState = [];
			foreach ($defaults as $name => $default) {
				$defaultResult = $default !== null ? $storage->findExpressionResult($default) : null;
				$defaultState[$name] = $defaultResult !== null ? $defaultResult->getType() : new NullType();
			}
			/** @var non-empty-list<array<string, Type>> $states */
			$states = [$defaultState];
			foreach ($scopes as $scope) {
				$state = [];
				foreach ($names as $name) {
					if (!$scope->hasVariableType($name)->yes()) {
						continue 2;
					}
					$type = $scope->getVariableType($name);
					if ((new NeverType())->isSuperTypeOf($type)->yes()) {
						// an unreachable scope
						continue 2;
					}
					$state[$name] = $type;
				}
				if ($types !== null && self::isWalkedStaticVariableState($state, $types, $scope, $walkedConditionalExpressions[spl_object_id($stmt)][1] ?? [])) {
					continue;
				}
				foreach ($states as $seenState) {
					if (self::equalStaticVariableStates($seenState, $state)) {
						continue 2;
					}
				}
				$states[] = $state;
			}
			if (count($states) < 2) {
				continue;
			}

			$joinedTypes = [];
			foreach ($names as $name) {
				$joinedTypes[$name] = TypeCombinator::union(...array_column($states, $name));
			}
			$runConditionalExpressions = [];
			foreach ($names as $targetName) {
				/** @var list<Type> $targetTypes */
				$targetTypes = [];
				foreach ($states as $state) {
					foreach ($targetTypes as $targetType) {
						if ($targetType->equals($state[$targetName])) {
							continue 2;
						}
					}
					$targetTypes[] = $state[$targetName];
				}
				foreach ($targetTypes as $targetType) {
					if ($targetType->equals($joinedTypes[$targetName])) {
						continue;
					}
					$otherStates = [];
					foreach ($states as $state) {
						if ($targetType->isSuperTypeOf($state[$targetName])->yes()) {
							continue;
						}
						$otherStates[] = $state;
					}
					// what the target holds in none of the other states - of
					// everything it can hold - covers the target type in a form
					// that does not change with every walk
					$remainingTargetType = TypeCombinator::union($enteredTypes[$targetName][0], $joinedTypes[$targetName]);
					foreach ($otherStates as $otherState) {
						$remainingTargetType = TypeCombinator::remove($remainingTargetType, $otherState[$targetName]);
					}
					if ($remainingTargetType->isSuperTypeOf($targetType)->yes()) {
						$targetType = $remainingTargetType;
					}
					foreach ($names as $guardName) {
						if ($guardName === $targetName) {
							continue;
						}
						// what the guard can hold in no state the target type
						// leaves out - of everything it can hold, the walked
						// type included, so a guard narrowed from that still
						// matches
						$guardType = TypeCombinator::union($enteredTypes[$guardName][0], $joinedTypes[$guardName]);
						foreach ($otherStates as $otherState) {
							$guardType = TypeCombinator::remove($guardType, $otherState[$guardName]);
						}
						if ((new NeverType())->isSuperTypeOf($guardType)->yes()) {
							continue;
						}
						foreach ($otherStates as $otherState) {
							// remove() keeps what it cannot subtract
							if (!$guardType->isSuperTypeOf($otherState[$guardName])->no()) {
								continue 2;
							}
						}
						$runConditionalExpressions['$' . $targetName][] = new ConditionalExpressionHolder(
							['$' . $guardName => ExpressionTypeHolder::createYes(new Expr\Variable($guardName), $guardType)],
							ExpressionTypeHolder::createYes(new Expr\Variable($targetName), $targetType),
						);
					}
				}
			}
			if ($runConditionalExpressions === []) {
				continue;
			}
			$conditionalExpressions[spl_object_id($stmt)] = [$stmt, $runConditionalExpressions];
		}

		return $conditionalExpressions;
	}

	/**
	 * Whether the variables hold the types the walk entered the run with, still
	 * bound by its conditional expressions.
	 *
	 * @param array<string, Type> $state
	 * @param array<string, array{Type, Type}> $types
	 * @param array<string, ConditionalExpressionHolder[]> $walkedConditionalExpressions
	 */
	private static function isWalkedStaticVariableState(array $state, array $types, MutatingScope $scope, array $walkedConditionalExpressions): bool
	{
		foreach ($state as $name => $type) {
			if (!$type->equals($types[$name][0])) {
				return false;
			}
		}
		$scopeConditionalExpressions = $scope->getConditionalExpressions();
		foreach ($walkedConditionalExpressions as $exprString => $holders) {
			foreach ($holders as $holder) {
				foreach ($scopeConditionalExpressions[$exprString] ?? [] as $scopeHolder) {
					if (self::equalConditionalExpressionHolders($holder, $scopeHolder)) {
						continue 2;
					}
				}
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<string, Type> $a
	 * @param array<string, Type> $b
	 */
	private static function equalStaticVariableStates(array $a, array $b): bool
	{
		foreach ($a as $name => $type) {
			if (!$type->equals($b[$name])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<int, array{Node\Stmt\Static_, array<string, ConditionalExpressionHolder[]>}> $a
	 * @param array<int, array{Node\Stmt\Static_, array<string, ConditionalExpressionHolder[]>}> $b
	 */
	private static function equalStaticVariableConditionalExpressions(array $a, array $b): bool
	{
		if (count($a) !== count($b)) {
			return false;
		}
		foreach ($a as $id => [, $aConditionalExpressions]) {
			if (!isset($b[$id]) || count($aConditionalExpressions) !== count($b[$id][1])) {
				return false;
			}
			foreach ($aConditionalExpressions as $exprString => $aHolders) {
				$bHolders = $b[$id][1][$exprString] ?? null;
				if ($bHolders === null || count($aHolders) !== count($bHolders)) {
					return false;
				}
				foreach ($aHolders as $aHolder) {
					foreach ($bHolders as $bHolder) {
						if (self::equalConditionalExpressionHolders($aHolder, $bHolder)) {
							continue 2;
						}
					}
					return false;
				}
			}
		}

		return true;
	}

	private static function equalConditionalExpressionHolders(ConditionalExpressionHolder $a, ConditionalExpressionHolder $b): bool
	{
		if (!$a->getTypeHolder()->equals($b->getTypeHolder())) {
			return false;
		}
		$bConditions = $b->getConditionExpressionTypeHolders();
		if (count($a->getConditionExpressionTypeHolders()) !== count($bConditions)) {
			return false;
		}
		foreach ($a->getConditionExpressionTypeHolders() as $exprString => $condition) {
			if (!isset($bConditions[$exprString]) || !$condition->equals($bConditions[$exprString])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * $types joined with $walkTypes - generalized like a loop's variables once
	 * the join keeps growing.
	 *
	 * @param list<string> $names
	 * @param array<string, array{Type, Type}> $types
	 * @param array<string, array{Type, Type}> $walkTypes
	 * @return array<string, array{Type, Type}>
	 */
	private function joinStaticVariableTypes(MutatingScope $bodyScope, array $names, array $types, array $walkTypes, bool $generalize): array
	{
		$joined = [];
		foreach ($names as $name) {
			$joined[$name] = [
				TypeCombinator::union($types[$name][0], $walkTypes[$name][0]),
				TypeCombinator::union($types[$name][1], $walkTypes[$name][1]),
			];
		}
		if (!$generalize) {
			return $joined;
		}

		$previousScope = $bodyScope;
		$joinedScope = $bodyScope;
		foreach ($names as $name) {
			$previousScope = $previousScope->assignVariable($name, $types[$name][0], $types[$name][1], TrinaryLogic::createYes());
			$joinedScope = $joinedScope->assignVariable($name, $joined[$name][0], $joined[$name][1], TrinaryLogic::createYes());
		}
		$generalizedScope = $previousScope->generalizeWith($joinedScope, array_fill_keys($names, true));
		$generalized = [];
		foreach ($names as $name) {
			$generalized[$name] = [
				$generalizedScope->getVariableType($name),
				$generalizedScope->doNotTreatPhpDocTypesAsCertain()->getVariableType($name),
			];
		}

		return $generalized;
	}

	/**
	 * Looked up instead of injected: the closure processor walks closure bodies
	 * through the statements handler, which constructor injection cannot express.
	 */
	private function getClosureProcessor(): ClosureProcessor
	{
		return $this->container->getByType(ClosureProcessor::class);
	}

	/**
	 * The one analysed walk of every closure of the body whose every invocation
	 * was seen: with the second pass done, the types its by-ref variables had
	 * at the invocations are known (see ClosureProcessor::processDeferredByRefClosureBody()).
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	private function processDeferredByRefClosureBodies(NodeScopeResolver $nodeScopeResolver, TemplateArgumentFrame $frame, StatementListWalkState $state, ExpressionResultStorage $storage, callable $nodeCallback): void
	{
		$sites = $frame->getLocalByRefSites();
		if ($sites === []) {
			return;
		}

		// the invocations are collected from the end of the body joined with
		// where it returned; the body is entered from where the closure was
		// created, which knows what its uses were narrowed to
		$endScope = $state->toResult()->getScope();
		$entryTypes = ClosureSignatureInference::collectByRefEntryTypes($endScope);
		foreach ($sites as [$site]) {
			$creationResult = $storage->findExpressionResult($site);
			$this->getClosureProcessor()->processDeferredByRefClosureBody(
				$nodeScopeResolver,
				$site,
				$creationResult !== null ? $creationResult->getBeforeScope() : $endScope,
				$storage,
				$nodeCallback,
				$entryTypes[spl_object_id($site)] ?? [],
			);
		}
	}

	/**
	 * The closure observation pass: a template argument resolved to something
	 * else than it stood for while observing, so the values read out of its
	 * object - and passed to the body's closures - change in the second pass.
	 * From the first template argument site on, the body is walked again with
	 * the template arguments resolved and without rules, observing only the
	 * closure signatures - like the second pass, a statement is walked only
	 * when it owns a site or mentions a variable the resolutions changed; the
	 * others carry the closure facts of their recorded walk over.
	 *
	 * @param Node\Stmt[] $stmts
	 * @param array<int, array{StatementListWalkState, int}> $entries
	 * @param list<int> $statementStartTokenPositions
	 */
	private function observeClosureSignatures(
		NodeScopeResolver $nodeScopeResolver,
		Node $parentNode,
		array $stmts,
		TemplateArgumentFrame $frame,
		array $entries,
		ExpressionResultStorage $storage,
		StatementContext $context,
		array $statementStartTokenPositions,
	): TemplateArgumentFrame
	{
		$start = $frame->firstSiteStatementIndex() ?? 0;
		$state = clone $entries[$start][0];
		$state->scope = $state->scope->withTemplateArgumentFrame($frame);
		// the second pass replays the observation pass's recording against the storage
		$storage = $storage->duplicate();
		$nodeCallback = new NoopNodeCallback();
		$stmtCount = count($stmts);
		$hasLabels = $this->containsLabels($stmts);
		$suspendedGatherers = $nodeScopeResolver->suspendNodeGatherers();
		$scope = $state->scope;
		$scope->pushExpressionResultStorage($storage);
		try {
			for ($i = $start; $i < $stmtCount; $i++) {
				$recordedEntry = $entries[$i][0];
				$differingRoots = $state->alreadyTerminated === $recordedEntry->alreadyTerminated
					? $state->scope->getDifferingVariableRoots($recordedEntry->scope)
					: null;
				if ($differingRoots === [] && !$frame->hasSiteAtOrAfter($i)) {
					// converged with the observation pass: the rest of its facts stand
					if (TemplateArgumentStats::$enabled) {
						TemplateArgumentStats::increment('closureObservationStatementsReplayed', $stmtCount - $i);
					}
					$state->scope = $this->withRecordedConstraints($state->scope, $recordedEntry->scope, $entries[$stmtCount][0]->scope);
					break;
				}

				$stmt = $stmts[$i];
				if (
					$differingRoots === null
					|| $hasLabels
					|| $frame->ownsSiteInStatement($i)
					|| $this->statementMentionsAnyVariable($stmt, $differingRoots)
				) {
					if (TemplateArgumentStats::$enabled) {
						TemplateArgumentStats::increment('closureObservationStatements');
					}
					$this->processStatementStep($nodeScopeResolver, $parentNode, $stmts, $i, $stmt, $state, $storage, $nodeCallback, $context, true);
					continue;
				}

				if (TemplateArgumentStats::$enabled) {
					TemplateArgumentStats::increment('closureObservationStatementsReplayed');
				}
				$recordedExit = $entries[$i + 1][0];
				$this->appendRecordedStatementResults($state, $recordedEntry, $recordedExit);
				$state->scope = $this->withRecordedConstraints(
					$state->scope->withRecordedStatementDelta($recordedEntry->scope, $recordedExit->scope),
					$recordedEntry->scope,
					$recordedExit->scope,
				);
			}
		} finally {
			$scope->popExpressionResultStorage();
			$nodeScopeResolver->restoreNodeGatherers($suspendedGatherers);
		}

		return $this->templateArgumentResolver->resolveObservedClosures($state->scope->getTemplateArgumentConstraints() ?? TemplateArgumentConstraints::createEmpty(), $frame, $statementStartTokenPositions);
	}

	/** The scope with the facts the observation pass collected from $recordedEntry to $recordedExit. */
	private function withRecordedConstraints(MutatingScope $scope, MutatingScope $recordedEntry, MutatingScope $recordedExit): MutatingScope
	{
		$recorded = $recordedExit->getTemplateArgumentConstraints();
		if ($recorded === null) {
			return $scope;
		}

		return $scope->withTemplateArgumentConstraints(
			($scope->getTemplateArgumentConstraints() ?? TemplateArgumentConstraints::createEmpty())->withRecordedFacts($recordedEntry->getTemplateArgumentConstraints(), $recorded),
		);
	}

	/** @param Node\Stmt[] $stmts */
	private function containsLabels(array $stmts): bool
	{
		foreach ($stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\Label && $stmt->getAttribute(GotoLabelVisitor::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE) === null) {
				continue;
			}

			return true;
		}

		return false;
	}

	/** Carries what the recorded walk from $from to $to added onto $state. */
	private function appendRecordedStatementResults(StatementListWalkState $state, StatementListWalkState $from, StatementListWalkState $to): void
	{
		foreach ($to->variableFlows as $index => $flow) {
			if (array_key_exists($index, $from->variableFlows)) {
				continue;
			}

			$state->variableFlows[$index] = $flow;
		}
		$state->hasYield = $state->hasYield || ($to->hasYield && !$from->hasYield);
		$state->alreadyTerminated = $state->alreadyTerminated || ($to->alreadyTerminated && !$from->alreadyTerminated);
		$state->exitPoints = array_merge($state->exitPoints, array_slice($to->exitPoints, count($from->exitPoints)));
		$state->throwPoints = array_merge($state->throwPoints, array_slice($to->throwPoints, count($from->throwPoints)));
		$state->impurePoints = array_merge($state->impurePoints, array_slice($to->impurePoints, count($from->impurePoints)));
	}

	public function getVariableMentionFlow(Node\Stmt $stmt): ?VariableFlow
	{
		$names = [];
		$mentionsEverything = false;
		$this->collectMentionedVariables($stmt, $names, $mentionsEverything);
		if ($mentionsEverything) {
			return VariableFlow::all(VariableFlow::MENTION_ALL);
		}

		$flows = [];
		foreach (array_keys($names) as $name) {
			$flows[] = VariableFlow::mention($name);
		}
		return VariableFlow::sequence(...$flows);
	}

	/**
	 * @param list<string> $variableNames
	 */
	private function statementMentionsAnyVariable(Node\Stmt $stmt, array $variableNames): bool
	{
		/** @var array{array<string, true>, bool}|null $mentions */
		$mentions = $stmt->getAttribute(self::MENTIONED_VARIABLES_ATTRIBUTE);
		if ($mentions === null) {
			$names = [];
			$mentionsEverything = false;
			$this->collectMentionedVariables($stmt, $names, $mentionsEverything);
			$mentions = [$names, $mentionsEverything];
			$stmt->setAttribute(self::MENTIONED_VARIABLES_ATTRIBUTE, $mentions);
		}
		[$names, $mentionsEverything] = $mentions;
		if ($mentionsEverything) {
			return true;
		}
		foreach ($variableNames as $variableName) {
			if (isset($names[$variableName])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Every variable the statement can read or write - syntactically, so
	 * reads and writes alike - with `$this`; a closure body lives in its own
	 * scope, so only its use() clause (and its bound `$this`) count, an arrow
	 * function captures implicitly and is traversed. Dynamic access
	 * (`$$name`, compact(), extract(), get_defined_vars(), eval, include)
	 * mentions everything.
	 *
	 * @param array<string, true> $names
	 */
	private function collectMentionedVariables(Node $node, array &$names, bool &$mentionsEverything): void
	{
		if ($node instanceof Expr\Variable) {
			if (!is_string($node->name)) {
				$mentionsEverything = true;
				return;
			}
			$names[$node->name] = true;
			return;
		}
		if ($node instanceof Expr\Closure) {
			if (!$node->static) {
				$names['this'] = true;
			}
			foreach ($node->uses as $use) {
				if (!is_string($use->var->name)) {
					$mentionsEverything = true;
					continue;
				}
				$names[$use->var->name] = true;
			}

			return;
		}
		if ($node instanceof Expr\Eval_ || $node instanceof Expr\Include_) {
			$mentionsEverything = true;
		} elseif (
			$node instanceof Expr\FuncCall
			&& $node->name instanceof Name
			&& in_array($node->name->toLowerString(), ['compact', 'extract', 'get_defined_vars'], true)
		) {
			$mentionsEverything = true;
		}

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->$subNodeName;
			if ($subNode instanceof Node) {
				$this->collectMentionedVariables($subNode, $names, $mentionsEverything);
			} elseif (is_array($subNode)) {
				foreach ($subNode as $item) {
					if (!$item instanceof Node) {
						continue;
					}
					$this->collectMentionedVariables($item, $names, $mentionsEverything);
				}
			}
		}
	}

	/**
	 * @return InternalThrowPoint[]|null
	 */
	public function getOverridingThrowPoints(Node\Stmt $statement, MutatingScope $scope): ?array
	{
		foreach ($statement->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}

			$function = $scope->getFunction();
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);

			$throwsTag = $resolvedPhpDoc->getThrowsTag();
			if ($throwsTag !== null) {
				$throwsType = $throwsTag->getType();
				if ($throwsType->isVoid()->yes()) {
					return [];
				}

				return [InternalThrowPoint::createExplicit($scope, $throwsType, $statement, false)];
			}
		}

		return null;
	}

	/**
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function processStmtVarAnnotation(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, ?Expr $defaultExpr, callable $nodeCallback): MutatingScope
	{
		$function = $scope->getFunction();
		$variableLessTags = [];

		foreach ($stmt->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}

			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);

			$assignedVariable = null;
			if (
				$stmt instanceof Node\Stmt\Expression
				&& ($stmt->expr instanceof Assign || $stmt->expr instanceof AssignRef)
				&& $stmt->expr->var instanceof Variable
				&& is_string($stmt->expr->var->name)
			) {
				$assignedVariable = $stmt->expr->var->name;
			}

			foreach ($resolvedPhpDoc->getVarTags() as $name => $varTag) {
				if (is_int($name)) {
					$variableLessTags[] = $varTag;
					continue;
				}

				if ($name === $assignedVariable) {
					continue;
				}

				$certainty = $scope->hasVariableType($name);
				if ($certainty->no()) {
					continue;
				}

				if ($scope->isInClass() && $scope->getFunction() === null) {
					continue;
				}

				if ($scope->canAnyVariableExist()) {
					$certainty = TrinaryLogic::createYes();
				}

				$variableNode = new Variable($name, $stmt->getAttributes());
				$originalType = $scope->getVariableType($name);
				if (!$originalType->equals($varTag->getType())) {
					$nodeScopeResolver->callNodeCallback($nodeCallback, new VarTagChangedExpressionTypeNode($varTag, $variableNode), $scope, $storage);
				}
				$templateArgumentFrame = $nodeScopeResolver->observingTemplateArgumentFrame($scope);
				if ($templateArgumentFrame !== null) {
					$scope = $scope->addTemplateArgumentConstraints($this->templateArgumentObserver->collectSend($varTag->getType(), $originalType));
				}

				$nativeScope = $scope->doNotTreatPhpDocTypesAsCertain();
				$scope = $scope->assignVariable(
					$name,
					$varTag->getType(),
					// a plain variable read is scope state
					$nativeScope->hasVariableType($name)->no() ? new ErrorType() : $nativeScope->getVariableType($name),
					$certainty,
				);
			}
		}

		if (count($variableLessTags) === 1 && $defaultExpr !== null) {
			// only the scope effect here: the changed-type node is emitted by the
			// statement handler AFTER it walked the expression (emitVarTagChangedNode),
			// so the rule prices the expression from its stored result rather than
			// asking the scope about a node not processed yet
			$scope = $scope->assignExpression($defaultExpr, $variableLessTags[0]->getType(), new MixedType());
		}

		return $scope;
	}

	/**
	 * The single variable-less @var tag on the statement's doc comment, or null
	 * when there is none or more than one - the shape processStmtVarAnnotation()
	 * applies to $defaultExpr and emitVarTagChangedNode() reports on.
	 */
	private function findSingleVariableLessVarTag(MutatingScope $scope, Node\Stmt $stmt): ?VarTag
	{
		$function = $scope->getFunction();
		$variableLessTags = [];
		foreach ($stmt->getComments() as $comment) {
			if (!$comment instanceof Doc) {
				continue;
			}

			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$scope->getFile(),
				$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
				$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
				$function !== null ? $function->getName() : null,
				$comment->getText(),
			);
			foreach ($resolvedPhpDoc->getVarTags() as $name => $varTag) {
				if (!is_int($name)) {
					continue;
				}

				$variableLessTags[] = $varTag;
			}
		}

		return count($variableLessTags) === 1 ? $variableLessTags[0] : null;
	}

	/**
	 * Emits the node the @var-changed-type rule listens to, for a statement with
	 * a single variable-less @var tag over $defaultExpr - called by the handler
	 * once the expression has been walked (see processStmtVarAnnotation()).
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function emitVarTagChangedNode(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, Expr $defaultExpr, callable $nodeCallback): TemplateArgumentConstraints
	{
		$varTag = $this->findSingleVariableLessVarTag($scope, $stmt);
		if ($varTag === null) {
			return TemplateArgumentConstraints::createEmpty();
		}

		$nodeScopeResolver->callNodeCallback($nodeCallback, new VarTagChangedExpressionTypeNode($varTag, $defaultExpr), $scope, $storage);
		$defaultExprResult = $storage->findExpressionResult($defaultExpr);
		if ($defaultExprResult === null || $nodeScopeResolver->observingTemplateArgumentFrame($defaultExprResult->getScope()) === null) {
			return TemplateArgumentConstraints::createEmpty();
		}
		return $this->templateArgumentObserver->collectSend($varTag->getType(), $defaultExprResult->getType());
	}

}
