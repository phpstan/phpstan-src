<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Expression>
 */
class CallToMethodStatementWithoutSideEffectsRule implements Rule
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->expr instanceof Node\Expr\NullsafeMethodCall) {
			$scope = $scope->filterByTruthyValue(new Node\Expr\BinaryOp\NotIdentical($node->expr->var, new Node\Expr\ConstFetch(new Node\Name('null'))));
		} elseif (!$node->expr instanceof Node\Expr\MethodCall) {
			return [];
		}

		$methodCall = $node->expr;
		if (!$methodCall->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $methodCall->name->toString();

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$methodCall->var,
			'',
			static function (Type $type) use ($methodName): bool {
				return $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes();
			},
			true
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
		if ($method->hasSideEffects()->no()) {
			$throwsType = $method->getThrowType();
			if ($throwsType !== null && !$throwsType instanceof VoidType) {
				return [];
			}

			$methodResult = $scope->getType($methodCall);
			if ($methodResult instanceof NeverType && $methodResult->isExplicit()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to %s %s::%s() on a separate line has no effect.',
					$method->isStatic() ? 'static method' : 'method',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName()
				))->build(),
			];
		}

		return [];
	}

}
