<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function is_bool;
use function sprintf;
use function ucfirst;

/**
 * Checks shared by methods and property hooks that override a method or a hook from a parent.
 */
#[AutowiredService]
final class OverridingMethodCheck
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkOverrideAttributeWithoutPrototype(
		ExtendedMethodReflection $method,
		Node\Stmt\ClassMethod|Node\PropertyHook $node,
	): array
	{
		if (!self::hasOverrideAttribute($node->attrGroups)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s has #[\Override] attribute but does not override any %s.',
				ucfirst(self::describeMethod($method, $method->getDeclaringClass())),
				self::isPropertyHook($method) ? sprintf('%s hook', $method->getPropertyHookName()) : 'method',
			))
				->line($node->getStartLine())
				->nonIgnorable()
				->identifier('method.override')
				->fixNode($node, static function (Node\Stmt\ClassMethod|Node\PropertyHook $method) {
					$method->attrGroups = self::filterOverrideAttribute($method->attrGroups);
					return $method;
				})
				->build(),
		];
	}

	/**
	 * Checks the parts of overriding that do not depend on the signature:
	 * the #[\Override] attribute, final, static, and abstract modifiers, and returning by reference.
	 *
	 * @param ExtendedMethodReflection $prototype the method the signature is checked against
	 * @param ExtendedMethodReflection $inheritancePrototype the method the final keyword and the #[\Override] attribute are checked against
	 * @return list<IdentifierRuleError>
	 */
	public function checkInheritance(
		ExtendedMethodReflection $method,
		Node\Stmt\ClassMethod|Node\PropertyHook $node,
		ExtendedMethodReflection $prototype,
		ClassReflection $prototypeDeclaringClass,
		ExtendedMethodReflection $inheritancePrototype,
		ClassReflection $inheritancePrototypeDeclaringClass,
		bool $reportMissingOverrideAttribute,
	): array
	{
		$messages = [];
		if (
			$reportMissingOverrideAttribute
			&& !self::hasOverrideAttribute($node->attrGroups)
		) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s overrides %s but is missing the #[\Override] attribute.',
				ucfirst(self::describeMethod($method, $method->getDeclaringClass())),
				self::describeMethod($inheritancePrototype, $inheritancePrototypeDeclaringClass, true),
			))
				->line($node->getStartLine())
				->identifier('method.missingOverride')
				->fixNode($node, static function (Node\Stmt\ClassMethod|Node\PropertyHook $method) {
					$method->attrGroups[] = new Node\AttributeGroup([
						new Attribute(new Node\Name\FullyQualified('Override')),
					]);

					return $method;
				})
				->build();
		}
		if ($inheritancePrototype->isFinalByKeyword()->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s overrides final %s.',
				ucfirst(self::describeMethod($method, $method->getDeclaringClass())),
				self::describeMethod($inheritancePrototype, $inheritancePrototypeDeclaringClass, true),
			))
				->line($node->getStartLine())
				->nonIgnorable()
				->identifier('method.parentMethodFinal')
				->build();
		} elseif ($inheritancePrototype->isFinal()->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s overrides @final %s.',
				ucfirst(self::describeMethod($method, $method->getDeclaringClass())),
				self::describeMethod($inheritancePrototype, $inheritancePrototypeDeclaringClass, true),
			))
				->line($node->getStartLine())
				->identifier('method.parentMethodFinalByPhpDoc')
				->build();
		}

		if ($prototype->isStatic()) {
			if (!$method->isStatic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Non-static method %s::%s() overrides static method %s::%s().',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototypeDeclaringClass->getDisplayName(true),
					$prototype->getName(),
				))
					->line($node->getStartLine())
					->nonIgnorable()
					->identifier('method.nonStatic')
					->build();
			}
		} elseif ($method->isStatic()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Static method %s::%s() overrides non-static method %s::%s().',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototypeDeclaringClass->getDisplayName(true),
				$prototype->getName(),
			))
				->line($node->getStartLine())
				->nonIgnorable()
				->identifier('method.static')
				->build();
		}

		$methodAbstract = $method->isAbstract();
		$prototypeAbstract = $prototype->isAbstract();
		if (
			(is_bool($methodAbstract) ? $methodAbstract : $methodAbstract->yes())
			&& !(is_bool($prototypeAbstract) ? $prototypeAbstract : $prototypeAbstract->yes())
		) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Cannot make non-abstract %s abstract in class %s.',
				self::describeMethod($prototype, $prototypeDeclaringClass, true),
				$method->getDeclaringClass()->getDisplayName(),
			))
				->line($node->getStartLine())
				->nonIgnorable()
				->identifier('method.abstractOverridingNonAbstract')
				->build();
		}

		if ($prototype->returnsByReference()->yes() && !$method->returnsByReference()->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s overriding %s that returns by reference should also return by reference.',
				ucfirst(self::describeMethod($method, $method->getDeclaringClass())),
				self::describeMethod($prototype, $prototypeDeclaringClass, true),
			))
				->line($node->getStartLine())
				->nonIgnorable()
				->identifier('method.returnByRef')
				->build();
		}

		return $messages;
	}

	/**
	 * Describes a method as "method Foo::bar()" and a property hook as "get hook for property Foo::$bar".
	 */
	public static function describeMethod(ExtendedMethodReflection $method, ClassReflection $declaringClass, bool $displayGenerics = false): string
	{
		if (self::isPropertyHook($method)) {
			return sprintf(
				'%s hook for property %s::$%s',
				$method->getPropertyHookName(),
				$declaringClass->getDisplayName($displayGenerics),
				$method->getHookedPropertyName(),
			);
		}

		return sprintf('method %s::%s()', $declaringClass->getDisplayName($displayGenerics), $method->getName());
	}

	/**
	 * @phpstan-assert-if-true PhpMethodReflection|PhpMethodFromParserNodeReflection $method
	 */
	private static function isPropertyHook(ExtendedMethodReflection $method): bool
	{
		return ($method instanceof PhpMethodReflection || $method instanceof PhpMethodFromParserNodeReflection)
			&& $method->isPropertyHook();
	}

	/**
	 * @param Node\AttributeGroup[] $attrGroups
	 */
	public static function hasOverrideAttribute(array $attrGroups): bool
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

	/**
	 * @param Node\AttributeGroup[] $attrGroups
	 * @return Node\AttributeGroup[]
	 */
	public static function filterOverrideAttribute(array $attrGroups): array
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

}
