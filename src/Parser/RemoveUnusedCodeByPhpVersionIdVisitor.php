<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\Php\VersionCompareHelper;
use function count;
use function in_array;
use function strtolower;
use function version_compare;

final class RemoveUnusedCodeByPhpVersionIdVisitor extends NodeVisitorAbstract
{

	public function __construct(private string $phpVersionString)
	{
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Stmt\If_) {
			return null;
		}

		if (count($node->elseifs) > 0) {
			return null;
		}

		if ($node->else === null) {
			return null;
		}

		$cond = $node->cond;

		$result = $this->evaluateVersionCompareCall($cond);
		if ($result === null) {
			$result = $this->evaluatePhpVersionIdComparison($cond);
		}
		if ($result === null) {
			return null;
		}
		if ($result) {
			// remove else
			$node->cond = new Node\Expr\ConstFetch(new Node\Name('true'));
			$node->else = null;

			return $node;
		}

		// remove if
		$node->cond = new Node\Expr\ConstFetch(new Node\Name('false'));
		$node->stmts = [];

		return $node;
	}

	private function evaluatePhpVersionIdComparison(Node\Expr $cond): ?bool
	{
		if (
			!$cond instanceof Node\Expr\BinaryOp\Smaller
			&& !$cond instanceof Node\Expr\BinaryOp\SmallerOrEqual
			&& !$cond instanceof Node\Expr\BinaryOp\Greater
			&& !$cond instanceof Node\Expr\BinaryOp\GreaterOrEqual
			&& !$cond instanceof Node\Expr\BinaryOp\Equal
			&& !$cond instanceof Node\Expr\BinaryOp\NotEqual
			&& !$cond instanceof Node\Expr\BinaryOp\Identical
			&& !$cond instanceof Node\Expr\BinaryOp\NotIdentical
		) {
			return null;
		}

		$operator = $cond->getOperatorSigil();
		if ($operator === '===') {
			$operator = '==';
		} elseif ($operator === '!==') {
			$operator = '!=';
		}

		$operands = $this->getOperands($cond->left, $cond->right);
		if ($operands === null) {
			return null;
		}

		return version_compare($operands[0], $operands[1], $operator);
	}

	private function evaluateVersionCompareCall(Node\Expr $cond): ?bool
	{
		if (!$cond instanceof Node\Expr\FuncCall) {
			return null;
		}

		if (!$cond->name instanceof Node\Name) {
			return null;
		}

		if (strtolower((string) $cond->name) !== 'version_compare') {
			return null;
		}

		$args = $cond->getArgs();
		if (count($args) !== 3) {
			return null;
		}

		$phpVersionArgIndex = null;
		if (
			$args[0]->value instanceof Node\Expr\ConstFetch
			&& $args[0]->value->name->toString() === 'PHP_VERSION'
		) {
			$phpVersionArgIndex = 0;
		} elseif (
			$args[1]->value instanceof Node\Expr\ConstFetch
			&& $args[1]->value->name->toString() === 'PHP_VERSION'
		) {
			$phpVersionArgIndex = 1;
		}

		if ($phpVersionArgIndex === null) {
			return null;
		}

		$otherArgIndex = $phpVersionArgIndex === 0 ? 1 : 0;
		if (!$args[$otherArgIndex]->value instanceof Node\Scalar\String_) {
			return null;
		}
		$versionString = $args[$otherArgIndex]->value->value;

		if (!$args[2]->value instanceof Node\Scalar\String_) {
			return null;
		}
		$operator = $args[2]->value->value;

		if (!in_array($operator, VersionCompareHelper::VALID_OPERATORS, true)) {
			return null;
		}

		if ($phpVersionArgIndex === 0) {
			return version_compare($this->phpVersionString, $versionString, $operator);
		}

		return version_compare($versionString, $this->phpVersionString, $operator);
	}

	/**
	 * @return array{string, string}|null
	 */
	private function getOperands(Node\Expr $left, Node\Expr $right): ?array
	{
		if (
			$left instanceof Node\Scalar\Int_
			&& $right instanceof Node\Expr\ConstFetch
			&& $right->name->toString() === 'PHP_VERSION_ID'
		) {
			return [(new PhpVersion($left->value))->getVersionString(), $this->phpVersionString];
		}

		if (
			$right instanceof Node\Scalar\Int_
			&& $left instanceof Node\Expr\ConstFetch
			&& $left->name->toString() === 'PHP_VERSION_ID'
		) {
			return [$this->phpVersionString, (new PhpVersion($right->value))->getVersionString()];
		}

		return null;
	}

}
