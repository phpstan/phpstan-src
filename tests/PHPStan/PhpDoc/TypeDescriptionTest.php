<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class TypeDescriptionTest extends PHPStanTestCase
{

	public function dataTest(): iterable
	{
		yield ['string', new StringType()];
		yield ['array', new ArrayType(new MixedType(), new MixedType())];
		yield ['literal-string', new IntersectionType([new StringType(), new AccessoryLiteralStringType()])];
		yield ['non-empty-string', new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])];
	}

	/**
	 * @dataProvider dataTest
	 */
	public function testParsingDesiredTypeDescription(string $description, Type $expectedType): void
	{
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$type = $typeStringResolver->resolve($description);
		$this->assertTrue($expectedType->equals($type), sprintf('Parsing %s did not result in %s, but in %s', $description, $expectedType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())));

		$newDescription = $type->describe(VerbosityLevel::value());
		$newType = $typeStringResolver->resolve($newDescription);
		$this->assertTrue($type->equals($newType), sprintf('Parsing %s again did not result in %s, but in %s', $newDescription, $type->describe(VerbosityLevel::value()), $newType->describe(VerbosityLevel::value())));
	}

	/**
	 * @dataProvider dataTest
	 */
	public function testDesiredTypeDescription(string $description, Type $expectedType): void
	{
		$this->assertSame($description, $expectedType->describe(VerbosityLevel::value()));

		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$type = $typeStringResolver->resolve($description);
		$this->assertSame($description, $type->describe(VerbosityLevel::value()));
	}

}
