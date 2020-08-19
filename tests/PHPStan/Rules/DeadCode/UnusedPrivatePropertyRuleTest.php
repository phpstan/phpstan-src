<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnusedPrivatePropertyRule>
 */
class UnusedPrivatePropertyRuleTest extends RuleTestCase
{

	/** @var string[] */
	private $alwaysWrittenTags;

	/** @var string[] */
	private $alwaysReadTags;

	protected function getRule(): Rule
	{
		return new UnusedPrivatePropertyRule(
			new DirectReadWritePropertiesExtensionProvider([
				new class() implements ReadWritePropertiesExtension {

					public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
					{
						return $property->getDeclaringClass()->getName() === 'UnusedPrivateProperty\\TextExtension'
							&& in_array($propertyName, [
								'read',
								'used',
							], true);
					}

					public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
					{
						return $property->getDeclaringClass()->getName() === 'UnusedPrivateProperty\\TextExtension'
							&& in_array($propertyName, [
								'written',
								'used',
							], true);
					}

					public function isInitialized(PropertyReflection $property, string $propertyName): bool
					{
						return false;
					}

				},
			]),
			$this->alwaysWrittenTags,
			$this->alwaysReadTags,
			true
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4 or static reflection.');
		}

		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];

		$this->analyse([__DIR__ . '/data/unused-private-property.php'], [
			[
				'Property UnusedPrivateProperty\Foo::$bar is never read, only written.',
				10,
			],
			[
				'Property UnusedPrivateProperty\Foo::$baz is unused.',
				12,
			],
			[
				'Property UnusedPrivateProperty\Foo::$lorem is never written, only read.',
				14,
			],
			[
				'Property UnusedPrivateProperty\Bar::$baz is never written, only read.',
				57,
			],
			[
				'Static property UnusedPrivateProperty\Baz::$bar is never read, only written.',
				86,
			],
			[
				'Static property UnusedPrivateProperty\Baz::$baz is unused.',
				88,
			],
			[
				'Static property UnusedPrivateProperty\Baz::$lorem is never written, only read.',
				90,
			],
			[
				'Property UnusedPrivateProperty\Lorem::$baz is never read, only written.',
				117,
			],
			[
				'Property UnusedPrivateProperty\TextExtension::$unused is unused.',
				148,
			],
			[
				'Property UnusedPrivateProperty\TextExtension::$read is never written, only read.',
				150,
			],
			[
				'Property UnusedPrivateProperty\TextExtension::$written is never read, only written.',
				152,
			],
		]);
	}

	public function testAlwaysUsedTags(): void
	{
		$this->alwaysWrittenTags = ['@ORM\Column'];
		$this->alwaysReadTags = ['@get'];
		$this->analyse([__DIR__ . '/data/private-property-with-tags.php'], [
			[
				'Property PrivatePropertyWithTags\Foo::$title is never read, only written.',
				13,
			],
			[
				'Property PrivatePropertyWithTags\Foo::$text is never written, only read.',
				18,
			],
		]);
	}

	public function testTrait(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/private-property-trait.php'], []);
	}

	public function testBug3636(): void
	{
		if (!self::$useStaticReflectionProvider && PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-3636.php'], [
			[
				'Property Bug3636\Bar::$date is never written, only read.',
				22,
			],
		]);
	}

}
