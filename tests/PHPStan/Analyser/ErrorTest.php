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

	public function testRemoveTraitContextKeepsTraitFilePath(): void
	{
		$error = new Error('Message', 'trait.php (in context of class C)', 11, true, 'user.php', 'trait.php');
		$this->assertSame('user.php', $error->getFilePath());
		$this->assertSame('trait.php', $error->getTraitFilePath());

		$withoutTraitContext = $error->removeTraitContext();
		// The error is now reported directly in the trait: the displayed file and
		// filePath are the trait (so a generated baseline records the trait path,
		// stable across runs and using-class changes - #14932), and traitFilePath
		// is kept so the editor URL and the trait-file ignore lookups resolve to
		// the trait (#14718).
		$this->assertSame('trait.php', $withoutTraitContext->getFile());
		$this->assertSame('trait.php', $withoutTraitContext->getFilePath());
		$this->assertSame('trait.php', $withoutTraitContext->getTraitFilePath());
	}

	public function testTraitContexts(): void
	{
		$error = (new Error('Message', 'trait.php (in context of class B)', 11, true, 'b.php', 'trait.php'))
			->removeTraitContext()
			->withTraitContexts([
				'a.php' => 'trait.php (in context of class A)',
				'b.php' => 'trait.php (in context of class B)',
			]);

		// the using-class contexts the deduplicated error was merged from are kept
		// so ignoreErrors paths pointing at a using class account for that class's
		// context only (#14932)
		$this->assertSame([
			'a.php' => 'trait.php (in context of class A)',
			'b.php' => 'trait.php (in context of class B)',
		], $error->getTraitContexts());

		$contextError = $error->asReportedInTraitContext('a.php');
		$this->assertSame('trait.php (in context of class A)', $contextError->getFile());
		$this->assertSame('a.php', $contextError->getFilePath());
		$this->assertSame('trait.php', $contextError->getTraitFilePath());
		$this->assertSame([], $contextError->getTraitContexts());
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
