<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;

/**
 * @implements Rule<Node\Expr>
 */
class NullCoalesceRule implements Rule
{

	public function __construct(private IssetCheck $issetCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$typeMessageCallback = static function (Type $type): ?string {
			$isNull = $type->isNull();
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
