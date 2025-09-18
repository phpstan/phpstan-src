<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
#[RegisteredRule(level: 0)]
final class CallToMethodStatementWithNoDiscardRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->expr instanceof Node\Expr\MethodCall
			&& !$node->expr instanceof Node\Expr\NullsafeMethodCall
		) {
			return [];
		}

		if ($node->expr->isFirstClassCallable()) {
			return [];
		}

		$funcCall = $node->expr;
		if (!$funcCall->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $funcCall->name->toString();

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $funcCall->var),
			'',
			static fn (Type $type): bool => $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes(),
		);
		$calledOnType = $typeResult->getType();
		if ($calledOnType instanceof ErrorType) {
			return [];
		}
		if (!$calledOnType->canCallMethods()->yes()) {
			return [];
		}

		if (!$calledOnType->hasMethod($methodName)->yes()) {
			return [];
		}

		$method = $calledOnType->getMethod($methodName, $scope);

		if (!$method->mustUseReturnValue()->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to %s %s::%s() on a separate line discards return value.',
				$method->isStatic() ? 'static method' : 'method',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->identifier('method.resultDiscarded')->build(),
		];
	}

}
