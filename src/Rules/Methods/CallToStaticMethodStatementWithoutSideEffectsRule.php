<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Node\NoopExpressionNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<NoopExpressionNode>
 */
final class CallToStaticMethodStatementWithoutSideEffectsRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return NoopExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$staticCall = $node->getOriginalExpr();
		if (!$staticCall instanceof Node\Expr\StaticCall) {
			return [];
		}

		if (!$staticCall->name instanceof Node\Identifier) {
			return [];
		}

		$methodName = $staticCall->name->toString();
		if ($staticCall->class instanceof Node\Name) {
			$className = $scope->resolveName($staticCall->class);
			if (!$this->reflectionProvider->hasClass($className)) {
				return [];
			}

			$calledOnType = new ObjectType($className);
		} else {
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $staticCall->class),
				'',
				static fn (Type $type): bool => $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes(),
			);
			$calledOnType = $typeResult->getType();
			if ($calledOnType instanceof ErrorType) {
				return [];
			}
		}

		if (!$calledOnType->canCallMethods()->yes()) {
			return [];
		}

		if (!$calledOnType->hasMethod($methodName)->yes()) {
			return [];
		}

		$method = $calledOnType->getMethod($methodName, $scope);
		if (
			(
				strtolower($method->getName()) === '__construct'
				|| strtolower($method->getName()) === strtolower($method->getDeclaringClass()->getName())
			)
		) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to %s %s::%s() on a separate line has no effect.',
				$method->isStatic() ? 'static method' : 'method',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->identifier('staticMethod.resultUnused')->build(),
		];
	}

}
