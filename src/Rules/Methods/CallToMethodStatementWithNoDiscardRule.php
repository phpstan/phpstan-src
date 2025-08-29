<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\VoidCastVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @implements Rule<MethodCall>
 */
#[RegisteredRule(level: 4)]
final class CallToMethodStatementWithNoDiscardRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		// We can ignore NullsafeMethodCall because a virtual MethodCall will
		// also be processed
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		if ($node->hasAttribute(VoidCastVisitor::ATTRIBUTE_NAME)) {
			return [];
		}
		$methodName = $node->name->toString();

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->var),
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

		if (!$method->hasNoDiscardAttribute()->yes()) {
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
