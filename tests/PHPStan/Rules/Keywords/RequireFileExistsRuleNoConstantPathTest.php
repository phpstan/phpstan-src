<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequireFileExistsRule>
 */
class RequireFileExistsRuleNoConstantPathTest extends RuleTestCase
{

	private string $currentWorkingDirectory = __DIR__ . '/../';

	protected function getRule(): Rule
	{
		return new RequireFileExistsRule(
			$this->currentWorkingDirectory,
			self::getContainer()->getByType(ExprPrinter::class),
		);
	}

	public function testBug12203NoConstantPath(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12203.php'], [
			[
				'Path in require_once() "../bug-12203-sure-does-not-exist.php" is not a file or it does not exist.',
				5,
			],
			[
				"Path in require_once() __DIR__ . '/../bug-12203-sure-does-not-exist.php' is not a file or it does not exist.",
				6,
			],
			[
				"Path in require_once() __DIR__ . '/' . \$path . '/' . \$file is not a file or it does not exist.",
				10,
			],
			[
				'Path in require_once() __DIR__ . "{$path}/{$file}" is not a file or it does not exist.',
				12,
			],
		]);
	}

	public function testInFileExists(): void
	{
		$this->analyse([__DIR__ . '/data/include-in-file-exists.php'], []);
	}

}
