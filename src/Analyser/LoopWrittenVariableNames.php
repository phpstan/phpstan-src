<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use function array_pop;
use function in_array;
use function is_array;
use function is_string;

/**
 * The variables a loop can write while its scope converges.
 *
 * A variable the loop never writes enters every iteration with its value from
 * before the loop, so its type differs between convergence passes only through
 * narrowing by the loop's conditions. Widening such a variable loses its type
 * for nothing.
 */
final class LoopWrittenVariableNames
{

	private const SYNTACTIC_NAMES_ATTRIBUTE = 'phpstanLoopWrittenVariableNames';

	/**
	 * Assignments, increments, destructuring, foreach bindings, catch, static,
	 * global, unset and by-reference closure uses are found in the loop's AST.
	 * Whether an argument is passed by reference is known only from the called
	 * function's reflection, so those writes are read off the variable flow of
	 * the convergence pass.
	 *
	 * @return array<string, true>|null null when the loop can write variables whose names are not known
	 */
	public static function collect(Node $loop, ?VariableFlow $passFlow): ?array
	{
		$names = self::getSyntacticNames($loop);
		if ($names === null) {
			return null;
		}

		$flows = [$passFlow];
		while ($flows !== []) {
			$flow = array_pop($flows);
			if ($flow instanceof VariableAccessFlow) {
				if (in_array($flow->kind, [VariableFlow::WRITE, VariableFlow::DEFINE, VariableFlow::DISCARD, VariableFlow::ESCAPE], true)) {
					$names[$flow->name] = true;
				}
				continue;
			}
			if ($flow instanceof VariableSequenceFlow) {
				foreach ($flow->children as $child) {
					$flows[] = $child;
				}
				continue;
			}
			if (!$flow instanceof VariableControlFlow) {
				continue;
			}

			foreach ($flow->children as $child) {
				$flows[] = $child;
			}
			foreach ($flow->catches as [, $catchFlow]) {
				$flows[] = $catchFlow;
			}
			foreach ($flow->cases as [$caseCondition, $caseBody]) {
				$flows[] = $caseCondition;
				$flows[] = $caseBody;
			}
			foreach ($flow->bindings as $write) {
				$names[$write->getVariableName()] = true;
			}
			foreach ($flow->ownWrites as $write) {
				$names[$write->getVariableName()] = true;
			}
		}

		return $names;
	}

	/**
	 * @return array<string, true>|null
	 */
	private static function getSyntacticNames(Node $loop): ?array
	{
		$cached = $loop->getAttribute(self::SYNTACTIC_NAMES_ATTRIBUTE);
		if (is_array($cached)) {
			return $cached;
		}
		if ($cached === false) {
			return null;
		}

		$names = self::findSyntacticNames($loop);
		$loop->setAttribute(self::SYNTACTIC_NAMES_ATTRIBUTE, $names ?? false);

		return $names;
	}

	/**
	 * @return array<string, true>|null
	 */
	private static function findSyntacticNames(Node $loop): ?array
	{
		$names = [];
		$nodes = [$loop];
		while ($nodes !== []) {
			$node = array_pop($nodes);
			if ($node instanceof Stmt\Function_ || $node instanceof Stmt\ClassLike) {
				continue;
			}
			if (
				($node instanceof Expr\Variable && !is_string($node->name))
				|| $node instanceof Expr\Include_
				|| $node instanceof Expr\Eval_
				|| (
					$node instanceof Expr\FuncCall
					&& $node->name instanceof Node\Name
					&& in_array($node->name->toLowerString(), ['extract', 'parse_str'], true)
				)
			) {
				return null;
			}

			$targets = [];
			if ($node instanceof Expr\Assign || $node instanceof Expr\AssignOp) {
				$targets[] = $node->var;
			} elseif ($node instanceof Expr\AssignRef) {
				$targets[] = $node->var;
				$targets[] = $node->expr;
			} elseif ($node instanceof Expr\PreInc || $node instanceof Expr\PreDec || $node instanceof Expr\PostInc || $node instanceof Expr\PostDec) {
				$targets[] = $node->var;
			} elseif ($node instanceof Stmt\Foreach_) {
				if ($node->byRef) {
					$targets[] = $node->expr;
				}
				if ($node->keyVar !== null) {
					$targets[] = $node->keyVar;
				}
				$targets[] = $node->valueVar;
			} elseif ($node instanceof Stmt\Catch_) {
				if ($node->var !== null) {
					$targets[] = $node->var;
				}
			} elseif ($node instanceof Stmt\Static_) {
				foreach ($node->vars as $staticVar) {
					$targets[] = $staticVar->var;
				}
			} elseif ($node instanceof Stmt\Global_ || $node instanceof Stmt\Unset_) {
				foreach ($node->vars as $var) {
					$targets[] = $var;
				}
			} elseif ($node instanceof Expr\Closure) {
				foreach ($node->uses as $use) {
					if (!$use->byRef) {
						continue;
					}
					$targets[] = $use->var;
				}
			}

			foreach ($targets as $target) {
				$targetNames = self::getTargetNames($target);
				if ($targetNames === null) {
					return null;
				}
				foreach ($targetNames as $targetName) {
					$names[$targetName] = true;
				}
			}

			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$nodes[] = $subNode;
					continue;
				}
				if (!is_array($subNode)) {
					continue;
				}
				foreach ($subNode as $subNodeItem) {
					if (!$subNodeItem instanceof Node) {
						continue;
					}
					$nodes[] = $subNodeItem;
				}
			}
		}

		return $names;
	}

	/**
	 * @return list<string>|null
	 */
	private static function getTargetNames(Expr $target): ?array
	{
		while ($target instanceof Expr\ArrayDimFetch || $target instanceof Expr\PropertyFetch || $target instanceof Expr\NullsafePropertyFetch) {
			$target = $target->var;
		}
		if ($target instanceof Expr\Variable) {
			return is_string($target->name) ? [$target->name] : null;
		}
		if (!$target instanceof Expr\List_ && !$target instanceof Expr\Array_) {
			return [];
		}

		$names = [];
		foreach ($target->items as $item) {
			if ($item === null) {
				continue;
			}
			$itemNames = self::getTargetNames($item->value);
			if ($itemNames === null) {
				return null;
			}
			foreach ($itemNames as $itemName) {
				$names[] = $itemName;
			}
		}

		return $names;
	}

}
