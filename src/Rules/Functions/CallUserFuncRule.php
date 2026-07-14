<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use function count;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<FuncCall>
 */
#[RegisteredRule(level: 5)]
final class CallUserFuncRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private FunctionCallParametersCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		if (count($node->getArgs()) === 0) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);

		$functionName = $functionReflection->getName();
		if ($functionName === 'call_user_func') {
			$result = ArgumentsNormalizer::reorderCallUserFuncArguments(
				$node,
				$scope,
			);
		} elseif ($functionName === 'call_user_func_array') {
			$result = ArgumentsNormalizer::reorderCallUserFuncArrayArguments(
				$node,
				$scope,
			);
		} elseif ($functionName === 'forward_static_call') {
			$result = ArgumentsNormalizer::reorderForwardStaticCallArguments(
				$node,
				$scope,
			);
		} elseif ($functionName === 'forward_static_call_array') {
			$result = ArgumentsNormalizer::reorderForwardStaticCallArrayArguments(
				$node,
				$scope,
			);
		} else {
			return [];
		}
		if ($result === null) {
			return [];
		}
		[$parametersAcceptor, $funcCall, $acceptsNamedArguments] = $result;

		$callableDescription = sprintf('callable passed to %s()', $functionName);

		return $this->check->check(
			$parametersAcceptor,
			$scope,
			false,
			$funcCall,
			'function',
			$acceptsNamedArguments,
			ucfirst($callableDescription) . ' invoked with %d parameter, %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameters, %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameter, at least %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameters, at least %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameter, %d-%d required.',
			ucfirst($callableDescription) . ' invoked with %d parameters, %d-%d required.',
			'%s of ' . $callableDescription . ' expects %s, %s given.',
			'Result of ' . $callableDescription . ' (void) is used.',
			'%s of ' . $callableDescription . ' is passed by reference, so it expects variables only.',
			'Unable to resolve the template type %s in call to ' . $callableDescription,
			'Missing parameter $%s in call to ' . $callableDescription . '.',
			'Unknown parameter $%s in call to ' . $callableDescription . '.',
			'Return type of call to ' . $callableDescription . ' contains unresolvable type.',
			'%s of ' . $callableDescription . ' contains unresolvable type.',
			ucfirst($callableDescription) . ' invoked with %s, but it\'s not allowed because of @no-named-arguments.',
			'Constant %s is not allowed for %s of ' . $callableDescription . '.',
			'Constants %s cannot be combined for %s of ' . $callableDescription . '.',
			'Combining constants with | is not allowed for %s of ' . $callableDescription . '.',
			null,
		);
	}

}
