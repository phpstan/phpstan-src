<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
class CallStaticMethodsRule implements Rule
{

	public function __construct(
		private StaticMethodCallCheck $methodCallCheck,
		private FunctionCallParametersCheck $parametersCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $node->name->name;

		[$errors, $method] = $this->methodCallCheck->check($scope, $methodName, $node->class);
		if ($method === null) {
			return $errors;
		}

		$displayMethodName = SprintfHelper::escapeFormatString(sprintf(
			'%s %s',
			$method->isStatic() ? 'Static method' : 'Method',
			$method->getDeclaringClass()->getDisplayName() . '::' . $method->getName() . '()',
		));
		$lowercasedMethodName = SprintfHelper::escapeFormatString(sprintf(
			'%s %s',
			$method->isStatic() ? 'static method' : 'method',
			$method->getDeclaringClass()->getDisplayName() . '::' . $method->getName() . '()',
		));

		$errors = array_merge($errors, $this->parametersCheck->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$method->getVariants(),
			),
			$scope,
			$method->getDeclaringClass()->isBuiltin(),
			$node,
			[
				$displayMethodName . ' invoked with %d parameter, %d required.',
				$displayMethodName . ' invoked with %d parameters, %d required.',
				$displayMethodName . ' invoked with %d parameter, at least %d required.',
				$displayMethodName . ' invoked with %d parameters, at least %d required.',
				$displayMethodName . ' invoked with %d parameter, %d-%d required.',
				$displayMethodName . ' invoked with %d parameters, %d-%d required.',
				'Parameter %s of ' . $lowercasedMethodName . ' expects %s, %s given.',
				'Result of ' . $lowercasedMethodName . ' (void) is used.',
				'Parameter %s of ' . $lowercasedMethodName . ' is passed by reference, so it expects variables only.',
				'Unable to resolve the template type %s in call to method ' . $lowercasedMethodName,
				'Missing parameter $%s in call to ' . $lowercasedMethodName . '.',
				'Unknown parameter $%s in call to ' . $lowercasedMethodName . '.',
				'Return type of call to ' . $lowercasedMethodName . ' contains unresolvable type.',
				'Parameter %s of ' . $lowercasedMethodName . ' contains unresolvable type.',
			],
			'staticMethod',
		));

		return $errors;
	}

}
