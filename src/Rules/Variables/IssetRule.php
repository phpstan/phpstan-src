<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IssetCheck;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<Node\Expr\Isset_>
 */
class IssetRule implements \PHPStan\Rules\Rule
{

	private IssetCheck $issetCheck;

	public function __construct(IssetCheck $issetCheck)
	{
		$this->issetCheck = $issetCheck;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Isset_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		foreach ($node->vars as $var) {
			$error = $this->issetCheck->check($var, $scope, 'in isset()', static function (Type $type): ?string {
				$isNull = (new NullType())->isSuperTypeOf($type);
				if ($isNull->maybe()) {
					return null;
				}

				if ($isNull->yes()) {
					return 'is always null';
				}

				return 'is not nullable';
			});
			if ($error === null) {
				continue;
			}
			$messages[] = $error;
		}

		return $messages;
	}

}
