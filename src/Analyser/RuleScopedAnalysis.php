<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use function array_filter;
use function array_values;
use function file_get_contents;
use function getenv;
use function in_array;
use function is_array;
use function json_decode;

/**
 * Files that are re-analysed only because a rule changed. In them only the changed rules run, no
 * collector runs, and the local ignores are matched later by the result cache, which knows the
 * cached errors of the other rules.
 *
 * Parallel workers read the state from the file that the environment variable points at.
 */
#[AutowiredService]
final class RuleScopedAnalysis
{

	public const ENVIRONMENT_VARIABLE = 'PHPSTAN_RULE_SCOPED_ANALYSIS';

	/** @var array<string, true> */
	private array $ruleClasses = [];

	/** @var array<string, true> */
	private array $files = [];

	private bool $loaded = false;

	/**
	 * @param list<string> $ruleClasses
	 * @param list<string> $files
	 */
	public function set(array $ruleClasses, array $files): void
	{
		$this->loaded = true;
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
		$this->load();

		return isset($this->files[$file]);
	}

	/**
	 * @return array<string, true>
	 */
	public function getRuleClasses(): array
	{
		$this->load();

		return $this->ruleClasses;
	}

	private function load(): void
	{
		if ($this->loaded) {
			return;
		}

		$this->loaded = true;
		$path = getenv(self::ENVIRONMENT_VARIABLE);
		if (in_array($path, [false, ''], true)) {
			return;
		}

		$contents = @file_get_contents($path);
		if ($contents === false) {
			return;
		}

		$data = json_decode($contents, true);
		if (!is_array($data) || !is_array($data['ruleClasses'] ?? null) || !is_array($data['files'] ?? null)) {
			return;
		}

		$this->set(
			array_values(array_filter($data['ruleClasses'], 'is_string')),
			array_values(array_filter($data['files'], 'is_string')),
		);
	}

}
