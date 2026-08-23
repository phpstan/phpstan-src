<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Type\Type;
use function array_keys;
use function is_array;
use function is_string;

#[GenerateFactory(interface: ExpressionResultFactory::class)]
final class ExpressionResult
{

	/** @var list<string>|null */
	private ?array $readVariableNames = null;

	/** @var list<string>|null */
	private ?array $readStateKeys = null;

	/**
	 * The subtree's emission segment in the convergence-pass recording that
	 * last walked it: [recording, start, end). A consumption splices it into
	 * the consuming pass's recording so that recording stays complete.
	 *
	 * @var array{RecordingNodeCallback, int, int}|null
	 */
	private ?array $recordingSegment = null;

	/** @var (callable(): MutatingScope)|null */
	private $truthyScopeCallback;

	private ?MutatingScope $truthyScope = null;

	/** @var (callable(): MutatingScope)|null */
	private $falseyScopeCallback;

	private ?MutatingScope $falseyScope = null;

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param (callable(): MutatingScope)|null $truthyScopeCallback
	 * @param (callable(): MutatingScope)|null $falseyScopeCallback
	 */
	public function __construct(
		private MutatingScope $scope,
		private MutatingScope $beforeScope,
		private Expr $expr,
		private bool $hasYield,
		private bool $isAlwaysTerminating,
		private array $throwPoints,
		private array $impurePoints,
		private bool $containsNullsafe = false,
		private ?IssetabilityDescriptor $issetabilityDescriptor = null,
		?callable $truthyScopeCallback = null,
		?callable $falseyScopeCallback = null,
	)
	{
		$this->truthyScopeCallback = $truthyScopeCallback;
		$this->falseyScopeCallback = $falseyScopeCallback;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getBeforeScope(): MutatingScope
	{
		return $this->beforeScope;
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function hasYield(): bool
	{
		return $this->hasYield;
	}

	/**
	 * Whether this expression's chain contains a nullsafe operator (?->). A
	 * fetch/call on a receiver whose chain short-circuits propagates null,
	 * which a plain nullable receiver (e.g. a nullable variable) does not -
	 * this flag is what tells them apart.
	 */
	public function containsNullsafe(): bool
	{
		return $this->containsNullsafe;
	}

	/**
	 * The isset/empty/?? view of this expression evaluated at the given
	 * scope: folds the chain descriptor, or builds a leaf resolution from the
	 * expression's own type when it is not a chain link (e.g. a method-call-rooted
	 * base like $this->getFoo()['x']). $useNativeTypes selects native vs phpdoc.
	 */
	public function getIssetabilityResolution(MutatingScope $scope, bool $useNativeTypes): IssetabilityResolution
	{
		if ($this->issetabilityDescriptor !== null) {
			return $this->issetabilityDescriptor->resolve($scope, $useNativeTypes, $this->expr);
		}

		$type = $this->getTypeOnScope($scope, $useNativeTypes);

		return new IssetabilityResolution(
			IssetabilityLinkInfo::leaf($type, $this->expr, $this->expr instanceof Expr\NullsafePropertyFetch),
			null,
		);
	}

	/** Prices this result's expression on the given scope in the requested flavour. */
	public function getTypeOnScope(MutatingScope $scope, bool $useNativeTypes): Type
	{
		return $useNativeTypes ? $scope->getNativeType($this->expr) : $scope->getType($this->expr);
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	/**
	 * @return ImpurePoint[]
	 */
	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	public function getTruthyScope(): MutatingScope
	{
		if ($this->truthyScope !== null) {
			return $this->truthyScope;
		}

		if ($this->truthyScopeCallback === null) {
			return $this->truthyScope = $this->scope->filterByTruthyValue($this->expr);
		}

		$callback = $this->truthyScopeCallback;
		return $this->truthyScope = $callback();
	}

	public function getFalseyScope(): MutatingScope
	{
		if ($this->falseyScope !== null) {
			return $this->falseyScope;
		}

		if ($this->falseyScopeCallback === null) {
			return $this->falseyScope = $this->scope->filterByFalseyValue($this->expr);
		}

		$callback = $this->falseyScopeCallback;
		return $this->falseyScope = $callback();
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
	}

	public function getType(): Type
	{
		return $this->beforeScope->getType($this->expr);
	}

	public function getNativeType(): Type
	{
		return $this->beforeScope->getNativeType($this->expr);
	}

	/**
	 * Whether replaying this result at a foreign position with matching read
	 * state is exact: the walk derived no scope (the same instance came out as
	 * went in), and there are no throw/impure points whose recorded scopes
	 * would replay stale state (a try/catch merges throw-point scopes into its
	 * catch entries).
	 */
	public function isEffectFree(): bool
	{
		return $this->scope === $this->beforeScope
			&& $this->throwPoints === []
			&& $this->impurePoints === [];
	}

	/**
	 * Whether everything this expression reads - variables and tracked
	 * expression holders (property fetches, remembered call results) - has the
	 * same state at the given scope as at this result's own walk position. A
	 * convergence pass may then consume the stored result instead of
	 * re-walking the subtree.
	 */
	public function readStateMatches(MutatingScope $scope, bool $useNativeTypes): bool
	{
		// same unpromoted position implies same promoted position - skip the
		// flavour derivation for the common same-position case
		if ($scope === $this->beforeScope) {
			return true;
		}
		// a closure's stored result IS its (by-ref converged) walk and the walk
		// derives no state from the enclosing position (by-ref effects would
		// fail the effect-free gate); its position-sensitive TYPE is priced by
		// getType() on the consuming position, not by the walk
		if ($this->expr instanceof Expr\Closure || $this->expr instanceof Expr\ArrowFunction) {
			return true;
		}
		$names = $this->getReadVariableNames();
		$stateKeys = $this->getReadStateKeys($scope);
		if ($names === [] && $stateKeys === []) {
			return true;
		}

		$readScope = $useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope;
		$positionScope = $useNativeTypes ? $this->beforeScope->doNotTreatPhpDocTypesAsCertain() : $this->beforeScope;
		if ($readScope === $positionScope) {
			return true;
		}

		foreach ($names as $name) {
			$askKnows = $readScope->hasVariableType($name);
			$positionKnows = $positionScope->hasVariableType($name);
			if ($askKnows->no() && $positionKnows->no()) {
				continue;
			}
			if (!$askKnows->equals($positionKnows)) {
				return false;
			}
			$askType = $readScope->getVariableType($name);
			$positionType = $positionScope->getVariableType($name);
			// identity short-circuits the equals() for unchanged variables -
			// the common case between converged passes
			if ($askType !== $positionType && !$askType->equals($positionType)) {
				return false;
			}
		}

		foreach ($stateKeys as $stateKey) {
			$askHolder = $readScope->expressionTypes[$stateKey] ?? null;
			$positionHolder = $positionScope->expressionTypes[$stateKey] ?? null;
			// unchanged holders stay the same object across derived scopes
			if ($askHolder === $positionHolder) {
				continue;
			}
			if ($askHolder === null || $positionHolder === null) {
				return false;
			}
			if (!$askHolder->getCertainty()->equals($positionHolder->getCertainty())) {
				return false;
			}
			$askType = $askHolder->getType();
			$positionType = $positionHolder->getType();
			if ($askType !== $positionType && !$askType->equals($positionType)) {
				return false;
			}
		}

		return true;
	}

	public function setRecordingSegment(RecordingNodeCallback $recording, int $start, int $end): void
	{
		$this->recordingSegment = [$recording, $start, $end];
	}

	/**
	 * @return array{RecordingNodeCallback, int, int}|null
	 */
	public function getRecordingSegment(): ?array
	{
		return $this->recordingSegment;
	}

	/**
	 * A copy of this result answering at a foreign consuming position: the
	 * scopes are re-anchored to the consuming scope, and the truthy/falsey
	 * callbacks are dropped - the originals capture the walk position's scopes
	 * and would answer stale narrowing; the defaults recompute on the new
	 * position.
	 */
	public function atAskPosition(MutatingScope $scope): self
	{
		$clone = clone $this;
		$clone->scope = $scope;
		$clone->beforeScope = $scope;
		$clone->truthyScope = null;
		$clone->falseyScope = null;
		$clone->truthyScopeCallback = null;
		$clone->falseyScopeCallback = null;

		return $clone;
	}

	/**
	 * @return list<string>
	 */
	private function getReadVariableNames(): array
	{
		return $this->readVariableNames ??= self::collectReadVariableNames($this->expr);
	}

	/**
	 * The names are pure syntax, so they cache on the AST node itself as an
	 * attribute (sharing the node's own lifetime) - a deep fetch/call chain
	 * composes each link's set from its child's cached set in O(1) amortized
	 * instead of re-traversing the whole subtree per link (and per
	 * loop-convergence pass, which recreates the results).
	 */
	private const READ_VARIABLE_NAMES_ATTRIBUTE = 'readVariableNames';

	/**
	 * @return list<string>
	 */
	private static function collectReadVariableNames(Node $node): array
	{
		if ($node instanceof Expr) {
			/** @var list<string>|null $cached */
			$cached = $node->getAttribute(self::READ_VARIABLE_NAMES_ATTRIBUTE);
			if ($cached !== null) {
				return $cached;
			}
		}

		$names = [];
		// $this included: its tracked holder does change ($this instanceof
		// narrowing, ArrayAccess-style writes through $this[...]), and the
		// identity shortcut in the comparison keeps the unchanged case cheap
		if ($node instanceof Expr\Variable && is_string($node->name)) {
			$names[$node->name] = true;
		}
		if ($node instanceof Expr\Closure) {
			// a closure body's variables live in its own scope - only the
			// use() clause reads the enclosing position. Arrow functions
			// capture implicitly and are traversed.
			foreach ($node->uses as $use) {
				if (!is_string($use->var->name)) {
					continue;
				}
				$names[$use->var->name] = true;
			}
		} else {
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					foreach (self::collectReadVariableNames($subNode) as $name) {
						$names[$name] = true;
					}
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						foreach (self::collectReadVariableNames($item) as $name) {
							$names[$name] = true;
						}
					}
				}
			}
		}

		$result = array_keys($names);
		if ($node instanceof Expr) {
			$node->setAttribute(self::READ_VARIABLE_NAMES_ATTRIBUTE, $result);
		}

		return $result;
	}

