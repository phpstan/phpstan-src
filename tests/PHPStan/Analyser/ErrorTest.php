<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ErrorTest extends PHPStanTestCase
{

	public function testError(): void
	{
		$error = new Error('Message', 'file', 10);
		$this->assertSame('Message', $error->getMessage());
		$this->assertSame('file', $error->getFile());
		$this->assertSame(10, $error->getLine());
	}

	public function testRemoveTraitContextUsesTraitFileAsFilePath(): void
	{
		$error = new Error('Message', 'trait.php (in context of class C)', 11, true, 'user.php', 'trait.php');
		$this->assertSame('user.php', $error->getFilePath());
		$this->assertSame('trait.php', $error->getTraitFilePath());

		$withoutTraitContext = $error->removeTraitContext();
		$this->assertSame('trait.php', $withoutTraitContext->getFile());
		// filePath must follow the file onto the trait, otherwise editor URLs and
		// inline @phpstan-ignore lookups point at the using-class file (#14718).
		$this->assertSame('trait.php', $withoutTraitContext->getFilePath());
		$this->assertNull($withoutTraitContext->getTraitFilePath());
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
		$this->assertTrue(Error::validateIdentifier($identifier));
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
		$this->assertFalse(Error::validateIdentifier($identifier));
	}

}
