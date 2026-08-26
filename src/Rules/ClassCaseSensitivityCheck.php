<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Name;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\UseAliasVisitor;
use PHPStan\Reflection\ReflectionProvider;
use function count;
use function implode;
use function sprintf;
use function strtolower;

#[AutowiredService]
final class ClassCaseSensitivityCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private bool $checkInternalClassCaseSensitivity,
	)
	{
	}

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return list<IdentifierRuleError>
	 */
	public function checkClassNames(array $pairs): array
	{
		$errors = [];
		foreach ($pairs as $pair) {
			$className = $this->getClassNameAsWritten($pair);
			if (!$this->reflectionProvider->hasClass($className)) {
				continue;
			}
			$classReflection = $this->reflectionProvider->getClass($className);
			if (!$this->checkInternalClassCaseSensitivity && $classReflection->isBuiltin()) {
				continue; // skip built-in classes
			}
			$realClassName = $classReflection->getName();
			if (strtolower($realClassName) !== strtolower($className)) {
				continue; // skip class_alias() where the alias is a completely different name
			}
			if ($pair->getNode()->getAttribute(UseAliasVisitor::ATTRIBUTE_NAME) === true) {
				continue;
			}
			if ($realClassName === $className) {
				continue;
			}

			$typeName = $classReflection->getClassTypeDescription();
			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s referenced with incorrect case: %s.',
				$typeName,
				$realClassName,
				$className,
			))
				->identifier(sprintf('%s.nameCase', strtolower($typeName)))
				->line($pair->getNode()->getStartLine())
				->build();
		}

		return $errors;
	}

	/**
	 * @return non-empty-string
	 */
	private function getClassNameAsWritten(ClassNameNodePair $pair): string
	{
		$className = $pair->getClassName();
		$node = $pair->getNode();
		if (!$node instanceof Name || $node->toString() !== $className) {
			return $className;
		}

		return self::getNameAsWritten($node);
	}

	/**
	 * NameResolver replaces a single-part imported name entirely with the use statement's
	 * spelling, so the case the user wrote survives only in the originalName attribute.
	 * Multi-part names keep the written case of everything after the first part.
	 *
	 * @return non-empty-string
	 */
	public static function getNameAsWritten(Name $node): string
	{
		$resolvedName = $node->toString();
		$originalName = $node->getAttribute('originalName');
		if (
			!$originalName instanceof Name
			|| $originalName instanceof Name\FullyQualified
			|| $originalName instanceof Name\Relative
		) {
			return $resolvedName;
		}

		$originalParts = $originalName->getParts();
		if (count($originalParts) !== 1) {
			return $resolvedName;
		}

		$resolvedParts = $node->getParts();
		$resolvedParts[count($resolvedParts) - 1] = $originalParts[0];

		return implode('\\', $resolvedParts);
	}

}
