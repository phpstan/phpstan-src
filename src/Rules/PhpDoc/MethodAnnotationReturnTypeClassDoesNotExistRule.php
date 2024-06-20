<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class MethodAnnotationReturnTypeClassDoesNotExistRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			return [];
		}
		$classReflection = $scope->getClassReflection();
		$errors = [];
		foreach ($classReflection->getMethodTags() as $methodName => $methodTag) {
			$type = $methodTag->getReturnType();
			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errors[] = RuleErrorBuilder::message(
						sprintf(
							'PHPDoc tag @method %s() has invalid return type "%s", class does not exist',
							$methodName,
							$class,
						),
					)->build();
				}
				$errors = array_merge(
					$errors,
					$this->classCaseSensitivityCheck->checkClassNames([
						new ClassNameNodePair($class, $node),
					]),
				);
			}
		}

		return $errors;
	}

}
