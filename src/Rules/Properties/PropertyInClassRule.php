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

		if (!$classReflection->isClass() || !$this->phpVersion->supportsPropertyHooks()) {
			return [];
		}

		if (!$classReflection->isAbstract() && $node->hasHooks() && $node->isAbstract()) {
			return [
				RuleErrorBuilder::message('Classes may not include abstract hooked properties.')
					->nonIgnorable()
					->identifier('property.abstractHookedInClass')
					->build(),
			];
		}

		if (!$classReflection->isAbstract() && $node->hasHooks() && !$this->doAllHooksHaveBody($node)) {
			return [
				RuleErrorBuilder::message('Classes may not include hooked properties without bodies.')
					->nonIgnorable()
					->identifier('property.hookedWithoutBodyInClass')
					->build(),
			];
		}

		if (!$classReflection->isAbstract()) {
			return [];
		}

		if ($node->hasHooks() && !$node->isAbstract()) {
			return [
				RuleErrorBuilder::message('Abstract classes may not include non-abstract hooked properties without bodies.')
					->nonIgnorable()
					->identifier('property.nonAbstractHookedWithoutBodyInAbstractClass')
					->build(),
			];
		}

		if ($node->isAbstract() && !$node->hasHooks()) {
			return [
				RuleErrorBuilder::message('Only hooked properties may be declared abstract.')
					->nonIgnorable()
					->identifier('property.nonHookedAbstractInClass')
					->build(),
			];
		}

		if ($node->isAbstract() && !$this->isAtLeastOneHookBodyEmpty($node)) {
			return [
				RuleErrorBuilder::message('Abstract properties must specify at least one abstract hook.')
					->nonIgnorable()
					->identifier('property.hookedAbstractWithBodies')
					->build(),
			];
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
