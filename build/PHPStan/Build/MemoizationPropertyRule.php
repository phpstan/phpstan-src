<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function dirname;
use function is_string;
use function str_starts_with;
use function strcasecmp;

/**
 * @implements Rule<If_>
 */
final class MemoizationPropertyRule implements Rule
{

	public function __construct(private FileHelper $fileHelper, private bool $skipTests = true)
	{
	}

	public function getNodeType(): string
	{
		return If_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$ifNode = $node;

		if (count($ifNode->stmts) !== 1
			|| !$ifNode->stmts[0] instanceof Expression
			|| count($ifNode->elseifs) !== 0
			|| $ifNode->else !== null
			|| !$ifNode->cond instanceof Identical
			|| !$this->isSupportedFetchNode($ifNode->cond->left)
			|| !$ifNode->cond->right instanceof ConstFetch
			|| strcasecmp($ifNode->cond->right->name->name, 'null') !== 0
		) {
			return [];
		}

		$ifThenNode = $ifNode->stmts[0]->expr;
		if (!$ifThenNode instanceof Assign || !$this->isSupportedFetchNode($ifThenNode->var)) {
			return [];
		}

		if ($this->areNodesNotEqual($ifNode->cond->left, [$ifThenNode->var])) {
			return [];
		}

		if ($this->skipTests && str_starts_with($this->fileHelper->normalizePath($scope->getFile()), $this->fileHelper->normalizePath(dirname(__DIR__, 3) . '/tests'))) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message('This initializing if statement can be replaced with null coalescing assignment operator (??=).')
			->fixNode($node, static fn (If_ $node) => new Expression(new Coalesce($ifThenNode->var, $ifThenNode->expr)))
			->identifier('phpstan.memoizationProperty');

		return [
			$errorBuilder->build(),
		];
	}

	/**
	 * @phpstan-assert-if-true PropertyFetch|StaticPropertyFetch $node
	 */
	private function isSupportedFetchNode(?Expr $node): bool
	{
		return $node instanceof PropertyFetch || $node instanceof StaticPropertyFetch;
	}

	/**
	 * @param list<PropertyFetch|StaticPropertyFetch> $otherNodes
	 */
	private function areNodesNotEqual(PropertyFetch|StaticPropertyFetch $node, array $otherNodes): bool
	{
		if ($node instanceof PropertyFetch) {
			if (!$node->var instanceof Variable
				|| !is_string($node->var->name)
				|| !$node->name instanceof Identifier
			) {
				return true;
			}

			foreach ($otherNodes as $otherNode) {
				if (!$otherNode instanceof PropertyFetch) {
					return true;
				}
				if (!$otherNode->var instanceof Variable
					|| !is_string($otherNode->var->name)
					|| !$otherNode->name instanceof Identifier
				) {
					return true;
				}

				if ($node->var->name !== $otherNode->var->name
					|| $node->name->name !== $otherNode->name->name
				) {
					return true;
				}
			}

			return false;
		}

		if (!$node->class instanceof Name || !$node->name instanceof VarLikeIdentifier) {
			return true;
		}

		foreach ($otherNodes as $otherNode) {
			if (!$otherNode instanceof StaticPropertyFetch) {
				return true;
			}

			if (!$otherNode->class instanceof Name
				|| !$otherNode->name instanceof VarLikeIdentifier
			) {
				return true;
			}

			if ($node->class->toLowerString() !== $otherNode->class->toLowerString()
				|| $node->name->toString() !== $otherNode->name->toString()
			) {
				return true;
			}
		}

		return false;
	}

}
