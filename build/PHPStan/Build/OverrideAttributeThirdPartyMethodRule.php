<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Methods\MethodPrototypeFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function str_starts_with;

/**
 * @implements Rule<InClassMethodNode>
 */
final class OverrideAttributeThirdPartyMethodRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodPrototypeFinder $methodPrototypeFinder,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		$prototypeData = $this->methodPrototypeFinder->findPrototype($node->getClassReflection(), $method->getName());
		if ($prototypeData === null) {
			return [];
		}

		[$prototype, $prototypeDeclaringClass] = $prototypeData;

		if (str_starts_with($prototypeDeclaringClass->getName(), 'PHPStan\\')) {
			if (
				!str_starts_with($prototypeDeclaringClass->getName(), 'PHPStan\\PhpDocParser\\')
				&& !str_starts_with($prototypeDeclaringClass->getName(), 'PHPStan\\BetterReflection\\')
			) {
				return [];
			}
		}

		$messages = [];
		if (
			$this->phpVersion->supportsOverrideAttribute()
			&& !$scope->isInTrait()
			&& !$this->hasOverrideAttribute($node->getOriginalNode())
		) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides 3rd party method %s::%s() but is missing the #[\Override] attribute.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototypeDeclaringClass->getDisplayName(true),
				$prototype->getName(),
			))
				->identifier('phpstan.missing3rdPartyOverride')
				->fixNode($node->getOriginalNode(), static function (Node\Stmt\ClassMethod $method) {
					$method->attrGroups[] = new Node\AttributeGroup([
						new Attribute(new Node\Name\FullyQualified('Override')),
					]);

					return $method;
				})
				->build();
		}

		return $messages;
	}

	private function hasOverrideAttribute(Node\Stmt\ClassMethod $method): bool
	{
		foreach ($method->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toLowerString() === 'override') {
					return true;
				}
			}
		}

		return false;
	}

}
