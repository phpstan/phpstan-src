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
use PHPStan\Analyser\Generics\TemplateArgumentConstraints;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Analyser\Generics\TemplateArgumentObserver;
use PHPStan\Analyser\Generics\TemplateArgumentResolver;
use PHPStan\Analyser\Generics\TemplateArgumentStats;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\PropertyHookStatementNode;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Parser\GotoLabelVisitor;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
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
use function sprintf;

/**
 * Walks statement lists for NodeScopeResolver - goto convergence, unreachable
 * statements, the two-pass function-like body walk - and applies statement-level
 * PHPDocs (@var, @throws). A single statement is dispatched to its StmtHandler
 * by NodeScopeResolver::processStmtNode().
 */
#[AutowiredService]
final class StatementsHandler
{

	private const MENTIONED_VARIABLES_ATTRIBUTE = 'templateArgumentMentionedVariables';

	/** PHPSTAN_TEMPLATE_ARGUMENTS_DEBUG=1 prints every second-pass re-walk/replay decision. */
	private bool $debugTemplateArguments;

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private TemplateArgumentObserver $templateArgumentObserver,
		private TemplateArgumentResolver $templateArgumentResolver,
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
		$frame = new TemplateArgumentFrame($parentFrame);
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
		$frame = $this->templateArgumentResolver->resolve($state->scope->getTemplateArgumentConstraints() ?? TemplateArgumentConstraints::createEmpty(), $parentFrame, $statementStartTokenPositions);
		$stmtCount = count($stmts);
		$entries[$stmtCount] = [clone $state, $recording->count()];

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
		$hasLabels = false;
		foreach ($stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\Label && $stmt->getAttribute(GotoLabelVisitor::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE) === null) {
				continue;
			}
			$hasLabels = true;
			break;
		}
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
				$state->scope = $entries[$stmtCount][0]->scope;

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

		$state->scope = $state->scope->withTemplateArgumentFrame($parentFrame)->withTemplateArgumentConstraints($parentConstraints);
		return $state->toResult();
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
