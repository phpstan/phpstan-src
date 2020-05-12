<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

class ClassCaseSensitivityCheck
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return RuleError[]
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
			if ($classReflection->getFileName() === false) {
				continue; // skip built-in classes
			}
			$realClassName = $classReflection->getName();
			if (strtolower($realClassName) !== strtolower($className)) {
				continue; // skip class alias
			}
			if ($realClassName === $className) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s referenced with incorrect case: %s.',
				$this->getTypeName($classReflection),
				$realClassName,
				$className
			))->line($pair->getNode()->getLine())->build();
		}

		return $errors;
	}

	private function getTypeName(ClassReflection $classReflection): string
	{
		if ($classReflection->isInterface()) {
			return 'Interface';
		} elseif ($classReflection->isTrait()) {
			return 'Trait';
		}

		return 'Class';
	}

}
