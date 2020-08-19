<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassConstantsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassConstantsNode>
 */
class UnusedPrivateConstantRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassConstantsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_) {
			return [];
		}
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();

		$constants = [];
		foreach ($node->getConstants() as $constant) {
			if (!$constant->isPrivate()) {
				continue;
			}
			foreach ($constant->consts as $const) {
				$constants[$const->name->toString()] = $const;
			}
		}

		foreach ($node->getFetches() as $fetch) {
			$fetchNode = $fetch->getNode();
			if (!$fetchNode->class instanceof Node\Name) {
				continue;
			}
			if (!$fetchNode->name instanceof Node\Identifier) {
				continue;
			}
			$fetchScope = $fetch->getScope();
			$fetchedOnClass = $fetchScope->resolveName($fetchNode->class);
			if ($fetchedOnClass !== $classReflection->getName()) {
				continue;
			}
			unset($constants[$fetchNode->name->toString()]);
		}

		$errors = [];
		foreach ($constants as $constantName => $constantNode) {
			$errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s is unused.', $classReflection->getDisplayName(), $constantName))->line($constantNode->getLine())->build();
		}

		return $errors;
	}

}
