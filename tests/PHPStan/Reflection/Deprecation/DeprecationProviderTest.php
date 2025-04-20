<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use CustomDeprecations\AttributeDeprecatedClass;
use CustomDeprecations\AttributeDeprecatedClassWithMessage;
use CustomDeprecations\DoubleDeprecatedClass;
use CustomDeprecations\DoubleDeprecatedClassOnlyAttributeMessage;
use CustomDeprecations\DoubleDeprecatedClassOnlyPhpDocMessage;
use CustomDeprecations\MyDeprecatedEnum;
use CustomDeprecations\NotDeprecatedClass;
use CustomDeprecations\PhpDocDeprecatedClass;
use CustomDeprecations\PhpDocDeprecatedClassWithMessage;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

class DeprecationProviderTest extends PHPStanTestCase
{

	#[RequiresPhp('>= 8.0')]
	public function testCustomDeprecations(): void
	{
		require __DIR__ . '/data/deprecations.php';

		$reflectionProvider = self::createReflectionProvider();

		$notDeprecatedClass = $reflectionProvider->getClass(NotDeprecatedClass::class);
		$attributeDeprecatedClass = $reflectionProvider->getClass(AttributeDeprecatedClass::class);

		// @phpstan-ignore classConstant.deprecatedClass
		$phpDocDeprecatedClass = $reflectionProvider->getClass(PhpDocDeprecatedClass::class);

		// @phpstan-ignore classConstant.deprecatedClass
		$phpDocDeprecatedClassWithMessages = $reflectionProvider->getClass(PhpDocDeprecatedClassWithMessage::class);
		$attributeDeprecatedClassWithMessages = $reflectionProvider->getClass(AttributeDeprecatedClassWithMessage::class);

		// @phpstan-ignore classConstant.deprecatedClass
		$doubleDeprecatedClass = $reflectionProvider->getClass(DoubleDeprecatedClass::class);

		// @phpstan-ignore classConstant.deprecatedClass
		$doubleDeprecatedClassOnlyPhpDocMessage = $reflectionProvider->getClass(DoubleDeprecatedClassOnlyPhpDocMessage::class);

		// @phpstan-ignore classConstant.deprecatedClass
		$doubleDeprecatedClassOnlyAttributeMessage = $reflectionProvider->getClass(DoubleDeprecatedClassOnlyAttributeMessage::class);

		$notDeprecatedFunction = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\notDeprecatedFunction'), null);
		$phpDocDeprecatedFunction = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\phpDocDeprecatedFunction'), null);
		$phpDocDeprecatedFunctionWithMessage = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\phpDocDeprecatedFunctionWithMessage'), null);
		$attributeDeprecatedFunction = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\attributeDeprecatedFunction'), null);
		$attributeDeprecatedFunctionWithMessage = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\attributeDeprecatedFunctionWithMessage'), null);
		$doubleDeprecatedFunction = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\doubleDeprecatedFunction'), null);
		$doubleDeprecatedFunctionOnlyAttributeMessage = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\doubleDeprecatedFunctionOnlyAttributeMessage'), null);
		$doubleDeprecatedFunctionOnlyPhpDocMessage = $reflectionProvider->getFunction(new FullyQualified('CustomDeprecations\\doubleDeprecatedFunctionOnlyPhpDocMessage'), null);

		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);

