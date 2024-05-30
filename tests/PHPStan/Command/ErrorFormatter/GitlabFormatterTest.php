<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use function sprintf;

class GitlabFormatterTest extends ErrorFormatterTestCase
{

	public function dataFormatterOutputProvider(): iterable
	{
		yield [
			'No errors',
			0,
			0,
			0,
			'[]',
		];

		yield [
			'One file error',
			1,
			1,
			0,
			'[
    {
        "description": "Foo",
        "fingerprint": "e82b7e1f1d4255352b19ecefa9116a12f129c7edb4351cf2319285eccdb1565e",
        "severity": "major",
        "location": {
            "path": "with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
            "lines": {
                "begin": 4
            }
        }
    }
]',
		];

		yield [
			'One generic error',
			1,
			0,
			1,
			'[
    {
        "description": "first generic error",
        "fingerprint": "53ed216d77c9a9b21d9535322457ca7d7b037d6596d76484b3481f161adfd96f",
        "severity": "major",
        "location": {
            "path": "",
            "lines": {
                "begin": 0
            }
        }
    }
]',
		];

		yield [
			'Multiple file errors',
			1,
			4,
			0,
			'[
    {
        "description": "Bar\nBar2",
        "fingerprint": "034b4afbfb347494c14e396ed8327692f58be4cd27e8aff5f19f4194934db7c9",
        "severity": "major",
        "location": {
            "path": "with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
            "lines": {
                "begin": 2
            }
        }
    },
    {
        "description": "Foo",
        "fingerprint": "e82b7e1f1d4255352b19ecefa9116a12f129c7edb4351cf2319285eccdb1565e",
        "severity": "major",
        "location": {
            "path": "with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
            "lines": {
                "begin": 4
            }
        }
    },
    {
        "description": "Foo<Bar>",
        "fingerprint": "d7002959fc192c81d51fc41b0a3f240617a1aa35361867b5e924ae8d7fec39cb",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 1
            }
        }
    },
    {
        "description": "Bar\nBar2",
        "fingerprint": "829f6c782152fdac840b39208c5b519d18e51bff2c601b6197812fffb8bcd9ed",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 5
            }
        }
    }
]',
		];

		yield [
			'Multiple file errors, including error with line=null',
			1,
			5,
			0,
			'[
    {
        "description": "Bar\nBar2",
        "fingerprint": "034b4afbfb347494c14e396ed8327692f58be4cd27e8aff5f19f4194934db7c9",
        "severity": "major",
        "location": {
            "path": "with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
            "lines": {
                "begin": 2
            }
        }
    },
    {
        "description": "Foo",
        "fingerprint": "e82b7e1f1d4255352b19ecefa9116a12f129c7edb4351cf2319285eccdb1565e",
        "severity": "major",
        "location": {
            "path": "with space/and unicode 😃/project/folder with unicode 😃/file name with \"spaces\" and unicode 😃.php",
            "lines": {
                "begin": 4
            }
        }
    },
    {
        "description": "Bar\nBar2",
        "fingerprint": "52d22d9e64bd6c6257b7a0d170ed8c99482043aeedd68c52bac081a80da9800a",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 0
            }
        }
    },
    {
        "description": "Foo<Bar>",
        "fingerprint": "d7002959fc192c81d51fc41b0a3f240617a1aa35361867b5e924ae8d7fec39cb",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 1
            }
        }
    },
    {
        "description": "Bar\nBar2",
        "fingerprint": "829f6c782152fdac840b39208c5b519d18e51bff2c601b6197812fffb8bcd9ed",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 5
            }
        }
    }
]',
		];

		yield [
			'Multiple generic errors',
			1,
			0,
			2,
			'[
    {
        "description": "first generic error",
        "fingerprint": "53ed216d77c9a9b21d9535322457ca7d7b037d6596d76484b3481f161adfd96f",
        "severity": "major",
        "location": {
            "path": "",
            "lines": {
                "begin": 0
            }
        }
    },
    {
        "description": "second generic<error>",
        "fingerprint": "adc18b2c27b0ecad40aed7975b165cbe357f0cbba58582af91c0a2e7fa5d77ab",
        "severity": "major",
        "location": {
            "path": "",
            "lines": {
                "begin": 0
            }
        }
    }
]',
		];

		yield [
			'Multiple file, multiple generic errors',
			1,
			4,
			2,
			'[
    {
        "description": "Bar\nBar2",
        "fingerprint": "034b4afbfb347494c14e396ed8327692f58be4cd27e8aff5f19f4194934db7c9",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/folder with unicode \ud83d\ude03/file name with \"spaces\" and unicode \ud83d\ude03.php",
            "lines": {
                "begin": 2
            }
        }
    },
    {
        "description": "Foo",
        "fingerprint": "e82b7e1f1d4255352b19ecefa9116a12f129c7edb4351cf2319285eccdb1565e",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/folder with unicode \ud83d\ude03/file name with \"spaces\" and unicode \ud83d\ude03.php",
            "lines": {
                "begin": 4
            }
        }
    },
    {
        "description": "Foo<Bar>",
        "fingerprint": "d7002959fc192c81d51fc41b0a3f240617a1aa35361867b5e924ae8d7fec39cb",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 1
            }
        }
    },
    {
        "description": "Bar\nBar2",
        "fingerprint": "829f6c782152fdac840b39208c5b519d18e51bff2c601b6197812fffb8bcd9ed",
        "severity": "major",
        "location": {
            "path": "with space/and unicode \ud83d\ude03/project/foo.php",
            "lines": {
                "begin": 5
            }
        }
    },
    {
        "description": "first generic error",
        "fingerprint": "53ed216d77c9a9b21d9535322457ca7d7b037d6596d76484b3481f161adfd96f",
        "severity": "major",
        "location": {
            "path": "",
            "lines": {
                "begin": 0
            }
        }
    },
    {
        "description": "second generic<error>",
        "fingerprint": "adc18b2c27b0ecad40aed7975b165cbe357f0cbba58582af91c0a2e7fa5d77ab",
        "severity": "major",
        "location": {
            "path": "",
            "lines": {
                "begin": 0
            }
        }
    }
]',
		];
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
		$formatter = new GitlabErrorFormatter(new SimpleRelativePathHelper('/data/folder'));

		$this->assertSame($exitCode, $formatter->formatErrors(
			$this->getAnalysisResult($numFileErrors, $numGenericErrors),
			$this->getOutput(),
		), sprintf('%s: response code do not match', $message));

		$this->assertJsonStringEqualsJsonString($expected, $this->getOutputContent(), sprintf('%s: output do not match', $message));
	}

}
