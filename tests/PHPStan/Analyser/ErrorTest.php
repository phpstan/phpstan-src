<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ErrorTest extends PHPStanTestCase
{

	public function testError(): void
	{
		$error = new Error('Message', 'file', 10);
		self::assertSame('Message', $error->getMessage());
		self::assertSame('file', $error->getFile());
		self::assertSame(10, $error->getLine());
	}

	public static function dataValidIdentifier(): iterable
	{
		yield ['a'];
		yield ['aa'];
		yield ['phpstan'];
		yield ['phpstan.internal'];
		yield ['phpstan.alwaysFail'];
		yield ['Phpstan.alwaysFail'];
		yield ['phpstan.internal.foo'];
		yield ['foo2.test'];
		yield ['phpstan123'];
		yield ['3m.blah'];
	}

	#[DataProvider('dataValidIdentifier')]
	public function testValidIdentifier(string $identifier): void
	{
		self::assertTrue(Error::validateIdentifier($identifier));
	}

	public static function dataInvalidIdentifier(): iterable
	{
		yield [''];
		yield [' '];
		yield ['phpstan '];
		yield [' phpstan'];
		yield ['.phpstan'];
		yield ['phpstan.'];
		yield ['.'];
	}

	#[DataProvider('dataInvalidIdentifier')]
	public function testInvalidIdentifier(string $identifier): void
	{
		self::assertFalse(Error::validateIdentifier($identifier));
	}

}
