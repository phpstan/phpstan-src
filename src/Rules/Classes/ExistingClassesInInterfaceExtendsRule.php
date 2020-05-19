<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Interface_>
 */
class ExistingClassesInInterfaceExtendsRule implements \PHPStan\Rules\Rule
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
		return Node\Stmt\Interface_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = $this->classCaseSensitivityCheck->checkClassNames(
			array_map(static function (Node\Name $interfaceName): ClassNameNodePair {
				return new ClassNameNodePair((string) $interfaceName, $interfaceName);
			}, $node->extends)
		);

		$currentInterfaceName = (string) $node->namespacedName;
		foreach ($node->extends as $extends) {
			$extendedInterfaceName = (string) $extends;
			if (!$this->reflectionProvider->hasClass($extendedInterfaceName)) {
				if (!$scope->isInClassExists($extendedInterfaceName)) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends unknown interface %s.',
						$currentInterfaceName,
						$extendedInterfaceName
					))->nonIgnorable()->build();
				}
			} else {
				$reflection = $this->reflectionProvider->getClass($extendedInterfaceName);
				if ($reflection->isClass()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends class %s.',
						$currentInterfaceName,
						$extendedInterfaceName
					))->nonIgnorable()->build();
				} elseif ($reflection->isTrait()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends trait %s.',
						$currentInterfaceName,
						$extendedInterfaceName
					))->nonIgnorable()->build();
				}
			}

			return $messages;
		}

		return $messages;
	}

}
