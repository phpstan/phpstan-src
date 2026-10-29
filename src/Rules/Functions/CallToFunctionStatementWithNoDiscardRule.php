<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function ltrim;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Expression>
 */
#[RegisteredRule(level: 0)]
final class CallToFunctionStatementWithNoDiscardRule implements Rule
{

	private const CALL_USER_FUNC_FUNCTIONS = [
		'call_user_func',
		'call_user_func_array',
		'forward_static_call',
		'forward_static_call_array',
	];

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

		$funcCall = $this->unwrapCallUserFunc($funcCall, $scope);

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
		$mustUseReturnValue = $this->getCallableMustUseReturnValue($callableType, $funcCall, $scope);
		if ($mustUseReturnValue === null) {
			return [];
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

	/**
	 * Replaces call_user_func('foo') and similar with a call of the callback,
	 * so that the discarded return value of the callback is reported.
	 */
	private function unwrapCallUserFunc(Node\Expr\FuncCall $funcCall, Scope $scope): Node\Expr\FuncCall
	{
		while ($funcCall->name instanceof Node\Name) {
			if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
				break;
			}

			$functionName = strtolower($this->reflectionProvider->getFunction($funcCall->name, $scope)->getName());
			$innerFuncCall = $this->createCallbackFuncCall($functionName, $funcCall);
			if ($innerFuncCall === null) {
				break;
			}

			$funcCall = $innerFuncCall;
		}

		return $funcCall;
	}

	private function createCallbackFuncCall(string $functionName, Node\Expr\FuncCall $funcCall): ?Node\Expr\FuncCall
	{
		if (!in_array($functionName, self::CALL_USER_FUNC_FUNCTIONS, true)) {
			return null;
		}

		$callbackArg = null;
		$passThruArgs = [];
		foreach ($funcCall->getArgs() as $i => $arg) {
			if ($callbackArg === null) {
				if ($arg->name === null && $i === 0) {
					$callbackArg = $arg;
					continue;
				}
				if ($arg->name !== null && $arg->name->toString() === 'callback') {
					$callbackArg = $arg;
					continue;
				}
			}

			$passThruArgs[] = $arg;
		}

		if ($callbackArg === null) {
			return null;
		}

		if (in_array($functionName, ['call_user_func_array', 'forward_static_call_array'], true)) {
			$passThruArgs = [];
		}

		return new Node\Expr\FuncCall($callbackArg->value, $passThruArgs, $funcCall->getAttributes());
	}

	private function getCallableMustUseReturnValue(Type $callableType, Node\Expr\FuncCall $funcCall, Scope $scope): ?TrinaryLogic
	{
		$constantStrings = $callableType->getConstantStrings();
		$callsCallUserFunc = false;
		foreach ($constantStrings as $constantString) {
			if (in_array(strtolower(ltrim($constantString->getValue(), '\\')), self::CALL_USER_FUNC_FUNCTIONS, true)) {
				$callsCallUserFunc = true;
				break;
			}
		}

		if (!$callsCallUserFunc) {
			if (!$callableType->isCallable()->yes()) {
				return null;
			}

			$mustUseReturnValue = TrinaryLogic::createNo();
			foreach ($callableType->getCallableParametersAcceptors($scope) as $callableParametersAcceptor) {
				$mustUseReturnValue = $mustUseReturnValue->or($callableParametersAcceptor->mustUseReturnValue());
			}

			return $mustUseReturnValue;
		}

		$mustUseReturnValue = null;
		foreach ($constantStrings as $constantString) {
			$innerFuncCall = $this->createCallbackFuncCall(strtolower(ltrim($constantString->getValue(), '\\')), $funcCall);
			if ($innerFuncCall !== null && $innerFuncCall->name instanceof Node\Expr) {
				$innerMustUseReturnValue = $this->getCallableMustUseReturnValue($scope->getType($innerFuncCall->name), $innerFuncCall, $scope);
			} else {
				$innerMustUseReturnValue = $this->getCallableMustUseReturnValue($constantString, $funcCall, $scope);
			}

			if ($innerMustUseReturnValue === null) {
				continue;
			}

			$mustUseReturnValue = $mustUseReturnValue === null ? $innerMustUseReturnValue : $mustUseReturnValue->or($innerMustUseReturnValue);
		}

		return $mustUseReturnValue;
	}

}
