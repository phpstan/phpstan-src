<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class DuplicateClassDeclarationRule implements Rule
{

	public function __construct(private Reflector $reflector, private RelativePathHelper $relativePathHelper)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$thisClass = $node->getClassReflection();
		$className = $thisClass->getName();
		$allClasses = $this->reflector->reflectAllClasses();
		$filteredClasses = [];
		foreach ($allClasses as $reflectionClass) {
			if ($reflectionClass->getName() !== $className) {
				continue;
			}

			$filteredClasses[] = $reflectionClass;
		}

		if (count($filteredClasses) < 2) {
			return [];
		}

		$filteredClasses = array_filter($filteredClasses, static fn (ReflectionClass $class) => $class->getStartLine() !== $thisClass->getNativeReflection()->getStartLine());

		$identifierType = 'class';
		if ($thisClass->isInterface()) {
			$identifierType = 'interface';
		} elseif ($thisClass->isEnum()) {
			$identifierType = 'enum';
		}

		return [
			RuleErrorBuilder::message(sprintf(
				"Class %s declared multiple times:\n%s",
				$thisClass->getDisplayName(),
				implode("\n", array_map(fn (ReflectionClass $class) => sprintf('- %s:%d', $this->relativePathHelper->getRelativePath($class->getFileName() ?? 'unknown'), $class->getStartLine()), $filteredClasses)),
			))->identifier(sprintf('%s.duplicate', $identifierType))->build(),
		];
	}

}
