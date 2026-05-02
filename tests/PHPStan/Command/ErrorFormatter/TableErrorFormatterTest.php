<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Override;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\CommandHelper;
use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function getenv;
use function putenv;
use function sprintf;

class TableErrorFormatterTest extends ErrorFormatterTestCase
{

	private string|false $terminalEmulator;

	#[Override]
	protected function setUp(): void
	{
		putenv('GITHUB_ACTIONS');

		$this->terminalEmulator = getenv('TERMINAL_EMULATOR');
		putenv('TERMINAL_EMULATOR');
	}

	#[Override]
	protected function tearDown(): void
	{
		putenv('COLUMNS');
		putenv('TERM_PROGRAM');
		putenv('TERMINAL_EMULATOR' . ($this->terminalEmulator !== false ? '=' . $this->terminalEmulator : ''));
		putenv('PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS');
	}

	public static function dataFormatterOutputProvider(): iterable
	{
		yield [
			'message' => 'No errors',
			'exitCode' => 0,
			'numFileErrors' => 0,
			'numGenericErrors' => 0,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => '
 [OK] No errors

',
		];

		yield [
			'message' => 'One file error',
			'exitCode' => 1,
			'numFileErrors' => 1,
			'numGenericErrors' => 0,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => ' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  4      Foo
 ------ -------------------------------------------------------------------


 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'One generic error',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 1,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => ' -- ---------------------
     Error
 -- ---------------------
     first generic error
 -- ---------------------


 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'Multiple file errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 0,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => ' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  2      Bar
         Bar2
  4      Foo
 ------ -------------------------------------------------------------------

 ------ -----------
  Line   foo.php
 ------ -----------
  1      Foo<Bar>
  5      Bar
         Bar2
         💡  a tip
 ------ -----------

 [ERROR] Found 4 errors

',
		];

		yield [
			'message' => 'Multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 2,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => ' -- -----------------------
     Error
 -- -----------------------
     first generic error
     second generic<error>
 -- -----------------------


 [ERROR] Found 2 errors

',
		];

		yield [
			'message' => 'Multiple file, multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 2,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => ' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  2      Bar
         Bar2
  4      Foo
 ------ -------------------------------------------------------------------

 ------ -----------
  Line   foo.php
 ------ -----------
  1      Foo<Bar>
  5      Bar
         Bar2
         💡  a tip
 ------ -----------

 -- -----------------------
     Error
 -- -----------------------
     first generic error
     second generic<error>
 -- -----------------------

 [ERROR] Found 6 errors

',
		];

		yield [
			'message' => 'One file error, called via Visual Studio Code',
			'exitCode' => 1,
			'numFileErrors' => 1,
			'numGenericErrors' => 0,
			'verbose' => false,
			'extraEnvVars' => ['TERM_PROGRAM=vscode'],
			'expected' => ' ------ -------------------------------------------------------------------
  Line   folder with unicode 😃/file name with "spaces" and unicode 😃.php
 ------ -------------------------------------------------------------------
  :4     Foo
 ------ -------------------------------------------------------------------


 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'One file error with tip',
			'exitCode' => 1,
			'numFileErrors' => [5, 1],
			'numGenericErrors' => 0,
			'verbose' => false,
			'extraEnvVars' => [],
			'expected' => ' ------ ----------------
  Line   foo.php
 ------ ----------------
  5      Foobar\Buz
         🪪  foobar.buz
         💡  a tip
 ------ ----------------


 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'One file error with tip and verbose',
			'exitCode' => 1,
			'numFileErrors' => [5, 1],
			'numGenericErrors' => 0,
			'verbose' => true,
			'extraEnvVars' => [],
			'expected' => ' ------ ----------------
  Line   foo.php
 ------ ----------------
  5      Foobar\Buz
         🪪  foobar.buz
         💡  a tip
 ------ ----------------


 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'Errors in JetBrains',
			'exitCode' => 1,
			'numFileErrors' => [5, 1],
			'numGenericErrors' => 1,
			'verbose' => true,
			'extraEnvVars' => ['TERMINAL_EMULATOR=JetBrains-JediTerm'],
			'expected' => ' ------ ----------------
  Line   foo.php
 ------ ----------------
  5      Foobar\Buz
         🪪  foobar.buz
         💡  a tip
         at foo.php:5
 ------ ----------------

 -- ---------------------
     Error
 -- ---------------------
     first generic error
 -- ---------------------

 [ERROR] Found 2 errors

',
		];
	}

