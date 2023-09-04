<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Interface_>
 */
class ExistingClassesInInterfaceExtendsRule implements Rule
{

	public function __construct(
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Interface_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = $this->classCaseSensitivityCheck->checkClassNames(
			array_map(static fn (Node\Name $interfaceName): ClassNameNodePair => new ClassNameNodePair((string) $interfaceName, $interfaceName), $node->extends),
		);

		$currentInterfaceName = (string) $node->namespacedName;
		foreach ($node->extends as $extends) {
			$extendedInterfaceName = (string) $extends;
			if (!$this->reflectionProvider->hasClass($extendedInterfaceName)) {
				if (!$scope->isInClassExists($extendedInterfaceName)) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends unknown interface %s.',
						$currentInterfaceName,
						$extendedInterfaceName,
					))
						->identifier('interface.notFound')
						->nonIgnorable()
						->discoveringSymbolsTip($extendedInterfaceName)
						->build();
				}
			} else {
				$reflection = $this->reflectionProvider->getClass($extendedInterfaceName);
				if ($reflection->isClass()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends class %s.',
						$currentInterfaceName,
						$reflection->getDisplayName(),
					))
						->identifier('interfaceExtends.class')
						->nonIgnorable()
						->build();
				} elseif ($reflection->isTrait()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends trait %s.',
						$currentInterfaceName,
						$reflection->getDisplayName(),
					))
						->identifier('interfaceExtends.trait')
						->nonIgnorable()
						->build();
				} elseif ($reflection->isEnum()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Interface %s extends enum %s.',
						$currentInterfaceName,
						$reflection->getDisplayName(),
					))
						->identifier('interfaceExtends.enum')
						->nonIgnorable()
						->build();
				}
			}

			return $messages;
		}

		return $messages;
	}

}
