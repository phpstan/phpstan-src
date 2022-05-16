<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class CallToFunctionParametersRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider, private FunctionCallParametersCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$function = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = SprintfHelper::escapeFormatString($function->getName());

		return $this->check->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$function->getVariants(),
			),
			$scope,
			$function->isBuiltin(),
			$node,
			[
				'Function ' . $functionName . ' invoked with %d parameter, %d required.',
				'Function ' . $functionName . ' invoked with %d parameters, %d required.',
				'Function ' . $functionName . ' invoked with %d parameter, at least %d required.',
				'Function ' . $functionName . ' invoked with %d parameters, at least %d required.',
				'Function ' . $functionName . ' invoked with %d parameter, %d-%d required.',
				'Function ' . $functionName . ' invoked with %d parameters, %d-%d required.',
				'Parameter %s of function ' . $functionName . ' expects %s, %s given.',
				'Result of function ' . $functionName . ' (void) is used.',
				'Parameter %s of function ' . $functionName . ' is passed by reference, so it expects variables only.',
				'Unable to resolve the template type %s in call to function ' . $functionName,
				'Missing parameter $%s in call to function ' . $functionName . '.',
				'Unknown parameter $%s in call to function ' . $functionName . '.',
				'Return type of call to function ' . $functionName . ' contains unresolvable type.',
				'Parameter %s of function ' . $functionName . ' contains unresolvable type.',
			],
		);
	}

}
