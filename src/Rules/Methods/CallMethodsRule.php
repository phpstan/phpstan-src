<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\NonStringableDynamicAccessCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
#[RegisteredRule(level: 0)]
final class CallMethodsRule implements Rule
{

	public function __construct(
		private MethodCallCheck $methodCallCheck,
		private FunctionCallParametersCheck $parametersCheck,
		private NonStringableDynamicAccessCheck $nonStringableDynamicAccessCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
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

			$nonStringableNameType = $this->nonStringableDynamicAccessCheck->checkStringName($scope, $node->name);
			if ($nonStringableNameType !== null) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Method name for %s must be a string, but %s was given.',
					$scope->getType($node->var)->describe(VerbosityLevel::typeOnly()),
					$nonStringableNameType->describe(VerbosityLevel::precise()),
				))
					->line($node->name->getStartLine())
					->identifier('method.nameNotString')
					->build();
			}
		}

		foreach ($methodNameScopes as $methodName => $methodScope) {
			$errors = array_merge($errors, $this->processSingleMethodCall(
				$methodScope,
				$node,
				(string) $methodName, // @phpstan-ignore cast.useless
			));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleMethodCall(Scope&NodeCallbackInvoker&CollectedDataEmitter $scope, MethodCall $node, string $methodName): array
	{
		[$errors, $methodReflection] = $this->methodCallCheck->check($scope, $methodName, $node->var, $node->name);
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
			'Constant %s is not allowed for %s of method ' . $messagesMethodName . '.',
			'Constants %s cannot be combined for %s of method ' . $messagesMethodName . '.',
			'Combining constants with | is not allowed for %s of method ' . $messagesMethodName . '.',
			!$methodReflection->isPrivate() && !$declaringClass->isFinal() ? [
				$declaringClass->getName(),
				$methodReflection->getName(),
			] : null,
		));
	}

}
