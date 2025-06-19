<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Interface_>
 */
#[RegisteredRule(level: 0)]
final class ExistingClassesInInterfaceExtendsRule implements Rule
{

	public function __construct(
		private ClassNameCheck $classCheck,
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter(ref: '%tips.discoveringSymbols%')]
		private bool $discoveringSymbolsTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Interface_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$currentInterfaceName = (string) $node->namespacedName;
		$messages = $this->classCheck->checkClassNames(
			$scope,
			array_map(static fn (Node\Name $interfaceName): ClassNameNodePair => new ClassNameNodePair((string) $interfaceName, $interfaceName), $node->extends),
			ClassNameUsageLocation::from(ClassNameUsageLocation::INTERFACE_EXTENDS, [
				'currentClassName' => $currentInterfaceName,
			]),
		);

		foreach ($node->extends as $extends) {
			$extendedInterfaceName = (string) $extends;
			if (!$this->reflectionProvider->hasClass($extendedInterfaceName)) {
				if (!$scope->isInClassExists($extendedInterfaceName)) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'Interface %s extends unknown interface %s.',
						$currentInterfaceName,
						$extendedInterfaceName,
					))
						->identifier('interface.notFound')
						->nonIgnorable();

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$messages[] = $errorBuilder->build();
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