		$scopeForNotDeprecatedClass = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($notDeprecatedClass));
		$scopeForDeprecatedClass = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($attributeDeprecatedClass));
		$scopeForPhpDocDeprecatedClass = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($phpDocDeprecatedClass));
		$scopeForPhpDocDeprecatedClassWithMessages = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($phpDocDeprecatedClassWithMessages));
		$scopeForAttributeDeprecatedClassWithMessages = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($attributeDeprecatedClassWithMessages));
		$scopeForDoubleDeprecatedClass = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($doubleDeprecatedClass));
		$scopeForDoubleDeprecatedClassOnlyNativeMessage = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($doubleDeprecatedClassOnlyPhpDocMessage));
		$scopeForDoubleDeprecatedClassOnlyCustomMessage = $scopeFactory->create(ScopeContext::create('dummy.php')->enterClass($doubleDeprecatedClassOnlyAttributeMessage));

		// class
		self::assertFalse($notDeprecatedClass->isDeprecated());
		self::assertNull($notDeprecatedClass->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClass->isDeprecated());
		self::assertNull($attributeDeprecatedClass->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClass->isDeprecated());
		self::assertNull($phpDocDeprecatedClass->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClassWithMessages->isDeprecated());
		self::assertSame('phpdoc', $phpDocDeprecatedClassWithMessages->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClassWithMessages->isDeprecated());
		self::assertSame('attribute', $attributeDeprecatedClassWithMessages->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClass->isDeprecated());
		self::assertSame('attribute', $doubleDeprecatedClass->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyPhpDocMessage->isDeprecated());
		self::assertNull($doubleDeprecatedClassOnlyPhpDocMessage->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyAttributeMessage->isDeprecated());
		self::assertSame('attribute', $doubleDeprecatedClassOnlyAttributeMessage->getDeprecatedDescription());

		// class constants
		self::assertFalse($notDeprecatedClass->getConstant('FOO')->isDeprecated()->yes());
		self::assertNull($notDeprecatedClass->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClass->getConstant('FOO')->isDeprecated()->yes());
		self::assertNull($attributeDeprecatedClass->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClass->getConstant('FOO')->isDeprecated()->yes());
		self::assertNull($phpDocDeprecatedClass->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClassWithMessages->getConstant('FOO')->isDeprecated()->yes());
		self::assertSame('phpdoc', $phpDocDeprecatedClassWithMessages->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClassWithMessages->getConstant('FOO')->isDeprecated()->yes());
		self::assertSame('attribute', $attributeDeprecatedClassWithMessages->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClass->getConstant('FOO')->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedClass->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyPhpDocMessage->getConstant('FOO')->isDeprecated()->yes());
		self::assertNull($doubleDeprecatedClassOnlyPhpDocMessage->getConstant('FOO')->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyAttributeMessage->getConstant('FOO')->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedClassOnlyAttributeMessage->getConstant('FOO')->getDeprecatedDescription());

		// properties
		self::assertFalse($notDeprecatedClass->getInstanceProperty('foo', $scopeForNotDeprecatedClass)->isDeprecated()->yes());
		self::assertNull($notDeprecatedClass->getInstanceProperty('foo', $scopeForNotDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClass->getInstanceProperty('foo', $scopeForDeprecatedClass)->isDeprecated()->yes());
		self::assertNull($attributeDeprecatedClass->getInstanceProperty('foo', $scopeForDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClass->getInstanceProperty('foo', $scopeForPhpDocDeprecatedClass)->isDeprecated()->yes());
		self::assertNull($phpDocDeprecatedClass->getInstanceProperty('foo', $scopeForPhpDocDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClassWithMessages->getInstanceProperty('foo', $scopeForPhpDocDeprecatedClassWithMessages)->isDeprecated()->yes());
		self::assertSame('phpdoc', $phpDocDeprecatedClassWithMessages->getInstanceProperty('foo', $scopeForPhpDocDeprecatedClassWithMessages)->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClassWithMessages->getInstanceProperty('foo', $scopeForAttributeDeprecatedClassWithMessages)->isDeprecated()->yes());
		self::assertSame('attribute', $attributeDeprecatedClassWithMessages->getInstanceProperty('foo', $scopeForAttributeDeprecatedClassWithMessages)->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClass->getInstanceProperty('foo', $scopeForDoubleDeprecatedClass)->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedClass->getInstanceProperty('foo', $scopeForDoubleDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyPhpDocMessage->getInstanceProperty('foo', $scopeForDoubleDeprecatedClassOnlyNativeMessage)->isDeprecated()->yes());
		self::assertNull($doubleDeprecatedClassOnlyPhpDocMessage->getInstanceProperty('foo', $scopeForDoubleDeprecatedClassOnlyNativeMessage)->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyAttributeMessage->getInstanceProperty('foo', $scopeForDoubleDeprecatedClassOnlyCustomMessage)->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedClassOnlyAttributeMessage->getInstanceProperty('foo', $scopeForDoubleDeprecatedClassOnlyCustomMessage)->getDeprecatedDescription());

		// methods
		self::assertFalse($notDeprecatedClass->getMethod('foo', $scopeForNotDeprecatedClass)->isDeprecated()->yes());
		self::assertNull($notDeprecatedClass->getMethod('foo', $scopeForNotDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClass->getMethod('foo', $scopeForDeprecatedClass)->isDeprecated()->yes());
		self::assertNull($attributeDeprecatedClass->getMethod('foo', $scopeForDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClass->getMethod('foo', $scopeForPhpDocDeprecatedClass)->isDeprecated()->yes());
		self::assertNull($phpDocDeprecatedClass->getMethod('foo', $scopeForPhpDocDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedClassWithMessages->getMethod('foo', $scopeForPhpDocDeprecatedClassWithMessages)->isDeprecated()->yes());
		self::assertSame('phpdoc', $phpDocDeprecatedClassWithMessages->getMethod('foo', $scopeForPhpDocDeprecatedClassWithMessages)->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedClassWithMessages->getMethod('foo', $scopeForAttributeDeprecatedClassWithMessages)->isDeprecated()->yes());
		self::assertSame('attribute', $attributeDeprecatedClassWithMessages->getMethod('foo', $scopeForAttributeDeprecatedClassWithMessages)->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClass->getMethod('foo', $scopeForDoubleDeprecatedClass)->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedClass->getMethod('foo', $scopeForDoubleDeprecatedClass)->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyPhpDocMessage->getMethod('foo', $scopeForDoubleDeprecatedClassOnlyNativeMessage)->isDeprecated()->yes());
		self::assertNull($doubleDeprecatedClassOnlyPhpDocMessage->getMethod('foo', $scopeForDoubleDeprecatedClassOnlyNativeMessage)->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedClassOnlyAttributeMessage->getMethod('foo', $scopeForDoubleDeprecatedClassOnlyCustomMessage)->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedClassOnlyAttributeMessage->getMethod('foo', $scopeForDoubleDeprecatedClassOnlyCustomMessage)->getDeprecatedDescription());

		// functions
		self::assertFalse($notDeprecatedFunction->isDeprecated()->yes());
		self::assertNull($notDeprecatedFunction->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedFunction->isDeprecated()->yes());
		self::assertNull($phpDocDeprecatedFunction->getDeprecatedDescription());

		self::assertTrue($phpDocDeprecatedFunctionWithMessage->isDeprecated()->yes());
		self::assertSame('phpdoc', $phpDocDeprecatedFunctionWithMessage->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedFunction->isDeprecated()->yes());
		self::assertNull($attributeDeprecatedFunction->getDeprecatedDescription());

		self::assertTrue($attributeDeprecatedFunctionWithMessage->isDeprecated()->yes());
		self::assertSame('attribute', $attributeDeprecatedFunctionWithMessage->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedFunction->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedFunction->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedFunctionOnlyPhpDocMessage->isDeprecated()->yes());
		self::assertNull($doubleDeprecatedFunctionOnlyPhpDocMessage->getDeprecatedDescription());

		self::assertTrue($doubleDeprecatedFunctionOnlyAttributeMessage->isDeprecated()->yes());
		self::assertSame('attribute', $doubleDeprecatedFunctionOnlyAttributeMessage->getDeprecatedDescription());
	}

	#[RequiresPhp('>= 8.1')]
	public function testCustomDeprecationsOfEnumCases(): void
	{
		require __DIR__ . '/data/deprecations-enums.php';

		$reflectionProvider = self::createReflectionProvider();

		$myEnum = $reflectionProvider->getClass(MyDeprecatedEnum::class);

		self::assertTrue($myEnum->isDeprecated());
		self::assertNull($myEnum->getDeprecatedDescription());

		self::assertTrue($myEnum->getEnumCase('CustomDeprecated')->isDeprecated()->yes());
		self::assertSame('custom', $myEnum->getEnumCase('CustomDeprecated')->getDeprecatedDescription());

		self::assertTrue($myEnum->getEnumCase('NativeDeprecated')->isDeprecated()->yes());
		self::assertSame('native', $myEnum->getEnumCase('NativeDeprecated')->getDeprecatedDescription());

		self::assertTrue($myEnum->getEnumCase('PhpDocDeprecated')->isDeprecated()->yes());
		self::assertNull($myEnum->getEnumCase('PhpDocDeprecated')->getDeprecatedDescription()); // this should not be null

		self::assertFalse($myEnum->getEnumCase('NotDeprecated')->isDeprecated()->yes());
		self::assertNull($myEnum->getEnumCase('NotDeprecated')->getDeprecatedDescription());
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/deprecation-provider.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
