<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class SarifErrorFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		$phpstanVersion = ComposerHelper::getPhpStanVersion();
		$workingDir = self::DIRECTORY_PATH;

		yield [
			'No errors',
			0,
			0,
			0,
			'
{
	"$schema": "https://json.schemastore.org/sarif-2.1.0.json",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"informationUri": "https://phpstan.org",
					"version": "' . $phpstanVersion . '",
					"rules": []
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
}',
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
					"informationUri": "https:\/\/phpstan.org",
					"version": "' . $phpstanVersion . '",
					"rules": []
				}
			},
			"originalUriBaseIds": {
				"WORKINGDIR": {
					"uri": "file://' . $workingDir . '/"
				}
			},
			"results": [
				{
					"level": "error",
					"message": {
						"text": "Foo"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
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
}',
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			'
{
	"$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0.json",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"informationUri": "https:\/\/phpstan.org",
					"version": "' . $phpstanVersion . '",
					"rules": []
				}
			},
			"originalUriBaseIds": {
				"WORKINGDIR": {
					"uri": "file://' . $workingDir . '/"
				}
			},
			"results": [
				{
					"level": "error",
					"message": {
						"text": "first generic error"
					}
				}
			]
		}
	]
}',
		];

		yield [
			'Multiple file errors',
			1,
			4,
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
					"informationUri": "https:\/\/phpstan.org",
					"version": "' . $phpstanVersion . '",
					"rules": []
				}
			},
			"originalUriBaseIds": {
				"WORKINGDIR": {
					"uri": "file://' . $workingDir . '/"
				}
			},
			"results": [
				{
					"level": "error",
					"message": {
						"text": "Bar\nBar2"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
									"uriBaseId": "WORKINGDIR"
								},
								"region": {
									"startLine": 2
								}
							}
						}
					],
					"properties": {
						"ignorable": true
					}
				},
				{
					"level": "error",
					"message": {
						"text": "Foo"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
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
				},
				{
					"level": "error",
					"message": {
						"text": "Foo"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "foo.php",
									"uriBaseId": "WORKINGDIR"
								},
								"region": {
									"startLine": 1
								}
							}
						}
					],
					"properties": {
						"ignorable": true
					}
				},
				{
					"level": "error",
					"message": {
						"text": "Bar\nBar2"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "foo.php",
									"uriBaseId": "WORKINGDIR"
								},
								"region": {
									"startLine": 5
								}
							}
						}
					],
					"properties": {
						"ignorable": true,
						"tip": "a tip"
					}
				}
			]
		}
	]
}',
		];

		yield [
			'Multiple generic errors',
			1,
			0,
			2,
			'
{
	"$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0.json",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"informationUri": "https:\/\/phpstan.org",
					"version": "' . $phpstanVersion . '",
					"rules": []
				}
			},
			"originalUriBaseIds": {
				"WORKINGDIR": {
					"uri": "file://' . $workingDir . '/"
				}
			},
			"results": [
				{
					"level": "error",
					"message": {
						"text": "first generic error"
					}
				},
				{
					"level": "error",
					"message": {
						"text": "second generic error"
					}
				}
			]
		}
	]
}',
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			'
{
	"$schema": "https:\/\/json.schemastore.org\/sarif-2.1.0.json",
	"version": "2.1.0",
	"runs": [
		{
			"tool": {
				"driver": {
					"name": "PHPStan",
					"informationUri": "https:\/\/phpstan.org",
					"version": "' . $phpstanVersion . '",
					"rules": []
				}
			},
			"originalUriBaseIds": {
				"WORKINGDIR": {
					"uri": "file://' . $workingDir . '/"
				}
			},
			"results": [
				{
					"level": "error",
					"message": {
						"text": "Bar\nBar2"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
									"uriBaseId": "WORKINGDIR"
								},
								"region": {
									"startLine": 2
								}
							}
						}
					],
					"properties": {
						"ignorable": true
					}
				},
				{
					"level": "error",
					"message": {
						"text": "Foo"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
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
				},
				{
					"level": "error",
					"message": {
						"text": "Foo"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "foo.php",
									"uriBaseId": "WORKINGDIR"
								},
								"region": {
									"startLine": 1
								}
							}
						}
					],
					"properties": {
						"ignorable": true
					}
				},
				{
					"level": "error",
					"message": {
						"text": "Bar\nBar2"
					},
					"locations": [
						{
							"physicalLocation": {
								"artifactLocation": {
									"uri": "foo.php",
									"uriBaseId": "WORKINGDIR"
								},
								"region": {
									"startLine": 5
								}
							}
						}
					],
					"properties": {
						"ignorable": true,
						"tip": "a tip"
					}
				},
				{
					"level": "error",
					"message": {
						"text": "first generic error"
					}
				},
				{
					"level": "error",
					"message": {
						"text": "second generic error"
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
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [], '/');
		$formatter = new SarifErrorFormatter($relativePathHelper, self::DIRECTORY_PATH, true);

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
		$relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), self::DIRECTORY_PATH, [], '/');
		$formatter = new SarifErrorFormatter($relativePathHelper, self::DIRECTORY_PATH, false);

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertJsonStringEqualsJsonString($expected, $this->getOutputContent(), sprintf('%s: JSON do not match', $message));
	}

}
