<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule<\PhpParser\Node\Expr\Variable>>
 */
final class PerFileBatchFixerTraitTest extends RuleTestCase
{

	/** @var Rule<\PhpParser\Node\Expr\Variable> */
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

		$errors = $this->gatherAnalyserErrors([__DIR__ . '/data/trait-disagreeing-consumers.php']);
		$skipped = self::filterFixableSkipped($errors);

		self::assertNotSame([], $skipped, 'expected at least one wasFixable && !applied error');
		foreach ($skipped as $error) {
			self::assertTrue($error->wasFixable());
			self::assertNull($error->getFixedErrorDiff());
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

		$errors = $this->gatherAnalyserErrors([__DIR__ . '/data/trait-single-consumer.php']);

		$applied = [];
		foreach ($errors as $error) {
			if ($error->getFixedErrorDiff() === null) {
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
	 * @param list<\PHPStan\Analyser\Error> $errors
	 * @return list<\PHPStan\Analyser\Error>
	 */
	private static function filterFixableSkipped(array $errors): array
	{
		$skipped = [];
		foreach ($errors as $error) {
			if (!$error->wasFixable()) {
				continue;
			}
			if ($error->getFixedErrorDiff() !== null) {
				continue;
			}
			$skipped[] = $error;
		}

		return $skipped;
	}

}
