<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
class ExistingClassInClassExtendsRule implements Rule
{

	public function __construct(
		private ClassNameCheck $classCheck,
		private ReflectionProvider $reflectionProvider,
	)
	{
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
		$messages = $this->classCheck->checkClassNames([new ClassNameNodePair($extendedClassName, $node->extends)]);
		$currentClassName = null;
		if (isset($node->namespacedName)) {
			$currentClassName = (string) $node->namespacedName;
		}
		if (!$this->reflectionProvider->hasClass($extendedClassName)) {
			if (!$scope->isInClassExists($extendedClassName)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends unknown class %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$extendedClassName,
				))
					->identifier('class.notFound')
					->nonIgnorable()
					->discoveringSymbolsTip()
					->build();
			}
		} else {
			$reflection = $this->reflectionProvider->getClass($extendedClassName);
			if ($reflection->isInterface()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends interface %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$reflection->getDisplayName(),
				))
					->identifier('class.extendsInterface')
					->nonIgnorable()
					->build();
			} elseif ($reflection->isTrait()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends trait %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$reflection->getDisplayName(),
				))
					->identifier('class.extendsTrait')
					->nonIgnorable()
					->build();
			} elseif ($reflection->isEnum()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends enum %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$reflection->getDisplayName(),
				))
					->identifier('class.extendsEnum')
					->nonIgnorable()
					->build();
			} elseif ($reflection->isFinalByKeyword()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends final class %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$reflection->getDisplayName(),
				))
					->identifier('class.extendsFinal')
					->nonIgnorable()
					->build();
			} elseif ($reflection->isFinal()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends @final class %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$reflection->getDisplayName(),
				))
					->identifier('class.extendsFinalByPhpDoc')
					->build();
			}

			if ($reflection->isClass()) {
				if ($node->isReadonly()) {
					if (!$reflection->isReadOnly()) {
						$messages[] = RuleErrorBuilder::message(sprintf(
							'%s extends non-readonly class %s.',
							$currentClassName !== null ? sprintf('Readonly class %s', $currentClassName) : 'Anonymous readonly class',
							$reflection->getDisplayName(),
						))->nonIgnorable()->build();
					}
				} elseif ($reflection->isReadOnly()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'%s extends readonly class %s.',
						$currentClassName !== null ? sprintf('Non-readonly class %s', $currentClassName) : 'Anonymous non-readonly class',
						$reflection->getDisplayName(),
					))->nonIgnorable()->build();
				}
			}
		}

		return $messages;
	}

}
