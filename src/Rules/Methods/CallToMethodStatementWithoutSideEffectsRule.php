<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function count;
use function sprintf;

/**
 * @implements Rule<NoopExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class CallToMethodStatementWithoutSideEffectsRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return NoopExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodCall = $node->getOriginalExpr();
		if ($methodCall instanceof Node\Expr\NullsafeMethodCall) {
			$scope = $scope->filterByTruthyValue(new Node\Expr\BinaryOp\NotIdentical($methodCall->var, new Node\Expr\ConstFetch(new Node\Name('null'))));
		} elseif (!$methodCall instanceof Node\Expr\MethodCall) {
			return [];
		}

		if (!$methodCall->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $methodCall->name->toString();

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $methodCall->var),
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

		$methodResult = $scope->getType($methodCall);
		if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
			return [];
		}

		$method = $calledOnType->getMethod($methodName, $scope);
		if (count($method->getAsserts()->getAsserts()) > 0) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to %s %s::%s() on a separate line has no effect.',
				$method->isStatic() ? 'static method' : 'method',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->identifier('method.resultUnused')->build(),
		];
	}

}
