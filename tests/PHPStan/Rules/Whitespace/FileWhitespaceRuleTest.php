<?php declare(strict_types = 1);

namespace PHPStan\Rules\Whitespace;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FileWhitespaceRule>
 */
class FileWhitespaceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FileWhitespaceRule();
	}

	public function testBom(): void
	{
		$this->analyse([__DIR__ . '/data/bom.php'], [
			[
				'File begins with UTF-8 BOM character. This may cause problems when running the code in the web browser.',
				1,
			],
		]);
	}

	public function testCorrectFile(): void
	{
		$this->analyse([__DIR__ . '/data/correct.php'], []);
	}

	public function testTrailingWhitespaceWithoutNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/trailing.php'], [
			[
				'File ends with a trailing whitespace. This may cause problems when running the code in the web browser. Remove the closing ?> mark or remove the whitespace.',
				6,
			],
		]);
	}

	public function testTrailingWhitespace(): void
	{
		$this->analyse([__DIR__ . '/data/trailing-namespace.php'], [
			[
				'File ends with a trailing whitespace. This may cause problems when running the code in the web browser. Remove the closing ?> mark or remove the whitespace.',
				8,
			],
		]);
	}

}
