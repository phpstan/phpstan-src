<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Name;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class ScopePhpVersionTest extends TypeInferenceTestCase
{

	public static function dataTestPhpVersion(): array
	{
		return [
			[
				'int<80000, 80699>',
				__DIR__ . '/data/scope-constants-global.php',
			],
			[
				'int<80000, 80699>',
				__DIR__ . '/data/scope-constants-namespace.php',
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

	public function testPhpVersionFollowsExpressionTypesWrittenInPlace(): void
	{
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__));
		$unnarrowed = $scope->getPhpVersion()->getType()->describe(VerbosityLevel::precise());

		$narrowed = $scope->assignExpression(
			new ConstFetch(new Name('PHP_VERSION_ID')),
			IntegerRangeType::fromInterval(80100, 80199),
			new IntegerType(),
		);
		$this->assertNotSame('int<80100, 80199>', $unnarrowed);

		$scope->expressionTypes = $narrowed->expressionTypes;
		$this->assertSame('int<80100, 80199>', $scope->getPhpVersion()->getType()->describe(VerbosityLevel::precise()));
	}

}
