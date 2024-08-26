<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use function count;
use function ucfirst;

/**
 * @implements Rule<FuncCall>
 */
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

	public function processNode(Node $node, Scope $scope): array
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
		if ($functionReflection->getName() !== 'call_user_func') {
			return [];
		}

		$result = ArgumentsNormalizer::reorderCallUserFuncArguments(
			$node,
			$scope,
		);
		if ($result === null) {
			return [];
		}
		[$parametersAcceptor, $funcCall, $acceptsNamedArguments] = $result;

		$callableDescription = 'callable passed to call_user_func()';

		return $this->check->check($parametersAcceptor, $scope, false, $funcCall, [
			ucfirst($callableDescription) . ' invoked with %d parameter, %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameters, %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameter, at least %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameters, at least %d required.',
			ucfirst($callableDescription) . ' invoked with %d parameter, %d-%d required.',
			ucfirst($callableDescription) . ' invoked with %d parameters, %d-%d required.',
			'Parameter %s of ' . $callableDescription . ' expects %s, %s given.',
			'Result of ' . $callableDescription . ' (void) is used.',
			'Parameter %s of ' . $callableDescription . ' is passed by reference, so it expects variables only.',
			'Unable to resolve the template type %s in call to ' . $callableDescription,
			'Missing parameter $%s in call to ' . $callableDescription . '.',
			'Unknown parameter $%s in call to ' . $callableDescription . '.',
			'Return type of call to ' . $callableDescription . ' contains unresolvable type.',
			'Parameter %s of ' . $callableDescription . ' contains unresolvable type.',
			ucfirst($callableDescription) . ' invoked with %s, but it\'s not allowed because of @no-named-arguments.',
		], 'function', $acceptsNamedArguments);
	}

}
