<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use DOMDocument;
use Generator;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;

class JunitErrorFormatterTest extends ErrorFormatterTestCase
{

	private JunitErrorFormatter $formatter;

	public function setUp(): void
	{
		parent::setUp();

		$this->formatter = new JunitErrorFormatter(new SimpleRelativePathHelper(self::DIRECTORY_PATH));
	}

	/**
	 * @return Generator<array<int, (string|int)>>
	 */
	public function dataFormatterOutputProvider(): Generator
	{
		yield 'No errors' => [
			0,
			0,
			0,
			'<?xml version="1.0" encoding="UTF-8"?>
<testsuite failures="0" name="phpstan" tests="1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="phpstan"/>
</testsuite>
',
		];

		yield 'One file error' => [
			1,
			1,
			0,
			'<?xml version="1.0" encoding="UTF-8"?>
<testsuite failures="1" name="phpstan" tests="1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="folder with unicode &#x1F603;/file name with &quot;spaces&quot; and unicode &#x1F603;.php:4">
    <failure type="ERROR" message="Foo" />
  </testcase>
</testsuite>
',
		];

		yield 'One generic error' => [
			1,
			0,
			1,
			'<?xml version="1.0" encoding="UTF-8"?>
<testsuite failures="1" name="phpstan" tests="1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="General error">
    <failure type="ERROR" message="first generic error" />
  </testcase>
</testsuite>
',
		];

		yield 'Multiple file errors' => [
			1,
			4,
			0,
			'<?xml version="1.0" encoding="UTF-8"?>
<testsuite failures="4" name="phpstan" tests="4" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="folder with unicode &#x1F603;/file name with &quot;spaces&quot; and unicode &#x1F603;.php:2">
    <failure type="ERROR" message="Bar Bar2" />
  </testcase>
  <testcase name="folder with unicode &#x1F603;/file name with &quot;spaces&quot; and unicode &#x1F603;.php:4">
    <failure type="ERROR" message="Foo" />
  </testcase>
  <testcase name="foo.php:1">
    <failure type="ERROR" message="Foo"/>
  </testcase>
  <testcase name="foo.php:5">
    <failure type="ERROR" message="Bar Bar2"/>
  </testcase>
</testsuite>
',
		];

		yield 'Multiple generic errors' => [
			1,
			0,
			2,
			'<?xml version="1.0" encoding="UTF-8"?>
<testsuite failures="2" name="phpstan" tests="2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="General error">
    <failure type="ERROR" message="first generic error" />
  </testcase>
  <testcase name="General error">
    <failure type="ERROR" message="second generic error"/>
  </testcase>
</testsuite>
',
		];

		yield 'Multiple file, multiple generic errors' => [
			1,
			4,
			2,
			'<?xml version="1.0" encoding="UTF-8"?>
<testsuite failures="6" name="phpstan" tests="6" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.3/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="folder with unicode &#x1F603;/file name with &quot;spaces&quot; and unicode &#x1F603;.php:2">
    <failure type="ERROR" message="Bar Bar2" />
  </testcase>
  <testcase name="folder with unicode &#x1F603;/file name with &quot;spaces&quot; and unicode &#x1F603;.php:4">
    <failure type="ERROR" message="Foo" />
  </testcase>
  <testcase name="foo.php:1">
    <failure type="ERROR" message="Foo"/>
  </testcase>
  <testcase name="foo.php:5">
    <failure type="ERROR" message="Bar Bar2"/>
  </testcase>
  <testcase name="General error">
    <failure type="ERROR" message="first generic error" />
  </testcase>
  <testcase name="General error">
    <failure type="ERROR" message="second generic error"/>
  </testcase>
</testsuite>
',
		];
	}

	/**
	 * Test generated use cases for JUnit output format.
	 *
	 * @dataProvider dataFormatterOutputProvider
	 */
	public function testFormatErrors(
		int $exitCode,
		int $numFileErrors,
		int $numGeneralErrors,
		string $expected,
	): void
	{
		$this->assertSame(
			$exitCode,
			$this->formatter->formatErrors(
				$this->getAnalysisResult($numFileErrors, $numGeneralErrors),
				$this->getOutput(),
			),
			'Response code do not match',
		);

		$xml = new DOMDocument();
		$xml->loadXML($this->getOutputContent());

		$this->assertTrue(
			$xml->schemaValidate(__DIR__ . '/junit-schema.xsd'),
			'Schema do not validate',
		);

		$this->assertXmlStringEqualsXmlString(
			$expected,
			$this->getOutputContent(),
			'XML do not match',
		);
	}

}
