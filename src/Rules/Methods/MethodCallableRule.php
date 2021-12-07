<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<MethodCallableNode>
 */
class MethodCallableRule implements Rule
{

	private MethodCallCheck $methodCallCheck;

	private PhpVersion $phpVersion;

	public function __construct(MethodCallCheck $methodCallCheck, PhpVersion $phpVersion)
	{
		$this->methodCallCheck = $methodCallCheck;
		$this->phpVersion = $phpVersion;
	}

	public function getNodeType(): string
	{
		return MethodCallableNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->supportsFirstClassCallables()) {
			return [
				RuleErrorBuilder::message('First-class callables are supported only on PHP 8.1 and later.')
					->nonIgnorable()
					->build(),
			];
		}

		$methodName = $node->getName();
		if (!$methodName instanceof Node\Identifier) {
			return [];
		}

		$methodNameName = $methodName->toString();

		[$errors, $methodReflection] = $this->methodCallCheck->check($scope, $methodNameName, $node->getVar());
		if ($methodReflection === null) {
			return $errors;
		}

		$declaringClass = $methodReflection->getDeclaringClass();
		if ($declaringClass->hasNativeMethod($methodNameName)) {
			return $errors;
		}

		$messagesMethodName = SprintfHelper::escapeFormatString($declaringClass->getDisplayName() . '::' . $methodReflection->getName() . '()');

		$errors[] = RuleErrorBuilder::message(sprintf('Creating callable from a non-native method %s.', $messagesMethodName))->build();

		return $errors;
	}

}
