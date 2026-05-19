<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function array_intersect_key;
use function array_pop;
use function count;
use function is_array;
use function spl_object_id;

#[AutowiredService]
final class GotoLabelVisitor extends NodeVisitorAbstract
{

	public const HAS_BACKWARD_GOTO_ATTRIBUTE = 'hasBackwardGoto';

	public const NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE = 'nestedBackwardGotoLabels';

	public const GOTO_LABEL_UNDEFINED_ATTRIBUTE = 'gotoLabelUndefined';

	/** @var array<int, array{labels: array<string, Node\Stmt\Label>, gotos: list<Goto_>}> */
	private array $scopeStack = [];

	/** @var array<int, array{labels: array<string, true>, gotos: array<string, true>}> */
	private array $subtreeData = [];

	private bool $hasGotoOrLabel = false;

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->scopeStack = [];
		$this->subtreeData = [];
		$this->hasGotoOrLabel = false;
		$this->pushScope();
		return null;
	}

	#[Override]
	public function afterTraverse(array $nodes): ?array
	{
		$this->popScope();
		$this->subtreeData = [];
		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Label) {
			$this->hasGotoOrLabel = true;
			$scopeIndex = count($this->scopeStack) - 1;
			$this->scopeStack[$scopeIndex]['labels'][$node->name->toString()] = $node;
			return null;
		}

		if ($node instanceof Goto_) {
			$this->hasGotoOrLabel = true;
			$scopeIndex = count($this->scopeStack) - 1;
			$this->scopeStack[$scopeIndex]['gotos'][] = $node;
			return null;
		}

		if ($this->isScopeBoundary($node)) {
			$this->pushScope();
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (!$this->hasGotoOrLabel) {
			if ($this->isScopeBoundary($node)) {
				$this->popScope();
			}
			return null;
		}

		$id = spl_object_id($node);

		if ($node instanceof Node\Stmt\Label) {
			$this->subtreeData[$id] = ['labels' => [$node->name->toString() => true], 'gotos' => []];
			return null;
		}

		if ($node instanceof Goto_) {
			$this->subtreeData[$id] = ['labels' => [], 'gotos' => [$node->name->toString() => true]];
			return null;
		}

		$stmts = $this->getStmts($node);
		if ($stmts !== null) {
			$this->processStatementList($stmts);
		}

		$labels = [];
		$gotos = [];
		$childIds = [];
		foreach ($node->getSubNodeNames() as $name) {
			$sub = $node->{$name};
			if ($sub instanceof Node) {
				if (!$this->isScopeBoundary($sub)) {
					$childId = spl_object_id($sub);
					if (isset($this->subtreeData[$childId])) {
						$labels += $this->subtreeData[$childId]['labels'];
						$gotos += $this->subtreeData[$childId]['gotos'];
						$childIds[] = $childId;
					}
				}
			} elseif (is_array($sub)) {
				foreach ($sub as $subItem) {
					if (!$subItem instanceof Node) {
						continue;
					}
					if ($this->isScopeBoundary($subItem)) {
						continue;
					}
					$childId = spl_object_id($subItem);
					if (!isset($this->subtreeData[$childId])) {
						continue;
					}
					$labels += $this->subtreeData[$childId]['labels'];
					$gotos += $this->subtreeData[$childId]['gotos'];
					$childIds[] = $childId;
				}
			}
		}

		foreach ($childIds as $childId) {
			unset($this->subtreeData[$childId]);
		}

		if ($labels !== [] || $gotos !== []) {
			$this->subtreeData[$id] = ['labels' => $labels, 'gotos' => $gotos];
		}

		if ($this->isScopeBoundary($node)) {
			$this->popScope();
		}

		return null;
	}

	/**
	 * @param array<Node\Stmt> $stmts
	 */
	private function processStatementList(array $stmts): void
	{
		$labelIndices = [];
		foreach ($stmts as $idx => $s) {
			if (!($s instanceof Node\Stmt\Label)) {
				continue;
			}

			$labelIndices[$s->name->toString()] = $idx;
		}

		$stmtCount = count($stmts);

		if ($labelIndices !== []) {
			foreach ($labelIndices as $labelName => $labelIdx) {
				for ($j = $labelIdx + 1; $j < $stmtCount; $j++) {
					$childId = spl_object_id($stmts[$j]);
					if (isset($this->subtreeData[$childId]['gotos'][$labelName])) {
						$stmts[$labelIdx]->setAttribute(self::HAS_BACKWARD_GOTO_ATTRIBUTE, true);
						break;
					}
				}
			}
		}

		foreach ($stmts as $s) {
			if ($s instanceof Node\Stmt\Label) {
				continue;
			}
			$childId = spl_object_id($s);
			if (!isset($this->subtreeData[$childId])) {
				continue;
			}
			$childData = $this->subtreeData[$childId];
			if ($childData['labels'] === [] || $childData['gotos'] === []) {
				continue;
			}
			$matchedLabels = array_intersect_key($childData['gotos'], $childData['labels']);
			if ($matchedLabels === []) {
				continue;
			}

			$s->setAttribute(self::NESTED_BACKWARD_GOTO_LABELS_ATTRIBUTE, $matchedLabels);
		}
	}

	/**
	 * @return array<Node\Stmt>|null
	 */
	private function getStmts(Node $node): ?array
	{
		if ($node instanceof Node\PropertyHook) {
			return is_array($node->body) ? $node->body : null;
		}

		if ($node instanceof Node\Stmt\ClassLike) {
			return null;
		}

		if (
			$node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\ClassMethod
			|| $node instanceof Node\Expr\Closure
			|| $node instanceof Node\Stmt\If_
			|| $node instanceof Node\Stmt\ElseIf_
			|| $node instanceof Node\Stmt\Else_
			|| $node instanceof Node\Stmt\Case_
			|| $node instanceof Node\Stmt\Catch_
			|| $node instanceof Node\Stmt\Do_
			|| $node instanceof Node\Stmt\Finally_
			|| $node instanceof Node\Stmt\For_
			|| $node instanceof Node\Stmt\Foreach_
			|| $node instanceof Node\Stmt\Namespace_
			|| $node instanceof Node\Stmt\TryCatch
			|| $node instanceof Node\Stmt\While_
			|| $node instanceof Node\Stmt\Block
			|| $node instanceof Node\Stmt\Declare_
		) {
			return $node->stmts ?? null;
		}

		return null;
	}

	private function isScopeBoundary(Node $node): bool
	{
		if (
			$node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\ClassMethod
			|| $node instanceof Node\Expr\Closure
			|| $node instanceof Node\Stmt\ClassLike
		) {
			return true;
		}

		return $node instanceof Node\PropertyHook && is_array($node->body);
	}

	private function pushScope(): void
	{
		$this->scopeStack[] = ['labels' => [], 'gotos' => []];
	}

	private function popScope(): void
	{
		$frame = array_pop($this->scopeStack);
		if ($frame === null) {
			return;
		}

		foreach ($frame['gotos'] as $goto) {
			if (isset($frame['labels'][$goto->name->toString()])) {
				continue;
			}

			$goto->setAttribute(self::GOTO_LABEL_UNDEFINED_ATTRIBUTE, true);
		}
	}

}
