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
 * Marks by-reference closure uses (`use (&$var)`) whose captured variable is
 * reassigned in the enclosing scope after the closure was declared.
 *
 * Such variables may hold a different value by the time the closure is invoked,
 * so the closure body must not assume the value present at declaration time.
 */
#[AutowiredService]
final class ClosureUseByRefReassignmentVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureUseByRefReassigned';

	/** @var list<array{closures: list<array{order: int, uses: list<Node\ClosureUse>}>, assignments: array<string, list<int>>}> */
	private array $frames = [];

	private int $order = 0;

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->frames = [self::createFrame()];
		$this->order = 0;

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		$this->order++;

		if ($node instanceof Node\Expr\Closure) {
			$byRefUses = [];
			foreach ($node->uses as $use) {
				if (!$use->byRef) {
					continue;
				}

				$byRefUses[] = $use;
			}

			if (count($byRefUses) > 0) {
				$this->frames[count($this->frames) - 1]['closures'][] = [
					'order' => $this->order,
					'uses' => $byRefUses,
				];
			}

			$this->frames[] = self::createFrame();

			return null;
		}

		if (
			$node instanceof Node\Expr\ArrowFunction
			|| $node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\ClassMethod
		) {
			$this->frames[] = self::createFrame();

			return null;
		}

		$variableName = self::getReassignedVariableName($node);
		if ($variableName !== null) {
			$this->frames[count($this->frames) - 1]['assignments'][$variableName][] = $this->order;
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\Closure
			|| $node instanceof Node\Expr\ArrowFunction
			|| $node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\ClassMethod
		) {
			$frame = array_pop($this->frames);
			if ($frame !== null) {
				self::resolveFrame($frame);
			}
		}

		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		$frame = array_pop($this->frames);
		if ($frame !== null) {
			self::resolveFrame($frame);
		}

		return null;
	}

	/**
	 * @return array{closures: list<array{order: int, uses: list<Node\ClosureUse>}>, assignments: array<string, list<int>>}
	 */
	private static function createFrame(): array
	{
		return ['closures' => [], 'assignments' => []];
	}

	/**
	 * @param array{closures: list<array{order: int, uses: list<Node\ClosureUse>}>, assignments: array<string, list<int>>} $frame
	 */
	private static function resolveFrame(array $frame): void
	{
		foreach ($frame['closures'] as $closure) {
			foreach ($closure['uses'] as $use) {
				if (!is_string($use->var->name)) {
					continue;
				}

				$variableName = $use->var->name;
				if (!isset($frame['assignments'][$variableName])) {
					continue;
				}

				foreach ($frame['assignments'][$variableName] as $assignmentOrder) {
					if ($assignmentOrder > $closure['order']) {
						$use->setAttribute(self::ATTRIBUTE_NAME, true);
						break;
					}
				}
			}
		}
	}

	private static function getReassignedVariableName(Node $node): ?string
	{
		if (
			!$node instanceof Node\Expr\Assign
			&& !$node instanceof Node\Expr\AssignRef
			&& !$node instanceof Node\Expr\AssignOp
			&& !$node instanceof Node\Expr\PreInc
			&& !$node instanceof Node\Expr\PostInc
			&& !$node instanceof Node\Expr\PreDec
			&& !$node instanceof Node\Expr\PostDec
		) {
			return null;
		}

		$var = $node->var;
		while ($var instanceof Node\Expr\ArrayDimFetch) {
			$var = $var->var;
		}

		if ($var instanceof Node\Expr\Variable && is_string($var->name)) {
			return $var->name;
		}

		return null;
	}

}
