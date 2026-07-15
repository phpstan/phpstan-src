<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

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
	 * accepted.
	 *
	 * @param Name|Expr|null $classNode the node whose name/type fills the leading `%s` placeholder, or null when the message has none
	 * @return list<IdentifierRuleError>
	 */
	public function checkStringCastableName(Scope $scope, Expr $name, string $messageFormat, $classNode, string $identifier): array
	{
		if (!$this->checkNonStringableDynamicAccess) {
			return [];
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
			return [$this->buildError($scope, $name, $scope->getType($name), $messageFormat, $classNode, $identifier)];
		}

		return [];
	}

	/**
	 * For names that must be actual strings (method, static method and class
	 * constant names) objects implementing __toString are not accepted.
	 *
	 * @param Name|Expr|null $classNode the node whose name/type fills the leading `%s` placeholder, or null when the message has none
	 * @return list<IdentifierRuleError>
	 */
	public function checkStringName(Scope $scope, Expr $name, string $messageFormat, $classNode, string $identifier): array
	{
		if (!$this->checkNonStringableDynamicAccess) {
			return [];
		}

		$nameType = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$name,
			'',
			static fn (Type $type) => $type->isString()->yes(),
		)->getType();

		if (!$nameType instanceof ErrorType && !$nameType->isString()->yes()) {
			return [$this->buildError($scope, $name, $nameType, $messageFormat, $classNode, $identifier)];
		}

		return [];
	}

	/**
	 * @param Name|Expr|null $classNode
	 */
	private function buildError(Scope $scope, Expr $name, Type $nameType, string $messageFormat, $classNode, string $identifier): IdentifierRuleError
	{
		$messageArgs = [];
		if ($classNode !== null) {
			$messageArgs[] = $classNode instanceof Name
				? $scope->resolveName($classNode)
				: $scope->getType($classNode)->describe(VerbosityLevel::typeOnly());
		}
		$messageArgs[] = $nameType->describe(VerbosityLevel::precise());

		return RuleErrorBuilder::message(sprintf($messageFormat, ...$messageArgs))
			->line($name->getStartLine())
			->identifier($identifier)
			->build();
	}

}
