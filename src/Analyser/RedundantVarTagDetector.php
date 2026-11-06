<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\RedundantVarTagNode;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function is_string;
use function spl_object_id;

/**
 * Finds the inline @var tags that change nothing about the variable or
 * expression they re-type, for the opt-in reportRedundantVarTag.
 *
 * The walk applies a tag every time it walks the tag's statement: in every
 * pass over a loop body, in the observation pass of template arguments, in
 * every iteration over a closure with by-reference uses. Its own type can flow
 * back into what it re-types in a later pass, so a tag is redundant only when
 * none of these applications changed anything. The engine records each one,
 * and emits the redundant tags once it has processed the statement.
 *
 * Only the type and the certainty are compared: an assignment's tag also makes
 * the native type mixed and forgets what the assigned value narrowed, and
 * without the tag both would only be more precise.
 */
#[AutowiredService]
final class RedundantVarTagDetector implements PerFileAnalysisResettable
{

	private const EXPLICIT_MIXED = 1;
	private const IMPLICIT_MIXED = 2;
	private const EXPLICIT_NEVER = 4;
	private const IMPLICIT_NEVER = 8;

	/** @var array<int, array{Node\Stmt, array<int|string, array{VarTag, bool}>}> statement id => [statement, tag key => [tag, changed nothing so far]] */
	private array $applications = [];

	public function resetFileAnalysisState(): void
	{
		$this->applications = [];
	}

	/**
	 * Records the application of $varTag to a variable, which gets the tag's
	 * type, and becomes certainly defined when $defines is true. $narrowedByForeach
	 * is true for the tag of a foreach's iteratee applied again on entering the
	 * loop body, where the loop may have narrowed the variable to a non-empty
	 * array the tag widens again.
	 *
	 * @param int|string $key the tag's key in ResolvedPhpDocBlock::getVarTags()
	 */
	public function recordVariable(Node\Stmt $stmt, $key, VarTag $varTag, MutatingScope $scope, string $variableName, bool $defines, bool $narrowedByForeach = false): void
	{
		$certainty = $scope->hasVariableType($variableName);
		if ($certainty->no() || ($defines && !$certainty->yes())) {
			$this->record($stmt, $key, $varTag, false);
			return;
		}

		$originalType = $scope->getVariableType($variableName);
		$tagType = $varTag->getType();
		$this->record(
			$stmt,
			$key,
			$varTag,
			$this->isSameType($originalType, $tagType)
			|| ($narrowedByForeach && $this->isSameType($originalType, TypeCombinator::remove($tagType, new ConstantArrayType([], [])))),
		);
	}

	/**
	 * Records the application of the variable-less $varTag to $expr, $scope
	 * being the scope before the tag re-typed it.
	 */
	public function recordExpression(NodeScopeResolver $nodeScopeResolver, Node\Stmt $stmt, VarTag $varTag, Expr $expr, MutatingScope $scope): void
	{
		// priced again from scratch: the walk's results of the expression and
		// of its parts saw the tag's type, re-typing $a['k'] narrows $a as well
		$originalType = $nodeScopeResolver->processExprOnDemand($expr, $scope, new ExpressionResultStorage())->getType();
		$this->record($stmt, 0, $varTag, $this->isSameType($originalType, $varTag->getType()));
	}

	/**
	 * Emits a RedundantVarTagNode for each tag above $stmt that changed
	 * nothing in any of its applications so far.
	 *
	 * @param callable(Node $node, Scope $scope): void $nodeCallback
	 */
	public function emitNodes(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, ExpressionResultStorage $storage, Node\Stmt $stmt, callable $nodeCallback): void
	{
		$id = spl_object_id($stmt);
		if (!isset($this->applications[$id]) || $this->applications[$id][0] !== $stmt) {
			return;
		}

		foreach ($this->applications[$id][1] as $key => [$varTag, $changedNothing]) {
			if (!$changedNothing) {
				continue;
			}

			$nodeScopeResolver->callNodeCallback($nodeCallback, new RedundantVarTagNode($stmt, $varTag, is_string($key) ? $key : null), $scope, $storage);
		}
	}

	/**
	 * @param int|string $key
	 */
	private function record(Node\Stmt $stmt, $key, VarTag $varTag, bool $changedNothing): void
	{
		$id = spl_object_id($stmt);
		if (!isset($this->applications[$id]) || $this->applications[$id][0] !== $stmt) {
			// the id of a statement freed since is reused
			$this->applications[$id] = [$stmt, []];
		}

		$changedNothingBefore = $this->applications[$id][1][$key][1] ?? true;
		$this->applications[$id][1][$key] = [$varTag, $changedNothingBefore && $changedNothing];
	}

	private function isSameType(Type $originalType, Type $tagType): bool
	{
		if (
			// equals() is one-way for a type extending another one, like a
			// template type or a benevolent union, and does not compare
			// whether a constant array is a list
			!$originalType->equals($tagType)
			|| !$tagType->equals($originalType)
			|| !$originalType->isList()->equals($tagType->isList())
		) {
			return false;
		}

		// equals() also ignores whether mixed and never are explicit, which
		// decides what is reported about them
		$kinds = $this->getMixedAndNeverKinds($originalType);

		return $kinds !== null && $kinds === $this->getMixedAndNeverKinds($tagType);
	}

	/**
	 * Which kinds of mixed and never the type contains; null when it contains an
	 * error type, or both kinds of mixed or of never, whose positions are not compared.
	 */
	private function getMixedAndNeverKinds(Type $type): ?int
	{
		$kinds = 0;
		$containsError = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$kinds, &$containsError): Type {
			if ($type instanceof ErrorType) {
				$containsError = true;
				return $type;
			}

			if ($type instanceof MixedType) {
				$kinds |= $type->isExplicitMixed() ? self::EXPLICIT_MIXED : self::IMPLICIT_MIXED;
			} elseif ($type instanceof NeverType) {
				$kinds |= $type->isExplicit() ? self::EXPLICIT_NEVER : self::IMPLICIT_NEVER;
			}

			return $traverse($type);
		});

		if (
			$containsError
			|| ($kinds & (self::EXPLICIT_MIXED | self::IMPLICIT_MIXED)) === (self::EXPLICIT_MIXED | self::IMPLICIT_MIXED)
			|| ($kinds & (self::EXPLICIT_NEVER | self::IMPLICIT_NEVER)) === (self::EXPLICIT_NEVER | self::IMPLICIT_NEVER)
		) {
			return null;
		}

		return $kinds;
	}

}
