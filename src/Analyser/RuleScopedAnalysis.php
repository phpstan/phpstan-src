<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;

/**
 * Files that are re-analysed only because a rule changed. In them only the changed rules run, no
 * collector runs, and the result cache matches the local ignores later, because it knows the
 * cached errors of the other rules.
 *
 * The main process sets it from the result cache; a parallel worker sets it from each job it gets.
 */
#[AutowiredService]
final class RuleScopedAnalysis
{

	/** @var array<string, true> */
	private array $ruleClasses = [];

	/** @var array<string, true> */
	private array $files = [];

	/**
	 * @param list<string> $ruleClasses
	 * @param list<string> $files
	 */
	public function set(array $ruleClasses, array $files): void
	{
		$this->ruleClasses = [];
		foreach ($ruleClasses as $ruleClass) {
			$this->ruleClasses[$ruleClass] = true;
		}
		$this->files = [];
		foreach ($files as $file) {
			$this->files[$file] = true;
		}
	}

	public function isScoped(string $file): bool
	{
		return isset($this->files[$file]);
	}

	/**
	 * @return array<string, true>
	 */
	public function getRuleClasses(): array
	{
		return $this->ruleClasses;
	}

}
