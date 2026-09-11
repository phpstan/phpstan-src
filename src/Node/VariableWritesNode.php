<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeAbstract;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;

/**
 * All local-variable write sites of a function-like body, with the set of
 * those whose written value was read on some path afterwards, and the set of
 * variable names the body mentions at all.
 *
 * Emitted right after the body's ReturnStatementsNode, with the scope inside
 * the function-like. Arrow functions have no node of their own - their writes
 * belong to the enclosing function-like.
 *
 * @api
 */
final class VariableWritesNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param list<VariableWrite> $writes
	 * @param array<int, true> $readWriteIds
	 * @param array<int, true> $usedWriteIds
	 * @param array<int, true> $coveredWriteIds
	 * @param array<string, true> $readVariableNames
	 * @param array<int, Type> $redundantWriteTypes
	 * @param array<string, true> $referencedVariableNames
	 * @param array<string, true> $untrackedVariableNames
	 * @param array<int, Foreach_|For_> $variableOverwritingLoops
	 */
	public function __construct(
		private Node\FunctionLike $functionLike,
		private array $writes,
		private array $readWriteIds,
		private array $usedWriteIds,
		private array $coveredWriteIds,
		private array $readVariableNames,
		private array $redundantWriteTypes,
		private array $referencedVariableNames,
		private array $untrackedVariableNames,
		private array $variableOverwritingLoops,
		private bool $opaque,
		private bool $allVariableNamesReferenced,
	)
	{
		parent::__construct($functionLike->getAttributes());
	}

	public function getFunctionLike(): Node\FunctionLike
	{
		return $this->functionLike;
	}

	/**
	 * @return list<VariableWrite>
	 */
	public function getWrites(): array
	{
		return $this->writes;
	}

	/**
	 * The write whose target is this exact node (a parameter's or closure
	 * use's variable), if it is tracked.
	 */
	public function getWriteForNode(Node\Expr\Variable $variable): ?VariableWrite
	{
		foreach ($this->writes as $write) {
			if ($write->getNode() === $variable) {
				return $write;
			}
		}

		return null;
	}

	/**
	 * Whether a construct that can observe every variable by name without
	 * reading its current value (func_get_args()) appears in the body.
	 */
	public function areAllVariableNamesReferenced(): bool
	{
		return $this->allVariableNamesReferenced;
	}

	/** Whether the value reaches an observable use, directly or through another write. */
	public function isUsed(VariableWrite $write): bool
	{
		return isset($this->usedWriteIds[$write->getId()]);
	}

	/**
	 * Whether the value flows into a write that is never read at all - that
	 * write is the one to report, this one only feeds it.
	 */
	public function flowsIntoNeverReadWrite(VariableWrite $write): bool
	{
		return isset($this->coveredWriteIds[$write->getId()]);
	}

	/** Whether some path from the write reaches a read of the written value. */
	public function isRead(VariableWrite $write): bool
	{
		return isset($this->readWriteIds[$write->getId()]);
	}

	/**
	 * Whether the variable name appears at a read site anywhere in the body,
	 * regardless of which writes the read observed.
	 */
	public function isVariableEverRead(string $variableName): bool
	{
		return isset($this->readVariableNames[$variableName]);
	}

	/**
	 * The type of the assigned value when the write assigns the value the
	 * variable provably already has, null otherwise.
	 */
	public function getRedundantType(VariableWrite $write): ?Type
	{
		return $this->redundantWriteTypes[$write->getId()] ?? null;
	}

	/**
	 * The loop statement that binds this write in its head - a foreach key
	 * or value variable, a for-loop initial assignment - when the variable
	 * was assigned before the loop and is read after it with no assignment
	 * in between other than the loop's own bindings and updates: the loop
	 * takes over a variable still in use, rather than a spent loop variable.
	 * Null for every other write.
	 *
	 * @return Foreach_|For_|null
	 */
	public function getVariableOverwritingLoop(VariableWrite $write): ?Node\Stmt
	{
		return $this->variableOverwritingLoops[$write->getId()] ?? null;
	}

	/**
	 * Whether the body mentions the variable at all: a read, a write, a
	 * statement naming it (global, static, a reference alias), or a construct
	 * that can observe every variable (eval, include, a dynamic compact() or
	 * $$name, func_get_args()).
	 */
	public function isVariableReferenced(string $variableName): bool
	{
		return $this->allVariableNamesReferenced
			|| $this->opaque
			|| isset($this->referencedVariableNames[$variableName]);
	}

	/**
	 * Variables whose writes escape the body (by-ref parameters and uses,
	 * global/static variables, reference aliases) - every write counts as used.
	 */
	public function isUntracked(string $variableName): bool
	{
		return isset($this->untrackedVariableNames[$variableName]);
	}

	/**
	 * The body contains a construct (goto) that defeats reaching-write tracking.
	 */
	public function isOpaque(): bool
	{
		return $this->opaque;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_VariableWritesNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
