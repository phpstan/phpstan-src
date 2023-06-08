<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function in_array;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnusedPrivatePropertyRule>
 */
class UnusedPrivatePropertyRuleTest extends RuleTestCase
{

	/** @var string[] */
	private array $alwaysWrittenTags;

	/** @var string[] */
	private array $alwaysReadTags;

	protected function getRule(): Rule
	{
		return new UnusedPrivatePropertyRule(
			new DirectReadWritePropertiesExtensionProvider([
				new class() implements ReadWritePropertiesExtension {

					public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
					{
						return $property->getDeclaringClass()->getName() === 'UnusedPrivateProperty\\TestExtension'
							&& in_array($propertyName, [
								'read',
								'used',
							], true);
					}

					public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
					{
						return $property->getDeclaringClass()->getName() === 'UnusedPrivateProperty\\TestExtension'
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
			true,
		);
	}

	public function testRule(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];

		$tip = 'See: https://phpstan.org/developing-extensions/always-read-written-properties';

		$this->analyse([__DIR__ . '/data/unused-private-property.php'], [
			[
				'Property UnusedPrivateProperty\Foo::$bar is never read, only written.',
				10,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\Foo::$baz is unused.',
				12,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\Foo::$lorem is never written, only read.',
				14,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\Bar::$baz is never written, only read.',
				57,
				$tip,
			],
			[
				'Static property UnusedPrivateProperty\Baz::$bar is never read, only written.',
				86,
				$tip,
			],
			[
				'Static property UnusedPrivateProperty\Baz::$baz is unused.',
				88,
				$tip,
			],
			[
				'Static property UnusedPrivateProperty\Baz::$lorem is never written, only read.',
				90,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\Lorem::$baz is never read, only written.',
				117,
				$tip,
			],
			[
				'Property class@anonymous/tests/PHPStan/Rules/DeadCode/data/unused-private-property.php:152::$bar is unused.',
				153,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\DolorWithAnonymous::$foo is unused.',
				148,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\ArrayAssign::$foo is never read, only written.',
				162,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\ListAssign::$foo is never read, only written.',
				191,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\WriteToCollection::$collection1 is never read, only written.',
				221,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\WriteToCollection::$collection2 is never read, only written.',
				224,
				$tip,
			],
		]);
		$this->analyse([__DIR__ . '/data/TestExtension.php'], [
			[
				'Property UnusedPrivateProperty\TestExtension::$unused is unused.',
				8,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\TestExtension::$read is never written, only read.',
				10,
				$tip,
			],
			[
				'Property UnusedPrivateProperty\TestExtension::$written is never read, only written.',
				12,
				$tip,
			],
		]);
	}

	public function testAlwaysUsedTags(): void
	{
		$this->alwaysWrittenTags = ['@ORM\Column'];
		$this->alwaysReadTags = ['@get'];
		$tip = 'See: https://phpstan.org/developing-extensions/always-read-written-properties';
		$this->analyse([__DIR__ . '/data/private-property-with-tags.php'], [
			[
				'Property PrivatePropertyWithTags\Foo::$title is never read, only written.',
				13,
				$tip,
			],
			[
				'Property PrivatePropertyWithTags\Foo::$text is never written, only read.',
				18,
				$tip,
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
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$tip = 'See: https://phpstan.org/developing-extensions/always-read-written-properties';
		$this->analyse([__DIR__ . '/data/bug-3636.php'], [
			[
				'Property Bug3636\Bar::$date is never written, only read.',
				22,
				$tip,
			],
		]);
	}

	public function testPromotedProperties(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = ['@get'];
		$tip = 'See: https://phpstan.org/developing-extensions/always-read-written-properties';
		$this->analyse([__DIR__ . '/data/unused-private-promoted-property.php'], [
			[
				'Property UnusedPrivatePromotedProperty\Foo::$lorem is never read, only written.',
				12,
				$tip,
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/nullsafe-unused-private-property.php'], []);
	}

	public function testBug3654(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-3654.php'], []);
	}

	public function testBug5935(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-5935.php'], []);
	}

	public function testBug5337(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-5337.php'], []);
	}

	public function testBug5971(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-5971.php'], []);
	}

	public function testBug6107(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-6107.php'], []);
	}

	public function testBug8204(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-8204.php'], []);
	}

	public function testBug8850(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-8850.php'], []);
	}

	public function testBug9409(): void
	{
		$this->alwaysWrittenTags = [];
		$this->alwaysReadTags = [];
		$this->analyse([__DIR__ . '/data/bug-9409.php'], []);
	}

}
