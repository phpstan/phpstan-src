<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class PropertyInClassRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();

		if (!$classReflection->isClass()) {
			return [];
		}

		if (!$this->phpVersion->supportsPropertyHooks() && $node->hasHooks()) {
			return [
				RuleErrorBuilder::message('Property hooks in classes are supported only on PHP 8.4 and later.')
					->nonIgnorable()
					->identifier('property.unsupportedHooksInClass')
					->build(),
			];
		}

		if (!$this->phpVersion->supportsPropertyHooks()) {
			return [];
		}

		if ($classReflection->isAbstract()) {
			if ($node->isAbstract()) {
				if (!$node->hasHooks()) {
					return [
						RuleErrorBuilder::message('Only hooked properties may be declared abstract.')
							->nonIgnorable()
							->identifier('property.nonHookedAbstractInClass')
							->build(),
					];
				}

				if (!$this->isAtLeastOneHookBodyEmpty($node)) {
					return [
						RuleErrorBuilder::message('Abstract properties must specify at least one abstract hook.')
							->nonIgnorable()
							->identifier('property.hookedAbstractWithBodies')
							->build(),
					];
				}
			}

			if (!$node->isAbstract()) {
				if ($node->hasHooks()) {
					return [
						RuleErrorBuilder::message('Abstract classes may not include non-abstract hooked properties without bodies.')
							->nonIgnorable()
							->identifier('property.nonAbstractHookedWithoutBodyInAbstractClass')
							->build(),
					];
				}
			}

			return [];
		}

		if ($node->hasHooks()) {
			if ($node->isAbstract()) {
				return [
					RuleErrorBuilder::message('Classes may not include abstract hooked properties.')
						->nonIgnorable()
						->identifier('property.abstractHookedInClass')
						->build(),
				];
			}

			if (!$this->doAllHooksHaveBody($node)) {
				return [
					RuleErrorBuilder::message('Non-abstract classes may not include hooked properties without bodies.')
						->nonIgnorable()
						->identifier('property.hookedWithoutBodyInClass')
						->build(),
				];
			}
		}

		return [];
	}

	private function doAllHooksHaveBody(ClassPropertyNode $node): bool
	{
		foreach ($node->getHooks() as $hook) {
			if ($hook->body === null) {
				return false;
			}
		}

		return true;
	}

	private function isAtLeastOneHookBodyEmpty(ClassPropertyNode $node): bool
	{
		foreach ($node->getHooks() as $hook) {
			if ($hook->body === null) {
				return true;
			}
		}

		return false;
	}

}
