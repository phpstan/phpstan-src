<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Constants\OverrideAttributeOnConstantCheck;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\EnumCase>
 */
#[RegisteredRule(level: 0)]
final class OverridingEnumCaseRule implements Rule
{

	public function __construct(private OverrideAttributeOnConstantCheck $overrideAttributeCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\EnumCase::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass() || !$this->overrideAttributeCheck->isNeeded($scope, $node->attrGroups)) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if (!$classReflection->isEnum()) {
			return [];
		}

		$caseName = $node->name->toString();

		return $this->overrideAttributeCheck->check(
			$scope,
			$classReflection,
			$caseName,
			$this->findPrototype($classReflection, $caseName),
			$node->attrGroups,
			$node,
			true,
		);
	}

	/**
	 * An enum case can only override a constant of an implemented interface.
	 */
	private function findPrototype(ClassReflection $classReflection, string $caseName): ?ClassConstantReflection
	{
		foreach ($classReflection->getImmediateInterfaces() as $immediateInterface) {
			if ($immediateInterface->hasConstant($caseName)) {
				return $immediateInterface->getConstant($caseName);
			}
		}

		return null;
	}

}
