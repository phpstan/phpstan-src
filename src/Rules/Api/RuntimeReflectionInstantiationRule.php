<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionExtension;
use ReflectionFunction;
use ReflectionGenerator;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionZendExtension;
use function array_keys;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<Node\Expr\New_>
 */
final class RuntimeReflectionInstantiationRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			return [];
		}

		$className = $scope->resolveName($node->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!in_array($classReflection->getName(), [
			ReflectionMethod::class,
			ReflectionClass::class,
			ReflectionClassConstant::class,
			'ReflectionEnum',
			'ReflectionEnumBackedCase',
			ReflectionZendExtension::class,
			ReflectionExtension::class,
			ReflectionFunction::class,
			ReflectionObject::class,
			ReflectionParameter::class,
			ReflectionProperty::class,
			ReflectionGenerator::class,
			'ReflectionFiber',
		], true)) {
			return [];
		}

		if (!$scope->isInClass()) {
			return [];
		}

		$scopeClassReflection = $scope->getClassReflection();
		$hasPhpStanInterface = false;
		foreach (array_keys($scopeClassReflection->getInterfaces()) as $interfaceName) {
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
				sprintf('Creating new %s is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.', $classReflection->getName()),
			)->identifier('phpstanApi.runtimeReflection')->build(),
		];
	}

}
