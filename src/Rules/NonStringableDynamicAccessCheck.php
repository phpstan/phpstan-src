<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * Checks whether the name of a dynamically accessed variable or member
 * (`$$name`, `$obj->{$name}`, `$obj->{$name}()`, `Foo::{$name}()`,
 * `Foo::${$name}`, `Foo::{$name}`) can actually be used as a name at runtime.
 *
 * Gated behind the `checkNonStringableDynamicAccess` feature toggle.
 */
#[AutowiredService]
final class NonStringableDynamicAccessCheck
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		#[AutowiredParameter(ref: '%featureToggles.checkNonStringableDynamicAccess%')]
		private bool $checkNonStringableDynamicAccess,
	)
	{
	}

	/**
	 * For names that PHP casts to string at runtime (variable variables,
	 * property and static property names) objects implementing __toString are
	 * accepted. Returns the offending name type to report, or null when the
	 * name is usable.
	 */
	public function checkStringCastableName(Scope $scope, Expr $name): ?Type
	{
		if (!$this->checkNonStringableDynamicAccess) {
			return null;
		}

		$nameType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$name,
			'',
			static fn (Type $type) => !$type->toString() instanceof ErrorType && $type->toString()->isString()->yes(),
		)->getType();

		if (
			!$nameType instanceof ErrorType
			&& ($nameType->toString() instanceof ErrorType || !$nameType->toString()->isString()->yes())
		) {
			return $scope->getType($name);
		}

		return null;
	}

	/**
	 * For names that must be actual strings (method, static method and class
	 * constant names) objects implementing __toString are not accepted.
	 * Returns the offending name type to report, or null when the name is usable.
	 */
	public function checkStringName(Scope $scope, Expr $name): ?Type
	{
		if (!$this->checkNonStringableDynamicAccess) {
			return null;
		}

		$nameType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$name,
			'',
			static fn (Type $type) => $type->isString()->yes(),
		)->getType();

		if (!$nameType instanceof ErrorType && !$nameType->isString()->yes()) {
			return $nameType;
		}

		return null;
	}

}
