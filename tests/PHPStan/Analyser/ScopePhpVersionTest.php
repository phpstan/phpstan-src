<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\VerbosityLevel;

class ScopePhpVersionTest extends TypeInferenceTestCase
{

	public function dataTestPhpVersion(): array
	{
		return [
			[
				'int<80000, 80499>',
				__DIR__ . '/data/scope-constants-global.php',
			],
			[
				'int<80000, 80499>',
				__DIR__ . '/data/scope-constants-namespace.php',
			],
		];
	}

	/**
	 * @dataProvider dataTestPhpVersion
	 */
	public function testPhpVersion(string $expected, string $file): void
	{
		self::processFile($file, function (Node $node, Scope $scope) use ($expected): void {
			if (!($node instanceof Exit_)) {
				return;
			}
			$this->assertSame(
				$expected,
				$scope->getPhpVersion()->getType()->describe(VerbosityLevel::precise()),
			);
		});
	}

}
