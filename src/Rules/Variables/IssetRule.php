<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;

/**
 * @implements Rule<Node\Expr\Isset_>
 */
#[RegisteredRule(level: 1)]
final class IssetRule implements Rule
{

	public function __construct(private IssetCheck $issetCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Isset_::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$messages = [];
		foreach ($node->vars as $var) {
			$error = $this->issetCheck->check($var, $scope, 'in isset()', 'isset', static function (Type $type): ?string {
				$isNull = $type->isNull();
				if ($isNull->maybe()) {
					return null;
				}

				if ($isNull->yes()) {
					return 'is always null';
				}

				return 'is not nullable';
			}, self::class);
			if ($error === null) {
				continue;
			}
			$messages[] = $error;
		}

		return $messages;
	}

}
