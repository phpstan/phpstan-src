<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertyInClassRule>
 */
class PropertyInClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PropertyInClassRule(new PhpVersion(PHP_VERSION_ID));
	}

	#[RequiresPhp('< 8.4')]
	public function testPhpLessThan84AndHookedPropertiesInClass(): void
	{
		// @phpstan-ignore phpstan.skipTestsRequiresPhp
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Property hooks cause syntax error on PHP 7.4');
		}

		$this->analyse([__DIR__ . '/data/hooked-properties-in-class.php'], [
			[
				'Property hooks are supported only on PHP 8.4 and later.',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndHookedPropertiesWithoutBodiesInClass(): void
	{
		// @phpstan-ignore phpstan.skipTestsRequiresPhp
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Property hooks cause syntax error on PHP 7.4');
		}

		$this->analyse([__DIR__ . '/data/hooked-properties-without-bodies-in-class.php'], [
			[
				'Non-abstract properties cannot include hooks without bodies.',
				7,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				9,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				15,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndNonAbstractHookedPropertiesInClass(): void
	{
		$this->analyse([__DIR__ . '/data/non-abstract-hooked-properties-in-class.php'], [
			[
				'Non-abstract properties cannot include hooks without bodies.',
				7,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndAbstractHookedPropertiesInClass(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-hooked-properties-in-class.php'], [
			[
				'Non-abstract classes cannot include abstract properties.',
				7,
			],
			[
				'Non-abstract classes cannot include abstract properties.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndNonAbstractHookedPropertiesInAbstractClass(): void
	{
		$this->analyse([__DIR__ . '/data/non-abstract-hooked-properties-in-abstract-class.php'], [
			[
				'Non-abstract properties cannot include hooks without bodies.',
				7,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				9,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				25,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndAbstractNonHookedPropertiesInAbstractClass(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-non-hooked-properties-in-abstract-class.php'], [
			[
				'Only hooked properties can be declared abstract.',
				7,
			],
			[
				'Only hooked properties can be declared abstract.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndAbstractHookedPropertiesWithBodies(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-hooked-properties-with-bodies.php'], [
			[
				'Abstract properties must specify at least one abstract hook.',
				7,
			],
			[
				'Abstract properties must specify at least one abstract hook.',
				12,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndReadonlyHookedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-property-hooks.php'], [
			[
				'Hooked properties cannot be readonly.',
				7,
			],
			[
				'Hooked properties cannot be readonly.',
				12,
			],
			[
				'Hooked properties cannot be readonly.',
				14,
			],
			[
				'Hooked properties cannot be readonly.',
				19,
			],
			[
				'Hooked properties cannot be readonly.',
				24,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndVirtualHookedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/virtual-hooked-properties.php'], [
			[
				'Virtual hooked properties cannot have a default value.',
				17,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndStaticHookedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/static-hooked-properties.php'], [
			[
				'Hooked properties cannot be static.',
				7,
			],
			[
				'Hooked properties cannot be static.',
				15,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndPrivateFinalHookedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/private-final-property-hooks.php'], [
			[
				'Property cannot be both final and private.',
				7,
			],
			[
				'Private property cannot have a final hook.',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndAbstractFinalHookedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-final-property-hook.php'], [
			[
				'Property cannot be both abstract and final.',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndAbstractPrivateHookedProperties(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-private-property-hook.php'], [
			[
				'Property cannot be both abstract and private.',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84AndAbstractFinalHookedPropertiesParseError(): void
	{
		// errors when parsing with php-parser, see https://github.com/nikic/PHP-Parser/issues/1071
		$this->analyse([__DIR__ . '/data/abstract-final-property-hook-parse-error.php'], [
			[
				'Cannot use the final modifier on an abstract class member on line 7',
				7,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84FinalProperties(): void
	{
		$this->analyse([__DIR__ . '/data/final-properties.php'], [
			[
				'Property cannot be both final and private.',
				7,
			],
		]);
	}

	#[RequiresPhp('< 8.4')]
	public function testBeforePhp84FinalProperties(): void
	{
		$this->analyse([__DIR__ . '/data/final-properties.php'], [
			[
				'Final properties are supported only on PHP 8.4 and later.',
				7,
			],
			[
				'Final properties are supported only on PHP 8.4 and later.',
				8,
			],
			[
				'Final properties are supported only on PHP 8.4 and later.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPhp84FinalPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/final-property-hooks.php'], [
			[
				'Cannot use the final modifier on an abstract class member on line 19',
				19,
			],
		]);
	}

}