	/**
	 * @param array{int, int}|int $numFileErrors
	 * @param array<string> $extraEnvVars
	 */
	#[DataProvider('dataFormatterOutputProvider')]
	public function testFormatErrors(
		string $message,
		int $exitCode,
		array|int $numFileErrors,
		int $numGenericErrors,
		bool $verbose,
		array $extraEnvVars,
		string $expected,
	): void
	{
		$formatter = $this->createErrorFormatter(null);

		// NOTE: extra env vars need to be cleared in tearDown()
		foreach ($extraEnvVars as $envVar) {
			putenv($envVar);
		}

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(false, $verbose),
		), sprintf('%s: response code do not match', $message));

		$this->assertSame($expected, $this->getOutputContent(false, $verbose), sprintf('%s: output do not match', $message));
	}

	public static function dataErrorLimit(): iterable
	{
		yield [
			'errorsBudget' => null,
			'usedLevel' => CommandHelper::DEFAULT_LEVEL,
			'showAllErrors' => false,
			'expected' => ' ------ -------------------------------
  Line   Foo.php (in context of trait)
 ------ -------------------------------
  12     Test
  13     Test
  14     Test
  15     Test
 ------ -------------------------------


 [ERROR] Found 4 errors

',
		];
		yield [
			'errorsBudget' => 1,
			'usedLevel' => CommandHelper::DEFAULT_LEVEL,
			'showAllErrors' => false,
			'expected' => ' ------ -------------------------------
  Line   Foo.php (in context of trait)
 ------ -------------------------------
  12     Test
 ------ -------------------------------


 [ERROR] Found 1+ errors

 ! [NOTE] Result is limited to the first 1 errors
 !        - Pass PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1
 !        environment variable to show all errors
 !        - Consider using PHPStan Pro for more comfortable error browsing
 !          Learn more: https://phpstan.com

',
		];

		yield [
			'errorsBudget' => 3,
			'usedLevel' => '8',
			'showAllErrors' => false,
			'expected' => ' ------ -------------------------------
  Line   Foo.php (in context of trait)
 ------ -------------------------------
  12     Test
  13     Test
  14     Test
 ------ -------------------------------


 [ERROR] Found 3+ errors

 ! [NOTE] Result is limited to the first 3 errors
 !        - Consider lowering the PHPStan level
 !        - Pass PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1
 !        environment variable to show all errors
 !        - Consider using PHPStan Pro for more comfortable error browsing
 !          Learn more: https://phpstan.com

',
		];

			yield [
				'errorsBudget' => 3,
				'usedLevel' => CommandHelper::DEFAULT_LEVEL,
				'showAllErrors' => false,
				'expected' => ' ------ -------------------------------
  Line   Foo.php (in context of trait)
 ------ -------------------------------
  12     Test
  13     Test
  14     Test
 ------ -------------------------------


 [ERROR] Found 3+ errors

 ! [NOTE] Result is limited to the first 3 errors
 !        - Pass PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1
 !        environment variable to show all errors
 !        - Consider using PHPStan Pro for more comfortable error browsing
 !          Learn more: https://phpstan.com
',
			];

			yield [
				'errorsBudget' => 4,
				'usedLevel' => CommandHelper::DEFAULT_LEVEL,
				'showAllErrors' => false,
				'expected' => ' ------ -------------------------------
  Line   Foo.php (in context of trait)
 ------ -------------------------------
  12     Test
  13     Test
  14     Test
  15     Test
 ------ -------------------------------


 [ERROR] Found 4 errors

',
			];
			yield [
				'errorsBudget' => 5,
				'usedLevel' => CommandHelper::DEFAULT_LEVEL,
				'showAllErrors' => false,
				'expected' => ' ------ -------------------------------
  Line   Foo.php (in context of trait)
 ------ -------------------------------
  12     Test
  13     Test
  14     Test
  15     Test
 ------ -------------------------------


 [ERROR] Found 4 errors

',
			];

			yield [
				'errorsBudget' => null,
				'usedLevel' => '8',
				'showAllErrors' => false,
				'expected' => '

 [ERROR] Found 1000+ errors

',
				'generateErrorsCount' => TableErrorFormatter::ERRORS_LIMIT + 5,
			];

			yield [
				'errorsBudget' => null,
				'usedLevel' => '8',
				'showAllErrors' => true,
				'expected' => '

 [ERROR] Found 1005 errors

',
				'generateErrorsCount' => TableErrorFormatter::ERRORS_LIMIT + 5,
			];
	}

	#[DataProvider('dataErrorLimit')]
	public function testErrorLimit(
		?int $errorsBudget,
		string $usedLevel,
		bool $showAllErrors,
		string $expected,
		int $generateErrorsCount = 4,
	): void
	{
		// windows has minor formatting differences (line breaks)
		$this->skipIfNotOnUnix();

		putenv('COLUMNS=80');
		if ($showAllErrors) {
			if ($errorsBudget !== null) {
				$this->fail('showAllErrors cannot be true when errorsBudget is set');
			}
			putenv('PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1');
			$errorsBudget = null;
		} else {
			putenv('PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS');
		}

		$formatter = $this->createErrorFormatter(
			null,
			null,
			$usedLevel,
			$errorsBudget,
		);
		$errors = [];
		$line = 12;
		for ($i = 0; $i < $generateErrorsCount; $i++) {
			$errors[] = new Error('Test', 'Foo.php (in context of trait)', $line, filePath: 'Foo.php', traitFilePath: 'Bar.php');
			$line++;
		}
		$formatter->formatErrors(new AnalysisResult($errors, [], [], [], [], false, null, true, 0, false, []), $this->getOutput());

		$this->assertStringContainsString($expected, $this->getOutputContent());
	}

	public function testEditorUrlWithTrait(): void
	{
		$formatter = $this->createErrorFormatter('editor://%file%/%line%');
		$error = new Error('Test', 'Foo.php (in context of trait)', 12, filePath: 'Foo.php', traitFilePath: 'Bar.php');
		$formatter->formatErrors(new AnalysisResult([$error], [], [], [], [], false, null, true, 0, false, []), $this->getOutput());

		$this->assertStringContainsString('Bar.php', $this->getOutputContent());
	}

	public function testEditorUrlWithRelativePath(): void
	{
		$formatter = $this->createErrorFormatter('editor://custom/path/%relFile%/%line%');
		$error = new Error('Test', 'Foo.php', 12, filePath: self::DIRECTORY_PATH . '/rel/Foo.php');
		$formatter->formatErrors(new AnalysisResult([$error], [], [], [], [], false, null, true, 0, false, []), $this->getOutput(true));

		$this->assertStringContainsString('editor://custom/path/rel/Foo.php', $this->getOutputContent(true));
	}

	public function testEditorUrlWithCustomTitle(): void
	{
		$formatter = $this->createErrorFormatter('editor://any', '%relFile%:%line%');
		$error = new Error('Test', 'Foo.php', 12, filePath: self::DIRECTORY_PATH . '/rel/Foo.php');
		$formatter->formatErrors(new AnalysisResult([$error], [], [], [], [], false, null, true, 0, false, []), $this->getOutput(true));

		$this->assertStringContainsString('rel/Foo.php:12', $this->getOutputContent(true));
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
				0,
				false,
				[],
			),
			$this->getOutput(),
		);
		self::expectNotToPerformAssertions();
	}

	public function testBug13292(): void
	{
		putenv('COLUMNS=200');
		$formatter = $this->createErrorFormatter(null);
		$formatter->formatErrors(
			new AnalysisResult(
				[
					new Error(
						'Parameter #1 $arrayabc of method Abcdefghijklmnopqrstuvwxyzabcdefghijk::translateAbcdefgh() expects array{status: int, error: string, date?: string}, non-empty-array<mixed, mixed> given.',
						'Foo.php',
						5,
						identifier: 'argument.type',
					),
				],
				[],
				[],
				[],
				[],
				false,
				null,
				true,
				0,
				false,
				[],
			),
			$this->getOutput(),
		);
		self::expectNotToPerformAssertions();
	}

	public function testBug13317(): void
	{
		putenv('COLUMNS=170');
		$formatter = $this->createErrorFormatter(null);
		$formatter->formatErrors(
			new AnalysisResult(
				[
					new Error(
						'Property bla::$error_params (non-empty-list<string>|null) is never assigned non-empty-list<string> so it can be removed from the property type.',
						'bla.php',
						6,
						identifier: 'property.unusedType',
					),
				],
				[],
				[],
				[],
				[],
				false,
				null,
				true,
				0,
				false,
				[],
			),
			$this->getOutput(),
		);
		$this->assertSame(
			<<<'TABLE'
 ------ -------------------------------------------------------------------------------------------------------------------------------------------------
  Line   bla.php
 ------ -------------------------------------------------------------------------------------------------------------------------------------------------
  6      Property bla::$error_params (non-empty-list<string>|null) is never assigned non-empty-list<string> so it can be removed from the property type.
         🪪  property.unusedType
 ------ -------------------------------------------------------------------------------------------------------------------------------------------------


 [ERROR] Found 1 error


TABLE,
			$this->getOutputContent(),
		);
	}

	public function testFormatSkippedFixErrorRendersIdentifierAndTip(): void
	{
		putenv('COLUMNS=170');
		$formatter = $this->createErrorFormatter(null);
		$formatter->formatErrors(
			new AnalysisResult(
				[
					new Error(
						'is_string($x) is equivalent to !== null here. Use the latter.',
						'Trait.php',
						4,
						tip: 'Auto-fix skipped: trait consumers proposed conflicting rewrites. '
							. 'Fix in context of class A differs from fix in class B.',
						identifier: 'app.type.forbidUselessIsTypeFunction',
						wasFixable: true,
					),
				],
				[],
				[],
				[],
				[],
				false,
				null,
				true,
				0,
				false,
				[],
			),
			$this->getOutput(),
		);
		$this->assertSame(
			<<<'TABLE'
 ------ -----------------------------------------------------------------------------------------------------------------------------
  Line   Trait.php
 ------ -----------------------------------------------------------------------------------------------------------------------------
  4      is_string($x) is equivalent to !== null here. Use the latter.
         🪪  app.type.forbidUselessIsTypeFunction
         💡  Auto-fix skipped: trait consumers proposed conflicting rewrites. Fix in context of class A differs from fix in class B.
 ------ -----------------------------------------------------------------------------------------------------------------------------


 [ERROR] Found 1 error


TABLE,
			$this->getOutputContent(),
		);
	}

	public function testJetBrainsTerminalRelativePath(): void
	{
		putenv('TERMINAL_EMULATOR=JetBrains-JediTerm');

		// FuzzyRelativePathHelper trims path segments based on analysed paths.
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [self::DIRECTORY_PATH . '/rel'], '/');

		$formatter = new TableErrorFormatter(
			$relativePathHelper,
			new SimpleRelativePathHelper(self::DIRECTORY_PATH),
			new CiDetectedErrorFormatter(
				new GithubErrorFormatter($relativePathHelper),
				new TeamcityErrorFormatter($relativePathHelper),
			),
			false,
			null,
			null,
			CommandHelper::DEFAULT_LEVEL,
		);
		$error = new Error('Test', 'Foo.php', 12, filePath: self::DIRECTORY_PATH . '/rel/Foo.php');
		$formatter->formatErrors(new AnalysisResult([$error], [], [], [], [], false, null, true, 0, false, []), $this->getOutput(true));

		$this->assertStringContainsString('at rel/Foo.php:12', $this->getOutputContent(true));
	}

	private function createErrorFormatter(
		?string $editorUrl,
		?string $editorUrlTitle = null,
		string $usedLevel = CommandHelper::DEFAULT_LEVEL,
		?int $errorsBudget = null,
	): TableErrorFormatter
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
			$editorUrlTitle,
			$usedLevel,
			$errorsBudget,
		);
	}

}
