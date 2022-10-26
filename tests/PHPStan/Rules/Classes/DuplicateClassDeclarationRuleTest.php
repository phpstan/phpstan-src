<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocator;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DuplicateClassDeclarationRule>
 */
class DuplicateClassDeclarationRuleTest extends RuleTestCase
{

	private const FILENAME = __DIR__ . '/data/duplicate-class.php';

	protected function getRule(): Rule
	{
		return new DuplicateClassDeclarationRule(
			new DefaultReflector(new OptimizedSingleFileSourceLocator(
				self::getContainer()->getByType(FileNodesFetcher::class),
				self::FILENAME,
			)),
			new SimpleRelativePathHelper(__DIR__ . '/data'),
		);
	}

	public function testRule(): void
	{
		$this->analyse([self::FILENAME], [
			[
				"Class DuplicateClassDeclaration\Foo declared multiple times:\n- duplicate-class.php:15\n- duplicate-class.php:20",
				10,
			],
			[
				"Class DuplicateClassDeclaration\Foo declared multiple times:\n- duplicate-class.php:10\n- duplicate-class.php:20",
				15,
			],
			[
				"Class DuplicateClassDeclaration\Foo declared multiple times:\n- duplicate-class.php:10\n- duplicate-class.php:15",
				20,
			],
		]);
	}

}
