<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\PHPStanTestCase;

class Bug8579Test extends PHPStanTestCase
{

	public function testBug8579(): void
	{
		$file = $this->getFileHelper()->normalizePath(__DIR__ . '/data/bug-8579.php');

		$analyser = self::getContainer()->getByType(Analyser::class);
		$finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class);
		$errors = $finalizer->finalize(
			$analyser->analyse([$file], null, null, true),
			false,
			true,
		)->getErrors();
		$this->assertNoErrors($errors);
	}

	public function testClassExistsFalseNotAlwaysRemembered(): void
	{
		$file = $this->getFileHelper()->normalizePath(__DIR__ . '/data/bug-8579-false-not-remembered.php');

		$analyser = self::getContainer()->getByType(Analyser::class);
		$finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class);
		$errors = $finalizer->finalize(
			$analyser->analyse([$file], null, null, true),
			false,
			true,
		)->getErrors();
		$this->assertNoErrors($errors);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/do-not-remember-possibly-impure-function-values.neon',
		];
	}

}
