<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use function array_merge;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
final class CallMethodsRule implements Rule
{

	public function __construct(
		private MethodCallCheck $methodCallCheck,
		private FunctionCallParametersCheck $parametersCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		if ($node->name instanceof Node\Identifier) {
			$methodNameScopes = [$node->name->name => $scope];
		} else {
			$nameType = $scope->getType($node->name);
			$methodNameScopes = [];
			foreach ($nameType->getConstantStrings() as $constantString) {
				$name = $constantString->getValue();
				$methodNameScopes[$name] = $scope->filterByTruthyValue(new Identical($node->name, new String_($name)));
			}
		}

		foreach ($methodNameScopes as $methodName => $methodScope) {
			$errors = array_merge($errors, $this->processSingleMethodCall($methodScope, $node, $methodName));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleMethodCall(Scope $scope, MethodCall $node, string $methodName): array
	{
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
				$methodReflection->getVariants(),
				$methodReflection->getNamedArgumentsVariants(),
			),
			$scope,
			$declaringClass->isBuiltin(),
			$node,
			'method',
			$methodReflection->acceptsNamedArguments(),
			'Method ' . $messagesMethodName . ' invoked with %d parameter, %d required.',
			'Method ' . $messagesMethodName . ' invoked with %d parameters, %d required.',
			'Method ' . $messagesMethodName . ' invoked with %d parameter, at least %d required.',
			'Method ' . $messagesMethodName . ' invoked with %d parameters, at least %d required.',
			'Method ' . $messagesMethodName . ' invoked with %d parameter, %d-%d required.',
			'Method ' . $messagesMethodName . ' invoked with %d parameters, %d-%d required.',
			'%s of method ' . $messagesMethodName . ' expects %s, %s given.',
			'Result of method ' . $messagesMethodName . ' (void) is used.',
			'%s of method ' . $messagesMethodName . ' is passed by reference, so it expects variables only.',
			'Unable to resolve the template type %s in call to method ' . $messagesMethodName,
			'Missing parameter $%s in call to method ' . $messagesMethodName . '.',
			'Unknown parameter $%s in call to method ' . $messagesMethodName . '.',
			'Return type of call to method ' . $messagesMethodName . ' contains unresolvable type.',
			'%s of method ' . $messagesMethodName . ' contains unresolvable type.',
			'Method ' . $messagesMethodName . ' invoked with %s, but it\'s not allowed because of @no-named-arguments.',
		));
	}

}
