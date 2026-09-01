<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Node\Variable\VariableWrite;

/**
 * All local-variable write sites of a function-like body, with the set of
 * those whose written value was read on some path afterwards.
 *
 * Emitted right after the body's ReturnStatementsNode. Arrow functions have no
 * node of their own - their writes belong to the enclosing function-like.
 */
final class VariableWritesNode extends NodeAbstract implements VirtualNode
{

	/**
	 * @param list<VariableWrite> $writes
	 * @param array<int, true> $readWriteIds
	 * @param array<string, true> $untrackedVariableNames
	 */
	public function __construct(
		Node\FunctionLike $functionLike,
		private array $writes,
		private array $readWriteIds,
		private array $untrackedVariableNames,
		private bool $opaque,
	)
	{
		parent::__construct($functionLike->getAttributes());
	}

	/**
	 * @return list<VariableWrite>
	 */
	public function getWrites(): array
	{
		return $this->writes;
	}

	/**
	 * Whether some path from the write reaches a read of the written value.
	 */
	public function isRead(VariableWrite $write): bool
	{
		return isset($this->readWriteIds[$write->getId()]);
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
