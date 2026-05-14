<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileFix;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule<Variable>>
 */
final class PerFileBatchFixerTraitTest extends RuleTestCase
{

	/** @var Rule<Variable> */
	private Rule $rule;

	#[Override]
	protected function getRule(): Rule
	{
		return $this->rule ?? new RenameVariableFixRule();
	}

	public function testSingleConsumerTraitFixIsApplied(): void
	{
		$this->rule = new RenameVariableFixRule();

		$this->fix(
			__DIR__ . '/data/trait-single-consumer.php',
			__DIR__ . '/data/trait-single-consumer.php.fixed',
		);
	}

	public function testAgreeingMultipleConsumersTraitFixIsApplied(): void
	{
		$this->rule = new RenameVariableFixRule();

		$this->fix(
			__DIR__ . '/data/trait-agreeing-consumers.php',
			__DIR__ . '/data/trait-agreeing-consumers.php.fixed',
		);
	}

	public function testDisagreeingMultipleConsumersTraitFixIsSkipped(): void
	{
		$this->rule = new ClassAwareRenameVariableFixRule();

		$this->fix(
			__DIR__ . '/data/trait-disagreeing-consumers.php',
			__DIR__ . '/data/trait-disagreeing-consumers.php.fixed',
		);
	}

	public function testTraitWithoutConsumerIsLeftAlone(): void
	{
		$this->rule = new RenameVariableFixRule();

		$this->fix(
			__DIR__ . '/data/trait-without-consumer.php',
			__DIR__ . '/data/trait-without-consumer.php.fixed',
		);
	}

	public function testDisagreeingConsumersErrorIsMarkedFixableWithConflictTip(): void
	{
		$this->rule = new ClassAwareRenameVariableFixRule();

		[$errors, $fixesByFixingFile] = $this->gatherAnalyserErrorsAndFixes([__DIR__ . '/data/trait-disagreeing-consumers.php']);
		$skipped = self::filterFixableSkipped($errors, $fixesByFixingFile);

		self::assertNotSame([], $skipped, 'expected at least one wasFixable && !applied error');
		foreach ($skipped as $error) {
			self::assertTrue($error->wasFixable());
			self::assertFalse(self::errorIsCoveredByAnyFix($error, $fixesByFixingFile));
			$tip = $error->getTip();
			self::assertNotNull($tip);
			self::assertStringContainsString('Auto-fix skipped: trait consumers proposed conflicting rewrites.', $tip);
			self::assertStringContainsString('TraitDisagreeingConsumerOne', $tip);
			self::assertStringContainsString('TraitDisagreeingConsumerTwo', $tip);
		}
	}

	public function testSingleConsumerErrorIsAppliedAndHasNoSkipTip(): void
	{
		$this->rule = new RenameVariableFixRule();

		[$errors, $fixesByFixingFile] = $this->gatherAnalyserErrorsAndFixes([__DIR__ . '/data/trait-single-consumer.php']);

		$applied = [];
		foreach ($errors as $error) {
			if (!self::errorIsCoveredByAnyFix($error, $fixesByFixingFile)) {
				continue;
			}
			$applied[] = $error;
		}

		self::assertNotSame([], $applied, 'expected at least one applied fix on a single-consumer trait');
		foreach ($applied as $error) {
			self::assertTrue($error->wasFixable());
			$tip = $error->getTip();
			if ($tip === null) {
				continue;
			}
			self::assertStringNotContainsString('Auto-fix skipped', $tip);
		}
	}

	/**
	 * @param list<Error> $errors
	 * @param array<string, FileFix> $fixesByFixingFile
	 * @return list<Error>
	 */
	private static function filterFixableSkipped(array $errors, array $fixesByFixingFile): array
	{
		$skipped = [];
		foreach ($errors as $error) {
			if (!$error->wasFixable()) {
				continue;
			}
			if (self::errorIsCoveredByAnyFix($error, $fixesByFixingFile)) {
				continue;
			}
			$skipped[] = $error;
		}

		return $skipped;
	}

	/**
	 * @param array<string, FileFix> $fixesByFixingFile
	 */
	private static function errorIsCoveredByAnyFix(Error $error, array $fixesByFixingFile): bool
	{
		$fixingFile = $error->getTraitFilePath() ?? $error->getFilePath();
		if (!isset($fixesByFixingFile[$fixingFile])) {
			return false;
		}
		$identifier = $error->getIdentifier();
		$line = $error->getLine();
		foreach ($fixesByFixingFile[$fixingFile]->errorRefs as $ref) {
			if ($ref['line'] === $line && $ref['identifier'] === $identifier) {
				return true;
			}
		}
		return false;
	}

}
