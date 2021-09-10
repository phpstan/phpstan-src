<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IssetCheck;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
class NullCoalesceRule implements \PHPStan\Rules\Rule
{

	private IssetCheck $issetCheck;

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
		$typeMessageCallback = static function (Type $type): ?string {
			$isNull = (new NullType())->isSuperTypeOf($type);
			if ($isNull->maybe()) {
				return null;
			}

			if ($isNull->yes()) {
				return 'is always null';
			}

			return 'is not nullable';
		};

		if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
			$error = $this->issetCheck->check($node->left, $scope, 'on left side of ??', $typeMessageCallback);
		} elseif ($node instanceof Node\Expr\AssignOp\Coalesce) {
			$error = $this->issetCheck->check($node->var, $scope, 'on left side of ??=', $typeMessageCallback);
		} else {
			return [];
		}

		if ($error === null) {
			return [];
		}

		return [$error];
	}

}
