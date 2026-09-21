<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(level: 0)]
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

		if (
			$node->isFinal()
			&& !$this->phpVersion->supportsFinalProperties()
		) {
			return [
				RuleErrorBuilder::message('Final properties are supported only on PHP 8.4 and later.')
					->nonIgnorable()
					->identifier('property.final')
					->build(),
			];
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
		} elseif (!$this->doAllHooksHaveBody($node)) {
			return [
				RuleErrorBuilder::message('Non-abstract properties cannot include hooks without bodies.')
					->nonIgnorable()
					->identifier('property.hookWithoutBody')
					->build(),
			];
		}

		if ($node->isPrivate()) {
			if ($node->isAbstract()) {
				return [
					RuleErrorBuilder::message('Property cannot be both abstract and private.')
						->nonIgnorable()
						->identifier('property.abstractPrivate')
						->build(),
				];
			}

			if ($node->isFinal()) {
				return [
					RuleErrorBuilder::message('Property cannot be both final and private.')
						->nonIgnorable()
						->identifier('property.finalPrivate')
						->build(),
				];
			}

			foreach ($node->getHooks() as $hook) {
				if (!$hook->isFinal()) {
					continue;
				}

				return [
					RuleErrorBuilder::message('Private property cannot have a final hook.')
						->nonIgnorable()
						->identifier('property.finalPrivateHook')
						->build(),
				];
			}
		}

		if ($node->isAbstract()) {
			if ($node->isFinal()) {
				return [
					RuleErrorBuilder::message('Property cannot be both abstract and final.')
						->nonIgnorable()
						->identifier('property.abstractFinal')
						->build(),
				];
			}

			foreach ($node->getHooks() as $hook) {
				if ($hook->body !== null) {
					continue;
				}

				if (!$hook->isFinal()) {
					continue;
				}

				return [
					RuleErrorBuilder::message('Property cannot be both abstract and final.')
						->nonIgnorable()
						->identifier('property.abstractFinal')
						->build(),
				];
			}
		}

		if ($node->isReadOnly()) {
			if ($node->hasHooks()) {
				return [
					RuleErrorBuilder::message('Hooked properties cannot be readonly.')
						->nonIgnorable()
						->identifier('property.hookReadOnly')
						->build(),
				];
			}
		}

		if ($node->isStatic()) {
			if ($node->hasHooks()) {
				return [
					RuleErrorBuilder::message('Hooked properties cannot be static.')
						->nonIgnorable()
						->identifier('property.hookedStatic')
						->build(),
				];
			}
			if (
				!$this->phpVersion->supportsAsymmetricVisibilityForStaticProperties()
				&& (
					$node->isPrivateSet()
					|| $node->isProtectedSet()
					|| $node->isPublicSet()
				)
			) {
				return [
					RuleErrorBuilder::message('Asymmetric visibility for static properties is supported only on PHP 8.5 and later.')
						->nonIgnorable()
						->identifier('property.staticAsymmetricVisibility')
						->build(),
				];
			}
		}

		if ($node->isVirtual()) {
			if ($node->getDefault() !== null) {
				return [
					RuleErrorBuilder::message('Virtual hooked properties cannot have a default value.')
						->nonIgnorable()
						->identifier('property.virtualDefault')
						->build(),
				];
			}
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
