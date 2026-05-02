<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RenameVariableFixRule>
 */
final class PerFileBatchFixerIgnoreAwarenessInlineTest extends RuleTestCase
{

	#[Override]
	protected function getRule(): Rule
	{
		return new RenameVariableFixRule();
	}

	public function testInlineAnnotationDropsOnlyAnnotatedNode(): void
	{
		$this->fix(
			__DIR__ . '/data/ignore-aware-inline-annotation.php',
			__DIR__ . '/data/ignore-aware-inline-annotation.php.fixed',
		);
	}

}
