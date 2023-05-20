<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Reflection\ReflectionProvider;
use function sprintf;
use function strtolower;

class ClassCaseSensitivityCheck
{

	public function __construct(private ReflectionProvider $reflectionProvider, private bool $checkInternalClassCaseSensitivity)
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
			$className = $pair->getClassName();
			if (!$this->reflectionProvider->hasClass($className)) {
				continue;
			}
			$classReflection = $this->reflectionProvider->getClass($className);
			if (!$this->checkInternalClassCaseSensitivity && $classReflection->isBuiltin()) {
				continue; // skip built-in classes
			}
			$realClassName = $classReflection->getName();
			if (strtolower($realClassName) !== strtolower($className)) {
				continue; // skip class alias
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
				->line($pair->getNode()->getLine())
				->build();
		}

		return $errors;
	}

}
