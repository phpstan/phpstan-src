<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\IssetCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @implements Rule<Node\Expr>
 */
#[RegisteredRule(level: 1)]
final class NullCoalesceRule implements Rule
{

	public function __construct(
		private IssetCheck $issetCheck,
		#[AutowiredParameter(ref: '%featureToggles.unnecessaryNullCoalesce%')]
		private bool $unnecessaryNullCoalesce,
	)
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
			$left = $node->left;
			$right = $node->right;
			$operator = '??';
		} elseif ($node instanceof Node\Expr\AssignOp\Coalesce) {
			$left = $node->var;
			$right = $node->expr;
			$operator = '??=';
		} else {
			return [];
		}

		$error = $this->issetCheck->check($left, $scope, sprintf('on left side of %s', $operator), 'nullCoalesce', $typeMessageCallback);
		if ($error !== null) {
			return [$error];
		}

		$unnecessaryError = $this->checkUnnecessaryNullCoalesce($left, $right, $operator, $scope);
		if ($unnecessaryError !== null) {
			return [$unnecessaryError];
		}

		return [];
	}

	private function checkUnnecessaryNullCoalesce(Node\Expr $left, Node\Expr $right, string $operator, Scope $scope): ?IdentifierRuleError
	{
		if (!$this->unnecessaryNullCoalesce) {
			return null;
		}

		if (!$scope->getType($right)->isNull()->yes()) {
			return null;
		}

		// The coalesce only changes the result when the left side is undefined.
		// If the left side is always set, `?? null` (or `??= null`) never changes
		// anything, so the whole coalesce is redundant.
		if ($scope->toMutatingScope()->issetCheck($left, static fn (): bool => true) !== true) {
			return null;
		}

		return RuleErrorBuilder::message(
			sprintf('Coalesce operator %s is unnecessary because the left side is always set and the right side is null.', $operator),
		)->identifier('nullCoalesce.unnecessary')->build();
	}

}
