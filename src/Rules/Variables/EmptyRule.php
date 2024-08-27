<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;

/**
 * @implements Rule<Node\Expr\Empty_>
 */
final class EmptyRule implements Rule
{

	public function __construct(private IssetCheck $issetCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Empty_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$error = $this->issetCheck->check($node->expr, $scope, 'in empty()', 'empty', static function (Type $type): ?string {
			$isNull = $type->isNull();
			if ($isNull->maybe()) {
				return null;
			}
			$isFalsey = $type->toBoolean()->isFalse();
			if ($isFalsey->maybe()) {
				return null;
			}

			if ($isNull->yes()) {
				if ($isFalsey->yes()) {
					return 'is always falsy';
				}
				if ($isFalsey->no()) {
					return 'is not falsy';
				}

				return 'is always null';
			}

			if ($isFalsey->yes()) {
				return 'is always falsy';
			}

			if ($isFalsey->no()) {
				return 'is not falsy';
			}

			return 'is not nullable';
		});

		if ($error === null) {
			return [];
		}

		return [$error];
	}

}
