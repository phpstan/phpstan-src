<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function putenv;
use function sprintf;
use const PHP_VERSION_ID;

class TableErrorFormatterTest extends ErrorFormatterTestCase
{

	protected function setUp(): void
	{
		putenv('GITHUB_ACTIONS');
	}

	protected function tearDown(): void
	{
		putenv('COLUMNS');
	}

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'No errors',
			0,
			0,
			0,
			'
 [OK] No errors

',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  4      Foo
 ------ -------------------------------------------------------------------


 [ERROR] Found 1 error

',
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			' -- ---------------------
     Error
 -- ---------------------
     first generic error
 -- ---------------------


 [ERROR] Found 1 error

',
		];

		yield [
			'Multiple file errors',
			1,
			4,
			0,
			' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  2      Bar
         Bar2
  4      Foo
 ------ -------------------------------------------------------------------

 ------ ---------
  Line   foo.php
 ------ ---------
  1      Foo
  5      Bar
         Bar2
 ------ ---------

 [ERROR] Found 4 errors

',
		];

		yield [
			'Multiple generic errors',
			1,
			0,
			2,
			' -- ----------------------
     Error
 -- ----------------------
     first generic error
     second generic error
 -- ----------------------


 [ERROR] Found 2 errors

',
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  2      Bar
         Bar2
  4      Foo
 ------ -------------------------------------------------------------------

 ------ ---------
  Line   foo.php
 ------ ---------
  1      Foo
  5      Bar
         Bar2
 ------ ---------

 -- ----------------------
     Error
 -- ----------------------
     first generic error
     second generic error
 -- ----------------------

 [ERROR] Found 6 errors

',
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
	 */
	public function testFormatErrors(
		string $message,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		string $expected,
	): void
	{
		if (PHP_VERSION_ID >= 80100) {
			self::markTestSkipped('Skipped on PHP 8.1 because of different result');
		}
		$formatter = $this->createErrorFormatter(null);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

	public function testEditorUrlWithTrait(): void
	{
		$formatter = $this->createErrorFormatter('editor://%file%/%line%');
		$error = new Error('Test', 'Foo.php (in context of trait)', 12, true, 'Foo.php', 'Bar.php');
		$formatter->formatErrors(new AnalysisResult([$error], [], [], [], [], false, null, true), $this->getOutput());

		$this->assertStringContainsString('Bar.php', $this->getOutputContent());
	}

	public function testEditorUrlWithRelativePath(): void
	{
		$formatter = $this->createErrorFormatter('editor://custom/path/%relFile%/%line%');
		$error = new Error('Test', 'Foo.php', 12, true, self::DIRECTORY_PATH . '/rel/Foo.php');
		$formatter->formatErrors(new AnalysisResult([$error], [], [], [], [], false, null, true), $this->getOutput(true));

		$this->assertStringContainsString('editor://custom/path/rel/Foo.php', $this->getOutputContent(true));
	}

	public function testBug6727(): void
	{
		putenv('COLUMNS=30');
		$formatter = $this->createErrorFormatter(null);
		$formatter->formatErrors(
			new AnalysisResult(
				[
					new Error(
						'Method MissingTypehintPromotedProperties\Foo::__construct() has parameter $foo with no value type specified in iterable type array.',
						'/var/www/html/app/src/Foo.php (in context of class App\Foo\Bar)',
						5,
					),
				],
				[],
				[],
				[],
				[],
				false,
				null,
				true,
			),
			$this->getOutput(),
		);
		self::expectNotToPerformAssertions();
	}

	private function createErrorFormatter(?string $editorUrl): TableErrorFormatter
	{
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [], '/');

		return new TableErrorFormatter(
			$relativePathHelper,
			new SimpleRelativePathHelper(self::DIRECTORY_PATH),
			new CiDetectedErrorFormatter(
				new GithubErrorFormatter($relativePathHelper),
				new TeamcityErrorFormatter($relativePathHelper),
			),
			false,
			$editorUrl,
		);
	}

}
