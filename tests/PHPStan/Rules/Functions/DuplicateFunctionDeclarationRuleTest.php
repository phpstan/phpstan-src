<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocator;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DuplicateFunctionDeclarationRule>
 */
class DuplicateFunctionDeclarationRuleTest extends RuleTestCase
{

	private const FILENAME = __DIR__ . '/data/duplicate-function.php';

	protected function getRule(): Rule
	{
		$fileHelper = new FileHelper(__DIR__ . '/data');

		return new DuplicateFunctionDeclarationRule(
			new DefaultReflector(new OptimizedSingleFileSourceLocator(
				self::getContainer()->getByType(FileNodesFetcher::class),
				self::FILENAME,
			)),
			new SimpleRelativePathHelper($fileHelper->normalizePath($fileHelper->getWorkingDirectory(), '/')),
		);
	}

	public function testRule(): void
	{
		$this->analyse([self::FILENAME], [
			[
				"Function DuplicateFunctionDeclaration\\foo declared multiple times:\n- duplicate-function.php:10\n- duplicate-function.php:15\n- duplicate-function.php:20",
				10,
			],
			[
				"Function DuplicateFunctionDeclaration\\foo declared multiple times:\n- duplicate-function.php:10\n- duplicate-function.php:15\n- duplicate-function.php:20",
				15,
			],
			[
				"Function DuplicateFunctionDeclaration\\foo declared multiple times:\n- duplicate-function.php:10\n- duplicate-function.php:15\n- duplicate-function.php:20",
				20,
			],
		]);
	}

}
