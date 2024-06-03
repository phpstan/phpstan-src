<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class RawErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'message' => 'No errors',
			'exitCode' => 0,
			'numFileErrors' => 0,
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '',
		];

		yield [
			'message' => 'One file error',
			'exitCode' => 1,
			'numFileErrors' => 1,
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n",
		];

		yield [
			'message' => 'One generic error',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 1,
			'verbose' => false,
			'expected' => '?:?:first generic error' . "\n",
		];

		yield [
			'message' => 'Multiple file errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2:Bar
Bar2
/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo
/data/folder/with space/and unicode 😃/project/foo.php:1:Foo<Bar>
/data/folder/with space/and unicode 😃/project/foo.php:5:Bar
Bar2
',
		];

		yield [
			'message' => 'Multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 2,
			'verbose' => false,
			'expected' => '?:?:first generic error
?:?:second generic<error>
',
		];

		yield [
			'message' => 'Multiple file, multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 2,
			'verbose' => false,
			'expected' => '?:?:first generic error
?:?:second generic<error>
/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2:Bar
Bar2
/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo
/data/folder/with space/and unicode 😃/project/foo.php:1:Foo<Bar>
/data/folder/with space/and unicode 😃/project/foo.php:5:Bar
Bar2
',
		];

		yield [
			'message' => 'One file error with tip',
			'exitCode' => 1,
			'numFileErrors' => [5, 6],
			'numGenericErrors' => 0,
			'verbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/foo.php:5:Foobar\Buz
',
		];

		yield [
			'message' => 'One file error with tip and verbose',
			'exitCode' => 1,
			'numFileErrors' => [5, 6],
			'numGenericErrors' => 0,
			'verbose' => true,
			'expected' => '/data/folder/with space/and unicode 😃/project/foo.php:5:Foobar\Buz [identifier=foobar.buz]
',
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 * @param array{int, int}|int $numFileErrors
	 */
	public function testFormatErrors(
		string $message,
		int $exitCode,
		array|int $numFileErrors,
		int $numGenericErrors,
		bool $verbose,
		string $expected,
	): void
	{
		$formatter = new RawErrorFormatter();

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(false, $verbose),
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(false, $verbose), sprintf('%s: output do not match', $message));
	}

}
