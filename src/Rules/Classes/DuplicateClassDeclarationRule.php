<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\File\RelativePathHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassNode>
 */
#[ValidatesStubFiles]
final class DuplicateClassDeclarationRule implements Rule
{

	/** @var array<class-string|trait-string, list<ReflectionClass>>|null */
	private ?array $classMap = null;

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

		// this rule runs at the very end of the analysis,
		// so all classes already have been discovered at this point.
		if ($this->classMap === null) {
			$this->classMap = [];

			$allClasses = $this->reflector->reflectAllClasses();
			foreach ($allClasses as $reflectionClass) {
				$reflectionClassName = $reflectionClass->getName();
				if (!isset($this->classMap[$reflectionClassName])) {
					$this->classMap[$reflectionClassName] = [];
				}
				$this->classMap[$reflectionClassName][] = $reflectionClass;
			}
		}

		if (!isset($this->classMap[$className]) || count($this->classMap[$className]) < 2) {
			return [];
		}

		$filteredClasses = array_filter($this->classMap[$className], static fn (ReflectionClass $class) => $class->getStartLine() !== $thisClass->getNativeReflection()->getStartLine());

		$identifierType = strtolower($thisClass->getClassTypeDescription());

		return [
			RuleErrorBuilder::message(sprintf(
				"Class %s declared multiple times:\n%s",
				$thisClass->getDisplayName(),
				implode("\n", array_map(fn (ReflectionClass $class) => sprintf('- %s:%d', $this->relativePathHelper->getRelativePath($class->getFileName() ?? 'unknown'), $class->getStartLine()), $filteredClasses)),
			))->identifier(sprintf('%s.duplicate', $identifierType))->build(),
		];
	}

}
