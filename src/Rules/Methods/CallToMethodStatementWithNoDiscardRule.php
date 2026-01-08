<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
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

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Expression::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodCall = $node->expr;
		$isInVoidCast = false;
		if ($methodCall instanceof Node\Expr\Cast\Void_) {
			$isInVoidCast = true;
			$methodCall = $methodCall->expr;
		}

		if ($methodCall instanceof Node\Expr\BinaryOp\Pipe) {
			if ($methodCall->right instanceof Node\Expr\MethodCall || $methodCall->right instanceof Node\Expr\NullsafeMethodCall) {
				if (!$methodCall->right->isFirstClassCallable()) {
					return [];
				}

				$methodCall = new Node\Expr\MethodCall($methodCall->right->var, $methodCall->right->name, []);
			} elseif ($methodCall->right instanceof Node\Expr\ArrowFunction) {
				$methodCall = $methodCall->right->expr;
			}
		}

		if (!$methodCall instanceof Node\Expr\MethodCall
			&& !$methodCall instanceof Node\Expr\NullsafeMethodCall
		) {
			return [];
		}
		if (!$methodCall->name instanceof Node\Identifier) {
			return [];
		}

		if ($methodCall->isFirstClassCallable()) {
			return [];
		}

		if (!$this->phpVersion->supportsNoDiscardAttribute()) {
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

		$method = $calledOnType->getMethod($methodName, $scope);
		$mustUseReturnValue = $method->mustUseReturnValue();
		if ($isInVoidCast) {
			if ($mustUseReturnValue->no()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Call to %s %s::%s() in (void) cast but method allows discarding return value.',
						$method->isStatic() ? 'static method' : 'method',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
					))->identifier('method.inVoidCast')->build(),
				];
			}

			return [];
		}

		if (!$mustUseReturnValue->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to %s %s::%s() on a separate line discards return value.',
				$method->isStatic() ? 'static method' : 'method',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->identifier('method.resultDiscarded')->nonIgnorable()->build(),
		];
	}

}
