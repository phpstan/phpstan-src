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
 * @implements Rule<Node\Stmt\Class_>
 */
#[RegisteredRule(level: 0)]
final class ExistingClassesInClassImplementsRule implements Rule
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
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$currentClassName = null;
		if (isset($node->namespacedName)) {
			$currentClassName = (string) $node->namespacedName;
		}

		$messages = $this->classCheck->checkClassNames(
			$scope,
			array_map(static fn (Node\Name $interfaceName): ClassNameNodePair => new ClassNameNodePair((string) $interfaceName, $interfaceName), $node->implements),
			ClassNameUsageLocation::from(ClassNameUsageLocation::CLASS_IMPLEMENTS, [
				'currentClassName' => $currentClassName,
			]),
		);

		foreach ($node->implements as $implements) {
			$implementedClassName = (string) $implements;
			if (!$this->reflectionProvider->hasClass($implementedClassName)) {
				if (!$scope->isInClassExists($implementedClassName)) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'%s implements unknown interface %s.',
						$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
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
						'%s implements class %s.',
						$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
						$reflection->getDisplayName(),
					))
						->identifier('classImplements.class')
						->nonIgnorable()
						->build();
				} elseif ($reflection->isTrait()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'%s implements trait %s.',
						$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
						$reflection->getDisplayName(),
					))
						->identifier('classImplements.trait')
						->nonIgnorable()
						->build();
				} elseif ($reflection->isEnum()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'%s implements enum %s.',
						$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
						$reflection->getDisplayName(),
					))
						->identifier('classImplements.enum')
						->nonIgnorable()
						->build();
				}
			}
		}

		return $messages;
	}

}
