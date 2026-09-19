<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Exit_;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class ScopePhpVersionComposerRangeTest extends TypeInferenceTestCase
{

	public static function getComposerAutoloaderProjectPaths(): array
	{
		return [__DIR__ . '/data/composer-require-php-7-and-8'];
	}

	public static function dataTestPhpVersion(): array
	{
		return [
			[
				'int<70400, 80699>',
				__DIR__ . '/data/scope-constants-composer-range.php',
			],
			[
				'int<80000, 80699>',
				__DIR__ . '/data/scope-constants-composer-range-narrowed.php',
			],
		];
	}

	#[DataProvider('dataTestPhpVersion')]
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
