<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function array_pop;
use function count;
use function is_string;

/**
 * For each closure that captures a variable by reference, this visitor collects the
 * right-hand side expressions of the assignments to that same variable that happen in the
 * enclosing function-like scope *after* the closure is defined.
 *
 * A variable captured by reference shares its storage with the enclosing variable, and the
 * closure may be invoked at any later point in time. Its type inside the closure body must
 * therefore account for the values the enclosing variable is assigned afterwards, which the
 * forward flow analysis would otherwise not see at the point the closure is created.
 * Assignments preceding the closure are already reflected in the variable's narrowed type.
 */
#[AutowiredService]
final class ClosureUseByRefAssignmentsVisitor extends NodeVisitorAbstract
{

	/**
	 * Attribute value shape: array<string, list<Node\Expr>> keyed by by-reference use variable name.
	 */
	public const ATTRIBUTE_NAME = 'enclosingByRefUseAssignedExprs';

	/**
	 * @var list<array{assignments: list<array{int, string, Node\Expr}>, closures: list<array{Node\Expr\Closure, list<string>}>}>
	 */
	private array $frames = [];

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->frames = [['assignments' => [], 'closures' => []]];
		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (
			($node instanceof Node\Expr\Assign || $node instanceof Node\Expr\AssignRef || $node instanceof Node\Expr\AssignOp)
			&& $node->var instanceof Node\Expr\Variable
			&& is_string($node->var->name)
		) {
			$this->frames[count($this->frames) - 1]['assignments'][] = [$node->getStartFilePos(), $node->var->name, $node->expr];
		}

		if ($node instanceof Node\Expr\Closure) {
			$byRefNames = [];
			foreach ($node->uses as $use) {
				if (!$use->byRef || !is_string($use->var->name)) {
					continue;
				}
				$byRefNames[] = $use->var->name;
			}
			if (count($byRefNames) > 0) {
				$this->frames[count($this->frames) - 1]['closures'][] = [$node, $byRefNames];
			}
		}

		if ($node instanceof Node\FunctionLike) {
			$this->frames[] = ['assignments' => [], 'closures' => []];
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\FunctionLike) {
			$frame = array_pop($this->frames);
			if ($frame !== null) {
				$this->attachFrame($frame);
			}
		}

		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		$frame = array_pop($this->frames);
		if ($frame !== null) {
			$this->attachFrame($frame);
		}

		return null;
	}

	/**
	 * @param array{assignments: list<array{int, string, Node\Expr}>, closures: list<array{Node\Expr\Closure, list<string>}>} $frame
	 */
	private function attachFrame(array $frame): void
	{
		foreach ($frame['closures'] as [$closure, $byRefNames]) {
			$closureStartPos = $closure->getStartFilePos();
			$map = [];
			foreach ($frame['assignments'] as [$assignStartPos, $assignName, $assignExpr]) {
				if ($assignStartPos <= $closureStartPos) {
					continue;
				}
				foreach ($byRefNames as $name) {
					if ($name !== $assignName) {
						continue;
					}
					$map[$name][] = $assignExpr;
				}
			}
			if (count($map) === 0) {
				continue;
			}
			$closure->setAttribute(self::ATTRIBUTE_NAME, $map);
		}
	}

}
