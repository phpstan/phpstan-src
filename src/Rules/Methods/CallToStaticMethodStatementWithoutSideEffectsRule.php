<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Expression>
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
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->expr instanceof Node\Expr\StaticCall) {
			return [];
		}

		$staticCall = $node->expr;
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

		if ($method->hasSideEffects()->no() || $node->expr->isFirstClassCallable()) {
			if (!$node->expr->isFirstClassCallable()) {
				$throwsType = $method->getThrowType();
				if ($throwsType !== null && !$throwsType->isVoid()->yes()) {
					return [];
				}
			}

			$methodResult = $scope->getType($staticCall);
			if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
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

		return [];
	}

}
