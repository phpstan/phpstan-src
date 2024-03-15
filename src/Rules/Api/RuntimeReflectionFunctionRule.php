<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_keys;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class RuntimeReflectionFunctionRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if (!in_array($functionReflection->getName(), [
			'is_a',
			'is_subclass_of',
			'get_declared_classes',
			'class_parents',
			'class_implements',
			'class_uses',
		], true)) {
			return [];
		}

		if (!$scope->isInClass()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		$hasPhpStanInterface = false;
		foreach (array_keys($classReflection->getInterfaces()) as $interfaceName) {
			if (!str_starts_with($interfaceName, 'PHPStan\\')) {
				continue;
			}

			$hasPhpStanInterface = true;
		}

		if (!$hasPhpStanInterface) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Function %s() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.', $functionReflection->getName()),
			)->build(),
		];
	}

}
