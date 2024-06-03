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
			'isVerbose' => false,
			'expected' => '',
		];

		yield [
			'message' => 'One file error',
			'exitCode' => 1,
			1,
			'numGenericErrors' => 0,
			'isVerbose' => false,
			'expected' => '/data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4:Foo' . "\n",
		];

		yield [
			'message' => 'One generic error',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 1,
			'isVerbose' => false,
			'?:?:first generic error' . "\n",
		];

		yield [
			'message' => 'Multiple file errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 0,
			'isVerbose' => false,
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
			'isVerbose' => false,
			'expected' => '?:?:first generic error
?:?:second generic<error>
',
		];

		yield [
			'message' => 'Multiple file, multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 2,
			'isVerbose' => false,
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
		bool $isVerbose,
		string $expected,
	): void
	{
		$formatter = new RawErrorFormatter();

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

}
