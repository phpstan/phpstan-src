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
 * @implements Rule<Node\Stmt\Enum_>
 */
#[RegisteredRule(level: 0)]
final class ExistingClassesInEnumImplementsRule implements Rule
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
		return Node\Stmt\Enum_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$currentEnumName = (string) $node->namespacedName;
		$messages = $this->classCheck->checkClassNames(
			$scope,
			array_map(static fn (Node\Name $interfaceName): ClassNameNodePair => new ClassNameNodePair((string) $interfaceName, $interfaceName), $node->implements),
			ClassNameUsageLocation::from(ClassNameUsageLocation::ENUM_IMPLEMENTS, [
				'currentClassName' => $currentEnumName,
			]),
		);

		foreach ($node->implements as $implements) {
			$implementedClassName = (string) $implements;
			if (!$this->reflectionProvider->hasClass($implementedClassName)) {
				if (!$scope->isInClassExists($implementedClassName)) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'Enum %s implements unknown interface %s.',
						$currentEnumName,
						$implementedClassName,
					))
						->identifier('interface.notFound')
						->nonIgnorable();

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$messages[] = $errorBuilder->build();
				}
			} else {
				$reflection = $this->reflectionProvider->getClass($implementedClassName);
				if ($reflection->isClass()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Enum %s implements class %s.',
						$currentEnumName,
						$reflection->getDisplayName(),
					))
						->identifier('enumImplements.class')
						->nonIgnorable()
						->build();
				} elseif ($reflection->isTrait()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Enum %s implements trait %s.',
						$currentEnumName,
						$reflection->getDisplayName(),
					))
						->identifier('enumImplements.trait')
						->nonIgnorable()
						->build();
				} elseif ($reflection->isEnum()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Enum %s implements enum %s.',
						$currentEnumName,
						$reflection->getDisplayName(),
					))
						->identifier('enumImplements.enum')
						->nonIgnorable()
						->build();
				}
			}
		}

		return $messages;
	}

}
