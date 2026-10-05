<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * Checks the #[\Override] attribute on class constants and enum cases (PHP 8.6+).
 */
#[AutowiredService]
final class OverrideAttributeOnConstantCheck
{

	public function __construct(
		#[AutowiredParameter]
		private ?bool $checkMissingOverrideConstantAttribute,
		#[AutowiredParameter]
		private bool $checkMissingOverrideMethodAttribute,
	)
	{
	}

	/**
	 * @param Node\AttributeGroup[] $attrGroups
	 * @param Node\Stmt\ClassConst|Node\Stmt\EnumCase|null $fixableNode
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		ClassReflection $classReflection,
		string $constantName,
		?ClassConstantReflection $prototype,
		array $attrGroups,
		?Node $fixableNode,
		bool $isEnumCase,
	): array
	{
		$description = $isEnumCase ? 'Enum case' : 'Constant';

		if ($prototype === null) {
			if (!$this->hasOverrideAttribute($attrGroups)) {
				return [];
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'%s %s::%s has #[\Override] attribute but does not override any constant.',
				$description,
				$classReflection->getDisplayName(false),
				$constantName,
			))
				->nonIgnorable()
				->identifier($isEnumCase ? 'enum.caseOverride' : 'classConstant.override');

			if ($fixableNode !== null) {
				$errorBuilder->fixNode($fixableNode, function (Node\Stmt\ClassConst|Node\Stmt\EnumCase $node) {
					$node->attrGroups = $this->filterOverrideAttribute($node->attrGroups);
					return $node;
				});
			}

			return [$errorBuilder->build()];
		}

		if (
			$scope->isInTrait()
			|| $this->hasOverrideAttribute($attrGroups)
			|| !$this->isMissingOverrideChecked($scope)
		) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'%s %s::%s overrides constant %s::%s but is missing the #[\Override] attribute.',
			$description,
			$classReflection->getDisplayName(false),
			$constantName,
			$prototype->getDeclaringClass()->getDisplayName(false),
			$prototype->getName(),
		))
			->identifier($isEnumCase ? 'enum.caseMissingOverride' : 'classConstant.missingOverride');

		if ($fixableNode !== null) {
			$errorBuilder->fixNode($fixableNode, static function (Node\Stmt\ClassConst|Node\Stmt\EnumCase $node) {
				$node->attrGroups[] = new Node\AttributeGroup([
					new Attribute(new Node\Name\FullyQualified('Override')),
				]);

				return $node;
			});
		}

		return [$errorBuilder->build()];
	}

	private function isMissingOverrideChecked(Scope $scope): bool
	{
		if ($this->checkMissingOverrideConstantAttribute !== null) {
			return $this->checkMissingOverrideConstantAttribute;
		}

		return $this->checkMissingOverrideMethodAttribute
			&& $scope->getPhpVersion()->supportsOverrideAttributeOnClassConstant()->yes();
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
