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

	private bool $checkFinalByPhpDocTag;

	public function __construct(
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		ReflectionProvider $reflectionProvider,
		bool $checkFinalByPhpDocTag = false
	)
	{
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->reflectionProvider = $reflectionProvider;
		$this->checkFinalByPhpDocTag = $checkFinalByPhpDocTag;
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
		$currentClassName = null;
		if (isset($node->namespacedName)) {
			$currentClassName = (string) $node->namespacedName;
		}
		if (!$this->reflectionProvider->hasClass($extendedClassName)) {
			if (!$scope->isInClassExists($extendedClassName)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends unknown class %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$extendedClassName
				))->nonIgnorable()->discoveringSymbolsTip()->build();
			}
		} else {
			$reflection = $this->reflectionProvider->getClass($extendedClassName);
			if ($reflection->isInterface()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends interface %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$extendedClassName
				))->nonIgnorable()->build();
			} elseif ($reflection->isTrait()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends trait %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$extendedClassName
				))->nonIgnorable()->build();
			} elseif ($reflection->isFinalByKeyword()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends final class %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$extendedClassName
				))->nonIgnorable()->build();
			} elseif ($this->checkFinalByPhpDocTag && $reflection->isFinal()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s extends @final class %s.',
					$currentClassName !== null ? sprintf('Class %s', $currentClassName) : 'Anonymous class',
					$extendedClassName
				))->build();
			}
		}

		return $messages;
	}

}
