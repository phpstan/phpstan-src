<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class CheckstyleErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'No errors',
			0,
			0,
			0,
			'<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
</checkstyle>
',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			'<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file name="folder with unicode 😃/file name with &quot;spaces&quot; and unicode 😃.php">
  <error line="4" column="1" severity="error" message="Foo"/>
</file>
</checkstyle>
',
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			'<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file>
  <error message="first generic error" severity="error"/>
</file>
</checkstyle>
',
		];

		yield [
			'Multiple file errors',
			1,
			4,
			0,
			'<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file name="folder with unicode 😃/file name with &quot;spaces&quot; and unicode 😃.php">
  <error line="2" column="1" severity="error" message="Bar Bar2"/>
  <error line="4" column="1" severity="error" message="Foo"/>
</file>
<file name="foo.php">
  <error line="1" column="1" severity="error" message="Foo"/>
  <error line="5" column="1" severity="error" message="Bar Bar2"/>
</file>
</checkstyle>
',
		];

		yield [
			'Multiple generic errors',
			1,
			0,
			2,
			'<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file>
  <error message="first generic error" severity="error"/>
  <error message="second generic error" severity="error"/>
</file>
</checkstyle>
',
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			'<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file name="folder with unicode 😃/file name with &quot;spaces&quot; and unicode 😃.php">
  <error line="2" column="1" severity="error" message="Bar Bar2"/>
  <error line="4" column="1" severity="error" message="Foo"/>
</file>
<file name="foo.php">
  <error line="1" column="1" severity="error" message="Foo"/>
  <error line="5" column="1" severity="error" message="Bar Bar2"/>
</file>
<file>
  <error message="first generic error" severity="error"/>
  <error message="second generic error" severity="error"/>
</file>
</checkstyle>
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
		$formatter = new CheckstyleErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$outputContent = $this->getOutputContent();
		$this->assertXmlStringEqualsXmlString($expected, $outputContent, sprintf('%s: XML do not match', $message));
		$this->assertStringStartsWith('<?xml', $outputContent);
	}

	public function testTraitPath(): void
	{
		$formatter = new CheckstyleErrorFormatter(new SimpleRelativePathHelper(__DIR__));
		$error = new Error(
			'Foo',
			__DIR__ . '/FooTrait.php (in context of class Foo)',
			5,
			true,
			__DIR__ . '/Foo.php',
			__DIR__ . '/FooTrait.php',
		);
		$formatter->formatErrors(new AnalysisResult(
			[$error],
			[],
			[],
			[],
			[],
			false,
			null,
			true,
			0,
			false,
		), $this->getOutput());
		$this->assertXmlStringEqualsXmlString('<checkstyle>
	<file name="FooTrait.php">
		<error column="1" line="5" message="Foo" severity="error"/>
	</file>
</checkstyle>', $this->getOutputContent());
	}

	public function testIdentifier(): void
	{
		$formatter = new CheckstyleErrorFormatter(new SimpleRelativePathHelper(__DIR__));
		$error = (new Error(
			'Foo',
			__DIR__ . '/FooTrait.php',
			5,
			true,
			__DIR__ . '/Foo.php',
			null,
		))->withIdentifier('argument.type');
		$formatter->formatErrors(new AnalysisResult(
			[$error],
			[],
			[],
			[],
			[],
			false,
			null,
			true,
			0,
			true,
		), $this->getOutput());
		$this->assertXmlStringEqualsXmlString('<checkstyle>
	<file name="Foo.php">
		<error column="1" line="5" message="Foo" severity="error" source="argument.type" />
	</file>
</checkstyle>', $this->getOutputContent());
	}

}
