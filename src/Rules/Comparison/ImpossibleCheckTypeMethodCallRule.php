<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\MethodCall>
 */
class ImpossibleCheckTypeMethodCallRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper */
	private $impossibleCheckTypeHelper;

	/** @var bool */
	private $checkAlwaysTrueCheckTypeFunctionCall;

	public function __construct(
		ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
		bool $checkAlwaysTrueCheckTypeFunctionCall
	)
	{
		$this->impossibleCheckTypeHelper = $impossibleCheckTypeHelper;
		$this->checkAlwaysTrueCheckTypeFunctionCall = $checkAlwaysTrueCheckTypeFunctionCall;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node);
		if ($isAlways === null) {
			return [];
		}

		if (!$isAlways) {
			$method = $this->getMethod($node->var, $node->name->name, $scope);
			return [
				RuleErrorBuilder::message(sprintf(
					'Call to method %s::%s()%s will always evaluate to false.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->args)
				))->build(),
			];
		} elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
			$method = $this->getMethod($node->var, $node->name->name, $scope);
			return [
				RuleErrorBuilder::message(sprintf(
					'Call to method %s::%s()%s will always evaluate to true.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->args)
				))->build(),
			];
		}

		return [];
	}

	private function getMethod(
		Expr $var,
		string $methodName,
		Scope $scope
	): MethodReflection
	{
		$calledOnType = $scope->getType($var);
		if (!$calledOnType->hasMethod($methodName)->yes()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $calledOnType->getMethod($methodName, $scope);
	}

}
