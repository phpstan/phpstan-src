<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
#[RegisteredRule(level: 0)]
final class CallToStaticMethodStatementWithNoDiscardRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private ReflectionProvider $reflectionProvider,
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
			if ($methodCall->right instanceof Node\Expr\StaticCall) {
				if (!$methodCall->right->isFirstClassCallable()) {
					return [];
				}

				$methodCall = new Node\Expr\StaticCall($methodCall->right->class, $methodCall->right->name, []);
			} elseif ($methodCall->right instanceof Node\Expr\ArrowFunction) {
				$methodCall = $methodCall->right->expr;
			}
		}

		if (!$methodCall instanceof Node\Expr\StaticCall) {
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
		if ($methodCall->class instanceof Node\Name) {
			$className = $scope->resolveName($methodCall->class);
			if (!$this->reflectionProvider->hasClass($className)) {
				return [];
			}

			$calledOnType = new ObjectType($className);
		} else {
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $methodCall->class),
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
		$mustUseReturnValue = $method->mustUseReturnValue();
		if ($isInVoidCast) {
			if ($mustUseReturnValue->no()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Call to %s %s::%s() in (void) cast but method allows discarding return value.',
						$method->isStatic() ? 'static method' : 'method',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
					))->identifier('staticMethod.inVoidCast')->build(),
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
			))->identifier('staticMethod.resultDiscarded')->nonIgnorable()->build(),
		];
	}

}
