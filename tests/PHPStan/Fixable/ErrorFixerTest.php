<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\Analyser\Error;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @extends RuleTestCase<RemoveNodeRule>
 */
class ErrorFixerTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RemoveNodeRule();
	}

	public function testOverlappingFixesAreSkipped(): void
	{
		$file = tempnam(sys_get_temp_dir(), 'phpstan-error-fixer');
		try {
			$code = FileReader::read(__DIR__ . '/data/remove-node-rounds.php');
			FileWriter::write($file, $code);
			$errors = $this->gatherAnalyserErrors([$file]);
			$this->assertCount(3, $errors);
			$notFixable = new Error('Not fixable.', $file);

			$result = self::getContainer()->getByType(ErrorFixer::class)->fix([$notFixable, ...$errors]);

			// the three arguments are removed by three errors, their changes of the same line overlap
			$this->assertSame([$errors[0]], $result->getFixedErrors());
			$this->assertSame([$errors[1], $errors[2]], $result->getSkippedErrors());
			$this->assertSame([$file], $result->getChangedFiles());
			$this->assertSame(
				str_replace('doFoo(REMOVE_ARG, 1, REMOVE_ARG, REMOVE_ARG);', 'doFoo(1, REMOVE_ARG, REMOVE_ARG);', $code),
				FileReader::read($file),
			);
		} finally {
			@unlink($file);
		}
	}

}
