<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use UnusedPrivateProperty\TextExtension;
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
						return $property->getDeclaringClass()->getName() === TextExtension::class
							&& in_array($propertyName, [
								'read',
								'used',
							], true);
					}

					public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
					{
						return $property->getDeclaringClass()->getName() === TextExtension::class
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
				'Class UnusedPrivateProperty\Foo has a write-only property $bar.',
				10,
			],
			[
				'Class UnusedPrivateProperty\Foo has an unused property $baz.',
				12,
			],
			[
				'Class UnusedPrivateProperty\Foo has a read-only property $lorem.',
				14,
			],
			[
				'Class UnusedPrivateProperty\Bar has a read-only property $baz.',
				57,
			],
			[
				'Class UnusedPrivateProperty\Baz has a write-only static property $bar.',
				86,
			],
			[
				'Class UnusedPrivateProperty\Baz has an unused static property $baz.',
				88,
			],
			[
				'Class UnusedPrivateProperty\Baz has a read-only static property $lorem.',
				90,
			],
			[
				'Class UnusedPrivateProperty\Lorem has a write-only property $baz.',
				117,
			],
			[
				'Class UnusedPrivateProperty\TextExtension has an unused property $unused.',
				148,
			],
			[
				'Class UnusedPrivateProperty\TextExtension has a read-only property $read.',
				150,
			],
			[
				'Class UnusedPrivateProperty\TextExtension has a write-only property $written.',
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
				'Class PrivatePropertyWithTags\Foo has a write-only property $title.',
				13,
			],
			[
				'Class PrivatePropertyWithTags\Foo has a read-only property $text.',
				18,
			],
		]);
	}

}
