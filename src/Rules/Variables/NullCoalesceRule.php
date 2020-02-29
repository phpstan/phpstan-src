<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IssetCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class NullCoalesceRule implements \PHPStan\Rules\Rule
{

	/** @var IssetCheck */
	private $issetCheck;

	public function __construct(IssetCheck $issetCheck)
	{
		$this->issetCheck = $issetCheck;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
			$error = $this->issetCheck->check($node->left, $scope, '??');
		} elseif ($node instanceof Node\Expr\AssignOp\Coalesce) {
			$error = $this->issetCheck->check($node->var, $scope, '??=');
		} else {
			return [];
		}

		if ($error === null) {
			return [];
		}

		return [$error];
	}

}
