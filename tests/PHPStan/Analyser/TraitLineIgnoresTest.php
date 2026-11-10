<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FileReader;
use PHPStan\Fixable\Patcher;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_map;
use function array_reverse;
use function basename;
use function sort;
use function str_replace;
use function str_starts_with;

class TraitLineIgnoresTest extends PHPStanTestCase
{

	private const FILES = [
		__DIR__ . '/data/trait-line-ignores/Foo.php',
		__DIR__ . '/data/trait-line-ignores/Bar.php',
		__DIR__ . '/data/trait-line-ignores/UnusedParametersTrait.php',
		__DIR__ . '/data/trait-line-ignores/PropertyTrait.php',
		__DIR__ . '/data/trait-line-ignores/ConstantConditionTrait.php',
		__DIR__ . '/data/trait-line-ignores/same-file.php',
	];

	public static function dataFileOrders(): iterable
	{
		yield [self::FILES];
		yield [array_reverse(self::FILES)];
	}

	/**
	 * @param list<string> $files
	 */
	#[DataProvider('dataFileOrders')]
	public function testIgnoreErrorsAreReportedOncePerTraitLine(array $files): void
	{
		$reported = [];
		foreach ($this->getIgnoreErrors($files) as $error) {
			$this->assertSame($error->getFile(), $error->getFilePath());
			$reported[] = basename($error->getFile()) . ':' . $error->getLine() . ' ' . $error->getIdentifier();
		}
		sort($reported);

		$this->assertSame([
			'ConstantConditionTrait.php:11 ignore.allLineErrors',
			'PropertyTrait.php:10 ignore.allLineErrors',
			'PropertyTrait.php:15 ignore.noComment',
			'UnusedParametersTrait.php:9 ignore.allLineErrors',
			'same-file.php:10 ignore.allLineErrors',
		], $reported);
	}

	/**
	 * @param list<string> $files
	 */
	#[DataProvider('dataFileOrders')]
	public function testFixIgnoresOfAllLineErrorsInTraits(array $files): void
	{
		$diffsByFile = [];
		foreach ($this->getIgnoreErrors($files) as $error) {
			$diffsByFile[$error->getFile()][] = $error->getFixedErrorDiffs();
		}

		$expected = [
			'UnusedParametersTrait.php' => [
				'@phpstan-ignore-next-line Called by the DI container',
				'@phpstan-ignore constructor.unusedParameter (Called by the DI container), constructor.unusedParameter (Called by the DI container), constructor.unusedParameter (Called by the DI container)',
			],
			'PropertyTrait.php' => [
				'@phpstan-ignore-line legacy',
				'@phpstan-ignore return.type (legacy), property.notFound (legacy)',
			],
			'ConstantConditionTrait.php' => [
				'@phpstan-ignore-next-line legacy',
				'@phpstan-ignore function.alreadyNarrowedType (legacy), method.notFound (legacy)',
			],
			'same-file.php' => [
				'@phpstan-ignore-line legacy',
				'@phpstan-ignore return.type (legacy), property.notFound (legacy)',
			],
		];
		foreach ($expected as $fileName => [$search, $replace]) {
			$file = $this->getFileHelper()->normalizePath(__DIR__ . '/data/trait-line-ignores/' . $fileName);
			$contents = FileReader::read($file);
			$patched = (new Patcher())->apply([$file => $contents], $diffsByFile[$file] ?? []);
			$this->assertSame(str_replace($search, $replace, $contents), $patched['contents'][$file] ?? $contents, $fileName);
		}
	}

	/**
	 * @param list<string> $files
	 * @return list<Error>
	 */
	private function getIgnoreErrors(array $files): array
	{
		$files = array_map(fn (string $file): string => $this->getFileHelper()->normalizePath($file), $files);
		$analyser = self::getContainer()->getByType(Analyser::class);
		$finalizer = self::getContainer()->getByType(AnalyserResultFinalizer::class);
		$errors = $finalizer->finalize($analyser->analyse($files), false, true)->getAnalyserResult()->getUnorderedErrors();

		$ignoreErrors = [];
		foreach ($errors as $error) {
			if (!str_starts_with((string) $error->getIdentifier(), 'ignore.')) {
				continue;
			}

			$ignoreErrors[] = $error;
		}

		return $ignoreErrors;
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/trait-line-ignores.neon',
		];
	}

}
