<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\EmptyExpressionNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;

/**
 * @implements Rule<EmptyExpressionNode>
 */
#[RegisteredRule(level: 1)]
final class EmptyRule implements Rule
{

	public function __construct(
		private IssetCheck $issetCheck,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return EmptyExpressionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$exprResult = $node->getExprResult();
		$error = $this->issetCheck->check($exprResult, $scope, 'in empty()', 'empty', static function (Type $type): ?string {
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
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $exprResult->getExpr());
			return [];
		}

		if ($scope->isInTrait()) {
			// IssetCheck's message already distinguishes the possible outcomes,
			// so the contexts only need to be told apart by error/no error.
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $exprResult->getExpr(), true, $error);
			return [];
		}

		return [$error];
	}

}
