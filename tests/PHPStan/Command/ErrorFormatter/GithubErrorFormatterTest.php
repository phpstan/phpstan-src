<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;
use const PHP_VERSION_ID;

class GithubErrorFormatterTest extends ErrorFormatterTestCase
{

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

::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=4,col=0::Foo
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

::error ::first generic error
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

::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=2,col=0::Bar%0ABar2
::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=4,col=0::Foo
::error file=foo.php,line=1,col=0::Foo
::error file=foo.php,line=5,col=0::Bar%0ABar2
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

::error ::first generic error
::error ::second generic error
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

::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=2,col=0::Bar%0ABar2
::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=4,col=0::Foo
::error file=foo.php,line=1,col=0::Foo
::error file=foo.php,line=5,col=0::Bar%0ABar2
::error ::first generic error
::error ::second generic error
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
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [], '/');
		$formatter = new GithubErrorFormatter(
			$relativePathHelper,
		);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

}
