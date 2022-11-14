<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class PolluteScopeWithLoopInitialAssignmentsTest extends TypeInferenceTestCase
{

	private bool $polluteScopeWithLoopInitialAssignments;

	protected function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return $this->polluteScopeWithLoopInitialAssignments;
	}

	public function dataPolluteTrue(): iterable
	{
		$this->polluteScopeWithLoopInitialAssignments = true;
		yield from $this->gatherAssertTypes(__DIR__ . '/data/pollute-scope-with-loop-initial-assignments-true.php');
	}

	/**
	 * @dataProvider dataPolluteTrue
	 * @param mixed ...$args
	 */
	public function testPolluteTrue(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public function dataPolluteFalse(): iterable
	{
		$this->polluteScopeWithLoopInitialAssignments = false;
		yield from $this->gatherAssertTypes(__DIR__ . '/data/pollute-scope-with-loop-initial-assignments-false.php');
	}

	/**
	 * @dataProvider dataPolluteFalse
	 * @param mixed ...$args
	 */
	public function testPolluteFalse(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

}
