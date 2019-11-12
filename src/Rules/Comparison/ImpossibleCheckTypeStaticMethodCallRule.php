<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\StaticCall>
 */
class ImpossibleCheckTypeStaticMethodCallRule implements \PHPStan\Rules\Rule
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
		return \PhpParser\Node\Expr\StaticCall::class;
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
			$method = $this->getMethod($node->class, $node->name->name, $scope);

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to static method %s::%s()%s will always evaluate to false.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->args)
				))->build(),
			];
		} elseif ($this->checkAlwaysTrueCheckTypeFunctionCall) {
			$method = $this->getMethod($node->class, $node->name->name, $scope);

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to static method %s::%s()%s will always evaluate to true.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->args)
				))->build(),
			];
		}

		return [];
	}

	/**
	 * @param Node\Name|Expr $class
	 * @param string $methodName
	 * @param Scope $scope
	 * @return MethodReflection
	 * @throws \PHPStan\ShouldNotHappenException
	 */
	private function getMethod(
		$class,
		string $methodName,
		Scope $scope
	): MethodReflection
	{
		if ($class instanceof Node\Name) {
			$calledOnType = new ObjectType($scope->resolveName($class));
		} else {
			$calledOnType = $scope->getType($class);
		}

		if (!$calledOnType->hasMethod($methodName)->yes()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $calledOnType->getMethod($methodName, $scope);
	}

}
