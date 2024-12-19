<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use Attribute;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function count;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
final class TraitAttributesRule implements Rule
{

	public function __construct(
		private AttributesCheck $attributesCheck,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$traitName = $node->namespacedName;
		if ($traitName === null) {
			return [];
		}

		if (!$this->reflectionProvider->hasClass($traitName->toString())) {
			return [];
		}
		$traitClassReflection = $this->reflectionProvider->getClass($traitName->toString());

		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}
		$fakeClass = new Class_(null, [new Node\Stmt\TraitUse([$traitName])], ['startLine' => 1, 'endLine' => 1]);
		$fakeClassReflection = $this->reflectionProvider->getAnonymousClassReflection($fakeClass, $scope);
		$scope = $scope->enterClass($fakeClassReflection);
		$scope = $scope->enterTrait($traitClassReflection);

		$errors = $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_CLASS,
			'class',
		);

		if (count($traitClassReflection->getNativeReflection()->getAttributes('AllowDynamicProperties')) > 0) {
			$errors[] = RuleErrorBuilder::message('Attribute class AllowDynamicProperties cannot be used with trait.')
				->identifier('trait.allowDynamicProperties')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