	/**
	 * @return list<string>
	 */
	private function getReadStateKeys(MutatingScope $scope): array
	{
		return $this->readStateKeys ??= self::collectReadStateKeys($this->expr, $scope->getExprPrinter());
	}

	private const READ_STATE_KEYS_ATTRIBUTE = 'readStateKeys';

	/**
	 * ExprStrings of the subtree's expressions whose state a scope can track as
	 * a holder (property fetches, dim fetches, remembered call and constant
	 * results) - the non-variable half of the expression's read set. Variables
	 * are compared by name via collectReadVariableNames(); scalars are never
	 * tracked; a closure's body lives in its own scope (only its use() clause,
	 * covered by the variable set, reads the enclosing position).
	 *
	 * @return list<string>
	 */
	private static function collectReadStateKeys(Node $node, ExprPrinter $exprPrinter): array
	{
		if ($node instanceof Expr) {
			/** @var list<string>|null $cached */
			$cached = $node->getAttribute(self::READ_STATE_KEYS_ATTRIBUTE);
			if ($cached !== null) {
				return $cached;
			}
		}

		$keys = [];
		if (
			$node instanceof Expr
			&& !$node instanceof Expr\Variable
			&& !$node instanceof Node\Scalar
			&& !$node instanceof Expr\Closure
			&& !$node instanceof Expr\ArrowFunction
		) {
			$keys[$exprPrinter->printExpr($node)] = true;
		}
		if (!$node instanceof Expr\Closure) {
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					foreach (self::collectReadStateKeys($subNode, $exprPrinter) as $key) {
						$keys[$key] = true;
					}
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						foreach (self::collectReadStateKeys($item, $exprPrinter) as $key) {
							$keys[$key] = true;
						}
					}
				}
			}
		}

		$result = array_keys($keys);
		if ($node instanceof Expr) {
			$node->setAttribute(self::READ_STATE_KEYS_ATTRIBUTE, $result);
		}

		return $result;
	}

}
