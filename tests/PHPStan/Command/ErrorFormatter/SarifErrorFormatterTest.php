<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\ErrorFormatterTestCase;

/**
 * @author Laurent Laville
 */
class SarifErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		$phpStanVersion = '1.1.2';
		$workingDir = getcwd();

		yield [
			'No errors',
			0,
			0,
			0,
			'
{
    "$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0.json",
    "version": "2.1.0",
    "runs": [
        {
            "tool": {
                "driver": {
                    "name": "PHPStan",
                    "version": "' . $phpStanVersion . '",
                    "informationUri": "https:\/\/phpstan.org"
                }
            },
            "originalUriBaseIds": {
                "WORKINGDIR": {
                    "uri": "file://' . $workingDir . '/"
                }
            },
            "results": []
        }
    ]
}
',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			'
{
    "$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0.json",
    "version": "2.1.0",
    "runs": [
        {
            "tool": {
                "driver": {
                    "name": "PHPStan",
                    "version": "' . $phpStanVersion . '",
                    "informationUri": "https:\/\/phpstan.org"
                }
            },
            "originalUriBaseIds": {
                "WORKINGDIR": {
                    "uri": "file://' . $workingDir . '/"
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
                                    "uri": "file:///data/folder/with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
                                    "uriBaseId": "WORKINGDIR"
                                },
                                "region": {
                                    "startLine": 4
                                }
                            }
                        }
                    ],
                    "properties": {
                    	"ignorable": true
                    }
                }
            ]
        }
    ]
}
',
		];
	}

	/**
	 * @dataProvider dataFormatterOutputProvider
	 *
	 * @param string $message
	 * @param int $exitCode
	 * @param int $numFileErrors
	 * @param int $numGenericErrors
	 * @param string $expected
	 * @throws ShouldNotHappenException
	 */
	public function testPrettyFormatErrors(
		string $message,
		int $exitCode,
		int $numFileErrors,
		int $numGenericErrors,
		string $expected
	): void
	{
		$formatter = new SarifErrorFormatter();

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput()
		), $message);

		$this->assertJsonStringEqualsJsonString($expected, $this->getOutputContent());
	}

}
