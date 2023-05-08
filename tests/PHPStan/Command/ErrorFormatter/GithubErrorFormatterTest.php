<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class GithubErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'No errors',
			0,
			0,
			0,
			'',
			'error',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			'::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=4,col=0::Foo
',
			'error',
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			'::error ::first generic error
',
			'error',
		];

		yield [
			'Multiple file errors',
			1,
			4,
			0,
			'::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=2,col=0::Bar%0ABar2
::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=4,col=0::Foo
::error file=foo.php,line=1,col=0::Foo
::error file=foo.php,line=5,col=0::Bar%0ABar2
',
			'error',
		];

		yield [
			'Multiple generic errors',
			1,
			0,
			2,
			'::error ::first generic error
::error ::second generic error
',
			'error',
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			'::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=2,col=0::Bar%0ABar2
::error file=folder with unicode 😃/file name with "spaces" and unicode 😃.php,line=4,col=0::Foo
::error file=foo.php,line=1,col=0::Foo
::error file=foo.php,line=5,col=0::Bar%0ABar2
::error ::first generic error
::error ::second generic error
',
			'error',
		];

		yield [
			'One generic error as warning',
			1,
			0,
			1,
			'::warning ::first generic error
',
			'warning',
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
		string $errorLevel,
	): void
	{
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [], '/');
		$formatter = new GithubErrorFormatter(
			$relativePathHelper,
			$errorLevel,
		);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

}
