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

		if (!$this->phpVersion->supportsPropertyHooks()) {
			if ($node->hasHooks()) {
				return [
					RuleErrorBuilder::message('Property hooks are supported only on PHP 8.4 and later.')
						->nonIgnorable()
						->identifier('property.hooksNotSupported')
						->build(),
				];
			}

			return [];
		}

		if ($node->isAbstract()) {
			if (!$node->hasHooks()) {
				return [
					RuleErrorBuilder::message('Only hooked properties can be declared abstract.')
						->nonIgnorable()
						->identifier('property.abstractNonHooked')
						->build(),
				];
			}

			if (!$this->isAtLeastOneHookBodyEmpty($node)) {
				return [
					RuleErrorBuilder::message('Abstract properties must specify at least one abstract hook.')
						->nonIgnorable()
						->identifier('property.abstractWithoutAbstractHook')
						->build(),
				];
			}

			if (!$classReflection->isAbstract()) {
				return [
					RuleErrorBuilder::message('Non-abstract classes cannot include abstract properties.')
						->nonIgnorable()
						->identifier('property.abstract')
						->build(),
				];
			}

			return [];
		}

		if (!$this->doAllHooksHaveBody($node)) {
			return [
				RuleErrorBuilder::message('Non-abstract properties cannot include hooks without bodies.')
					->nonIgnorable()
					->identifier('property.hookWithoutBody')
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
