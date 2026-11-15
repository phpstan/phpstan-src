<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Reflection\PartialFunctionApplicationParameters;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;

/**
 * @implements Rule<MethodCallableNode>
 */
#[RegisteredRule(level: 0)]
final class MethodCallableRule implements Rule
{

	public function __construct(
		private MethodCallCheck $methodCallCheck,
		private FunctionCallParametersCheck $parametersCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return MethodCallableNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$node->getOriginalNode()->isFirstClassCallable()) {
			if (!$scope->getPhpVersion()->supportsPartialFunctionApplication()->yes()) {
				return [
					RuleErrorBuilder::message('Partial function application is supported only on PHP 8.6 and later.')
						->nonIgnorable()
						->identifier('callable.notSupported')
						->build(),
				];
			}
			// compile errors, even when the callee is unknown
			$syntaxErrors = $this->parametersCheck->checkPartialApplicationSyntax($node->getOriginalNode());
			if ($syntaxErrors !== []) {
				return $syntaxErrors;
			}
		} elseif (!$scope->getPhpVersion()->supportsFirstClassCallables()->yes()) {
			return [
				RuleErrorBuilder::message('First-class callables are supported only on PHP 8.1 and later.')
					->nonIgnorable()
					->identifier('callable.notSupported')
					->build(),
			];
		}

		if ($node->getOriginalNode()->getAttribute('virtualNullsafeMethodCall') === true) {
			return [
				RuleErrorBuilder::message('Cannot combine nullsafe operator with Closure creation.')
					->nonIgnorable()
					->identifier('callable.notSupported')
					->build(),
			];
		}

		$methodName = $node->getName();
		if (!$methodName instanceof Node\Identifier) {
			return [];
		}

		$methodNameName = $methodName->toString();

		[$errors, $methodReflection] = $this->methodCallCheck->check($scope, $methodNameName, $node->getVar(), $node->getName());
		if ($methodReflection === null || $node->getOriginalNode()->isFirstClassCallable()) {
			return $errors;
		}

		$declaringClass = $methodReflection->getDeclaringClass();

		return array_merge($errors, $this->parametersCheck->checkPartialApplication(
			PartialFunctionApplicationParameters::selectVariant($node->getArgs(), $methodReflection->getVariants(), $methodReflection->getNamedArgumentsVariants()),
			$scope,
			$declaringClass->isBuiltin(),
			$node->getOriginalNode(),
			'method',
			$methodReflection->acceptsNamedArguments(),
			'method ' . SprintfHelper::escapeFormatString($declaringClass->getDisplayName() . '::' . $methodReflection->getName() . '()'),
			$methodReflection,
		));
	}

}
