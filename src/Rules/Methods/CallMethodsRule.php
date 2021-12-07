<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use function array_merge;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
class CallMethodsRule implements Rule
{

	private MethodCallCheck $methodCallCheck;

	private FunctionCallParametersCheck $parametersCheck;

	public function __construct(
		MethodCallCheck $methodCallCheck,
		FunctionCallParametersCheck $parametersCheck
	)
	{
		$this->methodCallCheck = $methodCallCheck;
		$this->parametersCheck = $parametersCheck;
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$methodName = $node->name->name;

		[$errors, $methodReflection] = $this->methodCallCheck->check($scope, $methodName, $node->var);
		if ($methodReflection === null) {
			return $errors;
		}

		$declaringClass = $methodReflection->getDeclaringClass();
		$messagesMethodName = SprintfHelper::escapeFormatString($declaringClass->getDisplayName() . '::' . $methodReflection->getName() . '()');

		return array_merge($errors, $this->parametersCheck->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$methodReflection->getVariants()
			),
			$scope,
			$declaringClass->isBuiltin(),
			$node,
			[
				'Method ' . $messagesMethodName . ' invoked with %d parameter, %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameters, %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameter, at least %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameters, at least %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameter, %d-%d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameters, %d-%d required.',
				'Parameter %s of method ' . $messagesMethodName . ' expects %s, %s given.',
				'Result of method ' . $messagesMethodName . ' (void) is used.',
				'Parameter %s of method ' . $messagesMethodName . ' is passed by reference, so it expects variables only.',
				'Unable to resolve the template type %s in call to method ' . $messagesMethodName,
				'Missing parameter $%s in call to method ' . $messagesMethodName . '.',
				'Unknown parameter $%s in call to method ' . $messagesMethodName . '.',
				'Return type of call to method ' . $messagesMethodName . ' contains unresolvable type.',
			]
		));
	}

}
