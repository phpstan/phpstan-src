<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingNativeTypeRule>
 */
class MissingNativeTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingNativeTypeRule(
			new PhpVersion(80200),
			self::getContainer()->getByType(PhpDocEditor::class),
			self::getContainer()->getByType(MissingTypehintCheck::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-native-type.php'], [
			[
				'Method MissingNativeType\SameTypes::doFoo() is missing native types: int $a, return type string.',
				15,
			],
			[
				'Method MissingNativeType\SameTypes::doBar() is missing native types: stdClass $o, ?Countable $c.',
				26,
			],
			[
				'Method MissingNativeType\SameTypes::withDescription() is missing native types: int $a.',
				31,
			],
			[
				'Method MissingNativeType\SameTypes::nullDefault() is missing native types: ?int $a.',
				36,
			],
			[
				'Method MissingNativeType\SameTypes::variadic() is missing native types: string ...$parts.',
				41,
			],
			[
				'Method MissingNativeType\SameTypes::explicitMixed() is missing native types: mixed $a.',
				46,
			],
			[
				'Method MissingNativeType\SameTypes::callable() is missing native types: callable $cb.',
				51,
			],
			[
				'Method MissingNativeType\SameTypes::returnStatic() is missing native types: return type MissingNativeType\SameTypes.',
				56,
			],
			[
				'Method MissingNativeType\SameTypes::returnVoid() is missing native types: return type void.',
				62,
			],
			[
				'Method MissingNativeType\MoreSpecificTypes::doFoo() is missing native types: string $s, ?int $i, return type array.',
				76,
			],
			[
				'Method MissingNativeType\MoreSpecificTypes::template() is missing native types: object $o, return type object.',
				86,
			],
			[
				'Method MissingNativeType\MoreSpecificTypes::returnThis() is missing native types: return type static.',
				92,
			],
			[
				'Method MissingNativeType\ParentClass::overridable() is missing native types: int $a.',
				146,
			],
			[
				'Method MissingNativeType\ParentClass::privateMethod() is missing native types: int $a, return type int.',
				155,
			],
			[
				'Method MissingNativeType\ParentClass::finalMethod() is missing native types: int $a, return type int.',
				164,
			],
			[
				'Method MissingNativeType\ChildClass::untyped() is missing native types: return type int.',
				178,
			],
			[
				'Method MissingNativeType\ChildClass::typed() is missing native types: int $a.',
				187,
			],
			[
				'Method MissingNativeType\ChildClass::__construct() is missing native types: int $a.',
				193,
			],
			[
				'Method MissingNativeType\SomeInterface::doFoo() is missing native types: int $a.',
				206,
			],
			[
				'Function MissingNativeType\doFoo() is missing native types: int $a, return type int.',
				214,
			],
			[
				'Property MissingNativeType\Properties::$a is missing native type int.',
				223,
			],
			[
				'Property MissingNativeType\Properties::$nullable is missing native type ?stdClass.',
				226,
			],
			[
				'Property MissingNativeType\Properties::$moreSpecific is missing native type string.',
				229,
			],
			[
				'Property MissingNativeType\Properties::$noDefault is missing native type stdClass.',
				232,
			],
			[
				'Property MissingNativeType\Properties::$staticNullable is missing native type ?stdClass.',
				238,
			],
			[
				'Property MissingNativeType\Properties::$withDescription is missing native type int.',
				250,
			],
			[
				'Property MissingNativeType\NonFinalProperties::$private is missing native type int.',
				263,
			],
			[
				'Method MissingNativeType\PromotedProperties::__construct() is missing native types: int $a.',
				274,
			],
			[
				'Method MissingNativeType\NonFinalPromotedProperties::__construct() is missing native types: int $a.',
				287,
			],
			[
				'Method MissingNativeType\ExplicitMixedValueType::doFoo() is missing native types: array $a, return type array.',
				327,
			],
			[
				'Method MissingNativeType\\ApiClass::__construct() is missing native types: int $a, int $b.',
				343,
			],
			[
				'Method MissingNativeType\\ApiClass::doFoo() is missing native types: return type int.',
				351,
			],
			[
				'Property MissingNativeType\\ApiClass::$private is missing native type int.',
				360,
			],
			[
				'Method MissingNativeType\\ApiMethod::notApi() is missing native types: int $a.',
				376,
			],
			[
				'Method MissingNativeType\\NotDowngradable::params() is missing native types: int $b.',
				390,
			],
			[
				'Method MissingNativeType\\NotDowngradable::returnsUnion() is missing native types: return type string|false.',
				419,
			],
			[
				'Method MissingNativeType\\ApiClassPrivateMethod::doFoo() is missing native types: int $a.',
				434,
			],
		]);
	}

	public function testFix(): void
	{
		$this->fix(__DIR__ . '/data/missing-native-type.php', __DIR__ . '/data/missing-native-type.php.fixed');
	}

}
