<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

// use Jean85\PrettyVersions;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class SarifErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		// $phpstanVersion = PrettyVersions::getVersion('phpstan/phpstan')->getPrettyVersion();
		$phpstanVersion = '1.9.11';

		yield [
			'No errors',
			0,
			0,
			0,
			'
{
	"$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"fullName": "PHP Static Analysis Tool",
					"semanticVersion": "' . $phpstanVersion . '",
					"informationUri": "https:\/\/phpstan.org"
				}
			},
			"results": []
		}
	]
}',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			'
{
	"$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"fullName": "PHP Static Analysis Tool",
					"semanticVersion": "' . $phpstanVersion . '",
					"informationUri": "https:\/\/phpstan.org"
				}
			},
			"results": [
				{
					"message": {
						"text": "Foo"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "file:///data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php"
								},
								"region": {
									"startLine": 4
								}
							}
						}
					],
					"properties": {
						"canBeIgnored": true
					}
				}
			]
		}
	]
}',
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			'
{
	"$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"fullName": "PHP Static Analysis Tool",
					"semanticVersion": "' . $phpstanVersion . '",
					"informationUri": "https:\/\/phpstan.org"
				}
			},
			"results": [
				{
					"message": {
						"text": "first generic error"
					}
				}
			]
		}
	]
}',
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
	 */
	public function testPrettyFormatErrors(
		string $message,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		string $expected,
	): void
	{
		$formatter = new SarifErrorFormatter(true);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), $message);

		$this->assertJsonStringEqualsJsonString($expected, $this->getOutputContent());
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
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
		$formatter = new SarifErrorFormatter(false);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertJsonStringEqualsJsonString($expected, $this->getOutputContent(), sprintf('%s: JSON do not match', $message));
	}

}
