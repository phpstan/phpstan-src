<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

/**
 * Marks expressions whose value is used as a string so that NodeScopeResolver
 * can emit an {@see \PHPStan\Node\ExprUsedAsStringNode} for them.
 *
 * Nested concatenations and interpolation parts are "claimed" by the enclosing
 * concatenation so that a concatenation chain is reported once for the whole
 * expression instead of once per operand.
 */
#[AutowiredService]
final class ExprUsedAsStringVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isExprUsedAsString';

	private const CLAIMED_ATTRIBUTE_NAME = 'claimedByStringConcat';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Echo_) {
			foreach ($node->exprs as $expr) {
				$expr->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		} elseif ($node instanceof Print_) {
			$node->expr->setAttribute(self::ATTRIBUTE_NAME, true);
		} elseif ($node instanceof Cast\String_) {
			$node->expr->setAttribute(self::ATTRIBUTE_NAME, true);
		} elseif ($node instanceof AssignOp\Concat) {
			$node->setAttribute(self::ATTRIBUTE_NAME, true);
			$node->expr->setAttribute(self::CLAIMED_ATTRIBUTE_NAME, true);
		} elseif ($node instanceof Concat) {
			if ($node->getAttribute(self::CLAIMED_ATTRIBUTE_NAME) !== true) {
				$node->setAttribute(self::ATTRIBUTE_NAME, true);
			}
			$node->left->setAttribute(self::CLAIMED_ATTRIBUTE_NAME, true);
			$node->right->setAttribute(self::CLAIMED_ATTRIBUTE_NAME, true);
		} elseif ($node instanceof InterpolatedString) {
			if ($node->getAttribute(self::CLAIMED_ATTRIBUTE_NAME) !== true) {
				$node->setAttribute(self::ATTRIBUTE_NAME, true);
			}
			foreach ($node->parts as $part) {
				if (!$part instanceof Expr) {
					continue;
				}
				$part->setAttribute(self::CLAIMED_ATTRIBUTE_NAME, true);
			}
		} elseif (
			$node instanceof PropertyFetch
			|| $node instanceof NullsafePropertyFetch
			|| $node instanceof MethodCall
			|| $node instanceof NullsafeMethodCall
			|| $node instanceof StaticPropertyFetch
			|| $node instanceof StaticCall
			|| $node instanceof ClassConstFetch
		) {
			if ($node->name instanceof Expr) {
				$node->name->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		} elseif ($node instanceof Variable) {
			if ($node->name instanceof Expr) {
				$node->name->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		}

		return null;
	}

}
