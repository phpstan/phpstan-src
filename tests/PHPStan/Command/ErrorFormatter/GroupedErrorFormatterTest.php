<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class GroupedErrorFormatterTest extends ErrorFormatterTestCase
{

	public static function dataFormatterOutputProvider(): iterable
	{
		yield [
			'message' => 'No errors',
			'exitCode' => 0,
			'numFileErrors' => 0,
			'numGenericErrors' => 0,
			'expected' => '
 [OK] No errors

',
		];

		yield [
			'message' => 'One file error',
			'exitCode' => 1,
			'numFileErrors' => 1,
			'numGenericErrors' => 0,
			'expected' => '[without identifier] (1x):
	- /data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4: Foo


 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'One generic error',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 1,
			'expected' => '?:?:first generic error

 [ERROR] Found 1 error

',
		];

		yield [
			'message' => 'Multiple file errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 0,
			'expected' => '[without identifier] (4x):
	- /data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2: Bar
Bar2
	- /data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4: Foo
	- /data/folder/with space/and unicode 😃/project/foo.php:1: Foo<Bar>
	- /data/folder/with space/and unicode 😃/project/foo.php:5: Bar
Bar2


 [ERROR] Found 4 errors

',
		];

		yield [
			'message' => 'Multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 0,
			'numGenericErrors' => 2,
			'expected' => '?:?:first generic error
?:?:second generic<error>

 [ERROR] Found 2 errors

',
		];

		yield [
			'message' => 'Multiple file, multiple generic errors',
			'exitCode' => 1,
			'numFileErrors' => 4,
			'numGenericErrors' => 2,
			'expected' => '[without identifier] (4x):
	- /data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:2: Bar
Bar2
	- /data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with "spaces" and unicode 😃.php:4: Foo
	- /data/folder/with space/and unicode 😃/project/foo.php:1: Foo<Bar>
	- /data/folder/with space/and unicode 😃/project/foo.php:5: Bar
Bar2

?:?:first generic error
?:?:second generic<error>

 [ERROR] Found 6 errors

',
		];

		yield [
			'message' => 'One file error with identifier',
			'exitCode' => 1,
			'numFileErrors' => [5, 6],
			'numGenericErrors' => 0,
			'expected' => '[foobar.buz] (1x):
	- /data/folder/with space/and unicode 😃/project/foo.php:5: Foobar\Buz


 [ERROR] Found 1 error

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
		string $expected,
	): void
	{
		$formatter = $this->createErrorFormatter(null);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertEquals($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

	private function createErrorFormatter(?string $editorUrl, ?string $editorUrlTitle = null): GroupedErrorFormatter
	{
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [], '/');

		return new GroupedErrorFormatter(
			$relativePathHelper,
			$editorUrl,
			$editorUrlTitle,
		);
	}

}
