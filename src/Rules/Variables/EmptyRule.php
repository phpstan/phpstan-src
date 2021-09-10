<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IssetCheck;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<Node\Expr\Empty_>
 */
class EmptyRule implements \PHPStan\Rules\Rule
{

	private IssetCheck $issetCheck;

	public function __construct(IssetCheck $issetCheck)
	{
		$this->issetCheck = $issetCheck;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Empty_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$error = $this->issetCheck->check($node->expr, $scope, 'in empty()', static function (Type $type): ?string {
			$isNull = (new NullType())->isSuperTypeOf($type);
			$isFalsey = (new ConstantBooleanType(false))->isSuperTypeOf($type->toBoolean());
			if ($isNull->maybe()) {
				return null;
			}
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
