<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use Nette\Utils\Strings;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use function count;

/**
 * @api
 */
#[AutowiredService(as: DefaultExceptionTypeResolver::class)]
final class DefaultExceptionTypeResolver implements ExceptionTypeResolver
{

	/**
	 * @param string[] $uncheckedExceptionRegexes
	 * @param string[] $uncheckedExceptionClasses
	 * @param string[] $checkedExceptionRegexes
	 * @param string[] $checkedExceptionClasses
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter(ref: '%exceptions.uncheckedExceptionRegexes%')]
		private array $uncheckedExceptionRegexes,
		#[AutowiredParameter(ref: '%exceptions.uncheckedExceptionClasses%')]
		private array $uncheckedExceptionClasses,
		#[AutowiredParameter(ref: '%exceptions.checkedExceptionRegexes%')]
		private array $checkedExceptionRegexes,
		#[AutowiredParameter(ref: '%exceptions.checkedExceptionClasses%')]
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
			if (!$classReflection->is($uncheckedExceptionClass)) {
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
			if (!$classReflection->is($checkedExceptionClass)) {
				continue;
			}

			return true;
		}

		return count($this->checkedExceptionRegexes) === 0 && count($this->checkedExceptionClasses) === 0;
	}

}
