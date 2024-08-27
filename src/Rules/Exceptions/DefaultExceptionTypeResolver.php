<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use Nette\Utils\Strings;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use function count;

/**
 * @api
 * @final
 */
class DefaultExceptionTypeResolver implements ExceptionTypeResolver
{

	/**
	 * @param string[] $uncheckedExceptionRegexes
	 * @param string[] $uncheckedExceptionClasses
	 * @param string[] $checkedExceptionRegexes
	 * @param string[] $checkedExceptionClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private array $uncheckedExceptionRegexes,
		private array $uncheckedExceptionClasses,
		private array $checkedExceptionRegexes,
		private array $checkedExceptionClasses,
	)
	{
	}

	public function isCheckedException(string $className, Scope $scope): bool
	{
		foreach ($this->uncheckedExceptionRegexes as $regex) {
			if (Strings::match($className, $regex) !== null) {
				return false;
			}
		}

		foreach ($this->uncheckedExceptionClasses as $uncheckedExceptionClass) {
			if ($className === $uncheckedExceptionClass) {
				return false;
			}
		}

		if (!$this->reflectionProvider->hasClass($className)) {
			return $this->isCheckedExceptionInternal($className);
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		foreach ($this->uncheckedExceptionClasses as $uncheckedExceptionClass) {
			if ($classReflection->getName() === $uncheckedExceptionClass) {
				return false;
			}

			if (!$classReflection->isSubclassOf($uncheckedExceptionClass)) {
				continue;
			}

			return false;
		}

		return $this->isCheckedExceptionInternal($className);
	}

	private function isCheckedExceptionInternal(string $className): bool
	{
		foreach ($this->checkedExceptionRegexes as $regex) {
			if (Strings::match($className, $regex) !== null) {
				return true;
			}
		}

		foreach ($this->checkedExceptionClasses as $checkedExceptionClass) {
			if ($className === $checkedExceptionClass) {
				return true;
			}
		}

		if (!$this->reflectionProvider->hasClass($className)) {
			return count($this->checkedExceptionRegexes) === 0 && count($this->checkedExceptionClasses) === 0;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		foreach ($this->checkedExceptionClasses as $checkedExceptionClass) {
			if ($classReflection->getName() === $checkedExceptionClass) {
				return true;
			}

			if (!$classReflection->isSubclassOf($checkedExceptionClass)) {
				continue;
			}

			return true;
		}

		return count($this->checkedExceptionRegexes) === 0 && count($this->checkedExceptionClasses) === 0;
	}

}
