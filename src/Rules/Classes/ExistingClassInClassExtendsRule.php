<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
class ExistingClassInClassExtendsRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private ReflectionProvider $reflectionProvider;

	public function __construct(
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->extends === null) {
			return [];
		}
		$extendedClassName = (string) $node->extends;
		$messages = $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($extendedClassName, $node->extends)]);
		if (
			!$this->reflectionProvider->hasClass($extendedClassName)
			&& !$scope->isInClassExists($extendedClassName)
		) {
			$currentClassName = null;
			if (isset($node->namespacedName)) {
				$currentClassName = (string) $node->namespacedName;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s extends unknown class %s.',
				$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
				$extendedClassName
			))->build();
		}

		return $messages;
	}

}
