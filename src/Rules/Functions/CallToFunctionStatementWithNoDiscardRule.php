<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
#[RegisteredRule(level: 0)]
final class CallToFunctionStatementWithNoDiscardRule implements Rule
{

	public function __construct(
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
		$funcCall = $node->expr;
		$isInVoidCast = false;
		if ($funcCall instanceof Node\Expr\Cast\Void_) {
			$isInVoidCast = true;
			$funcCall = $funcCall->expr;
		}

		if ($funcCall instanceof Node\Expr\BinaryOp\Pipe) {
			if ($funcCall->right instanceof Node\Expr\FuncCall) {
				if (!$funcCall->right->isFirstClassCallable()) {
					return [];
				}

				$funcCall = new Node\Expr\FuncCall($funcCall->right->name, []);
			} elseif ($funcCall->right instanceof Node\Expr\ArrowFunction) {
				$funcCall = $funcCall->right->expr;
			}
		}

		if (!$funcCall instanceof Node\Expr\FuncCall) {
			return [];
		}

		if ($funcCall->isFirstClassCallable()) {
			return [];
		}

		if ($funcCall->name instanceof Node\Name) {
			if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
				return [];
			}

			$function = $this->reflectionProvider->getFunction($funcCall->name, $scope);
			$mustUseReturnValue = $function->mustUseReturnValue();
			if ($isInVoidCast) {
				if ($mustUseReturnValue->no()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Call to function %s() in (void) cast but function allows discarding return value.',
							$function->getName(),
						))->identifier('function.inVoidCast')->build(),
					];
				}

				return [];
			}

			if (!$mustUseReturnValue->yes()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to function %s() on a separate line discards return value.',
					$function->getName(),
				))->identifier('function.resultDiscarded')->nonIgnorable()->build(),
			];
		}

		$callableType = $scope->getType($funcCall->name);
		if (!$callableType->isCallable()->yes()) {
			return [];
		}

		$mustUseReturnValue = TrinaryLogic::createNo();
		foreach ($callableType->getCallableParametersAcceptors($scope) as $callableParametersAcceptor) {
			$mustUseReturnValue = $mustUseReturnValue->or($callableParametersAcceptor->mustUseReturnValue());
		}

		if ($isInVoidCast) {
			if ($mustUseReturnValue->no()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Call to callable %s in (void) cast but callable allows discarding return value.',
						$callableType->describe(VerbosityLevel::value()),
					))->identifier('callable.inVoidCast')->build(),
				];
			}

			return [];
		}

		if (!$mustUseReturnValue->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Call to callable %s on a separate line discards return value.',
				$callableType->describe(VerbosityLevel::value()),
			))->identifier('callable.resultDiscarded')->nonIgnorable()->build(),
		];
	}

}
