<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function count;
use function sprintf;

/**
 * Checks the #[\Override] attribute on class constants and enum cases, shared by OverridingConstantRule and OverridingEnumCaseRule.
 */
#[AutowiredService]
final class OverrideAttributeOnClassConstantCheck
{

	public function __construct(
		private PhpVersion $phpVersion,
		#[AutowiredParameter]
		private ?bool $checkMissingOverrideConstantAttribute,
		#[AutowiredParameter]
		private ?bool $checkMissingOverridePropertyAttribute,
		#[AutowiredParameter]
		private bool $checkMissingOverrideMethodAttribute,
	)
	{
	}

	public function findPrototype(ClassReflection $classReflection, string $constantName): ?ClassConstantReflection
	{
		foreach ($classReflection->getImmediateInterfaces() as $immediateInterface) {
			if ($immediateInterface->hasConstant($constantName)) {
				return $immediateInterface->getConstant($constantName);
			}
		}

		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return null;
		}

		if (!$parentClass->hasConstant($constantName)) {
			return null;
		}

		$constant = $parentClass->getConstant($constantName);
		if ($constant->isPrivate()) {
			return null;
		}

		return $constant;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		Node\Stmt\ClassConst|Node\Stmt\EnumCase $node,
		string $constantName,
		?ClassConstantReflection $prototype,
	): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		if ($node instanceof Node\Stmt\EnumCase) {
			$description = 'Enum case';
			$overrideIdentifier = 'enum.caseOverride';
			$missingOverrideIdentifier = 'enum.caseMissingOverride';
			$isFixable = true;
		} else {
			$description = 'Constant';
			$overrideIdentifier = 'classConstant.override';
			$missingOverrideIdentifier = 'classConstant.missingOverride';
			// the attribute applies to all constants declared in the statement
			$isFixable = count($node->consts) === 1;
		}

		$className = $scope->getClassReflection()->getDisplayName(false);
		$hasOverrideAttribute = $this->hasOverrideAttribute($node->attrGroups);

		if ($prototype === null) {
			if (!$hasOverrideAttribute) {
				return [];
			}

			$builder = RuleErrorBuilder::message(sprintf(
				'%s %s::%s has #[\Override] attribute but does not override any constant.',
				$description,
				$className,
				$constantName,
			))
				->nonIgnorable()
				->identifier($overrideIdentifier);
			if ($isFixable) {
				$builder->fixNode($node, function (Node\Stmt\ClassConst|Node\Stmt\EnumCase $node) {
					$node->attrGroups = $this->filterOverrideAttribute($node->attrGroups);
					return $node;
				});
			}

			return [$builder->build()];
		}

		if (
			$hasOverrideAttribute
			|| $scope->isInTrait()
			|| !$this->isMissingOverrideAttributeChecked()
		) {
			return [];
		}

		$builder = RuleErrorBuilder::message(sprintf(
			'%s %s::%s overrides constant %s::%s but is missing the #[\Override] attribute.',
			$description,
			$className,
			$constantName,
			$prototype->getDeclaringClass()->getDisplayName(false),
			$prototype->getName(),
		))->identifier($missingOverrideIdentifier);
		if ($isFixable) {
			$builder->fixNode($node, static function (Node\Stmt\ClassConst|Node\Stmt\EnumCase $node) {
				$node->attrGroups[] = new Node\AttributeGroup([
					new Node\Attribute(new Node\Name\FullyQualified('Override')),
				]);

				return $node;
			});
		}

		return [$builder->build()];
	}

	private function isMissingOverrideAttributeChecked(): bool
	{
		if ($this->checkMissingOverrideConstantAttribute !== null) {
			return $this->checkMissingOverrideConstantAttribute;
		}

		return ($this->checkMissingOverridePropertyAttribute === true || $this->checkMissingOverrideMethodAttribute)
			&& $this->phpVersion->supportsOverrideAttributeOnClassConstant();
	}

	/**
	 * @param Node\AttributeGroup[] $attrGroups
	 * @return Node\AttributeGroup[]
	 */
	private function filterOverrideAttribute(array $attrGroups): array
	{
		foreach ($attrGroups as $i => $attrGroup) {
			foreach ($attrGroup->attrs as $j => $attr) {
				if ($attr->name->toLowerString() !== 'override') {
					continue;
				}

				unset($attrGroup->attrs[$j]);
				if (count($attrGroup->attrs) !== 0) {
					continue;
				}

				unset($attrGroups[$i]);
			}
		}

		return $attrGroups;
	}

	/**
	 * @param Node\AttributeGroup[] $attrGroups
	 */
	private function hasOverrideAttribute(array $attrGroups): bool
	{
		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'override') {
					return true;
				}
			}
		}

		return false;
	}

}
