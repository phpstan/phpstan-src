<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Nette\Utils\FileSystem;
use PhpParser\Node;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\FixedErrorDiff;
use PHPStan\Analyser\Generics\TemplateArgumentObserver;
use PHPStan\Analyser\Ignore\UnmatchedIgnoreFixer;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\LocalIgnoresProcessor;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\PerFileAnalysisResettable;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\StatementsHandler;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Fixable\Patcher;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function array_combine;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function implode;
use function sprintf;
use function str_replace;
use function strcmp;
use function sys_get_temp_dir;
use function uniqid;
use function usort;

/**
 * @api
 * @template TRule of Rule
 */
abstract class RuleTestCase extends PHPStanTestCase
{

	private const FIX_ROUNDS_LIMIT = 10;

	private ?Analyser $analyser = null;

	/**
	 * @return TRule
	 */
	abstract protected function getRule(): Rule;

	/**
	 * @return array<Collector<Node, mixed>>
	 */
	protected function getCollectors(): array
	{
		return [];
	}

	/**
	 * No longer configures the analysis - register the extensions as services
	 * in a config file returned from getAdditionalConfigFiles() instead.
	 * Returning any extension fails the test.
	 *
	 * @return ReadWritePropertiesExtension[]
	 */
	protected function getReadWritePropertiesExtensions(): array
	{
		return [];
	}

	protected function getTypeSpecifier(): TypeSpecifier
	{
		return self::getContainer()->getService('typeSpecifier');
	}

	protected function createNodeScopeResolver(): NodeScopeResolver
	{
		if (
			$this->getReadWritePropertiesExtensions() !== []
			|| $this->shouldPolluteScopeWithLoopInitialAssignments() !== self::getContainer()->getParameter('polluteScopeWithLoopInitialAssignments')
			|| $this->shouldPolluteScopeWithAlwaysIterableForeach() !== self::getContainer()->getParameter('polluteScopeWithAlwaysIterableForeach')
		) {
			throw new ShouldNotHappenException('getReadWritePropertiesExtensions(), shouldPolluteScopeWithLoopInitialAssignments() and shouldPolluteScopeWithAlwaysIterableForeach() no longer configure the analysis. Register the extensions as services and set the parameters in a config file returned from getAdditionalConfigFiles() instead.');
		}

		return new NodeScopeResolver(
			self::getContainer(),
			self::getContainer()->getByType(TemplateArgumentObserver::class),
			self::getContainer()->getByType(FileHelper::class),
			self::getContainer()->getExtensionsCollection(PerFileAnalysisResettable::class),
			self::getContainer()->getByType(ExpressionResultFactory::class),
			self::getContainer()->getByType(StatementsHandler::class),
		);
	}

	private function getAnalyser(DirectRuleRegistry $ruleRegistry): Analyser
	{
		if ($this->analyser === null) {
			$collectorRegistry = new CollectorRegistry($this->getCollectors());

			$nodeScopeResolver = $this->createNodeScopeResolver();

			$fileAnalyser = new FileAnalyser(
				self::createScopeFactory(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
				),
				$nodeScopeResolver,
				$this->getParser(),
				self::getContainer()->getExtensionsCollection(IgnoreErrorExtension::class),
				self::getContainer()->getByType(RuleErrorTransformer::class),
				new LocalIgnoresProcessor(),
				false,
				self::getContainer()->getByType(ValueDependencyCollector::class),
				self::getContainer()->getByType(DependencyResolver::class),
			);
			$this->analyser = new Analyser(
				$fileAnalyser,
				$ruleRegistry,
				$collectorRegistry,
				$nodeScopeResolver,
				50,
			);
		}

		return $this->analyser;
	}

	/**
	 * @param string[] $files
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	public function analyse(array $files, array $expectedErrors): void
	{
		[$actualErrors, $delayedErrors] = $this->gatherAnalyserErrorsWithDelayedErrors($files);
		$strictlyTypedSprintf = static function (int $line, string $message, ?string $tip): string {
			$message = sprintf('%02d: %s', $line, $message);
			if ($tip !== null) {
				$message .= "\n    💡 " . $tip;
			}

			return $message;
		};

		usort($expectedErrors, static function ($a, $b) {
			if ($a[1] !== $b[1]) {
				return $a[1] <=> $b[1];
			}

			if ($a[0] !== $b[0]) {
				return strcmp($a[0], $b[0]);
			}

			if (!isset($a[2])) {
				if (!isset($b[2])) {
					return 0;
				}

				return 1;
			} elseif (!isset($b[2])) {
				return -1;
			}

			return strcmp($a[2], $b[2]);
		});

		$expectedErrors = array_map(
			static fn (array $error): string => $strictlyTypedSprintf($error[1], $error[0], $error[2] ?? null),
			$expectedErrors,
		);

		usort($actualErrors, static function ($a, $b) {
			if ($a->getLine() !== $b->getLine()) {
				return $a->getLine() <=> $b->getLine();
			}

			if ($a->getMessage() !== $b->getMessage()) {
				return strcmp($a->getMessage(), $b->getMessage());
			}

			if ($a->getTip() === null) {
				if ($b->getTip() === null) {
					return 0;
				}

				return 1;
			} elseif ($b->getTip() === null) {
				return -1;
			}

			return strcmp($a->getTip(), $b->getTip());
		});

		$actualErrors = array_map(
			static function (Error $error) use ($strictlyTypedSprintf): string {
				$line = $error->getLine();
				if ($line === null) {
					return $strictlyTypedSprintf(-1, $error->getMessage(), $error->getTip());
				}
				return $strictlyTypedSprintf($line, $error->getMessage(), $error->getTip());
			},
			$actualErrors,
		);

		$expectedErrorsString = implode("\n", $expectedErrors) . "\n";
		$actualErrorsString = implode("\n", $actualErrors) . "\n";

		if (count($delayedErrors) === 0) {
			$this->assertSame($expectedErrorsString, $actualErrorsString);
			return;
		}

		if ($expectedErrorsString === $actualErrorsString) {
			$this->assertSame($expectedErrorsString, $actualErrorsString);
			return;
		}

		$actualErrorsString .= sprintf(
			"\n%s might be reported because of the following misconfiguration %s:\n\n",
			count($actualErrors) === 1 ? 'This error' : 'These errors',
			count($delayedErrors) === 1 ? 'issue' : 'issues',
		);

		foreach ($delayedErrors as $delayedError) {
			$actualErrorsString .= sprintf("* %s\n", $delayedError->getMessage());
		}

		$this->assertSame($expectedErrorsString, $actualErrorsString);
	}

	/**
	 * Like `phpstan analyse --fix`, the fixed code is analysed again until nothing is left to fix,
	 * so fixes that overlap with other fixes and fixes of errors caused by other fixes are applied too.
	 * A fix that is fixable again fails the test, it would not finish with --fix either.
	 */
	public function fix(string $file, string $expectedFile): void
	{
		$this->fixOtherFile($file, $file, $expectedFile);
	}

	/**
	 * Analyses $file and $fixedFile together and asserts the fixes of $fixedFile,
	 * like a trait file or a file the rule points to with RuleErrorBuilder::file().
	 *
	 * Fixes in rounds like fix(). Later rounds analyse copies of the files in a temporary directory,
	 * so they do not fix traits, which are always read from their original file.
	 */
	public function fixOtherFile(string $file, string $fixedFile, string $expectedFile): void
	{
		$fileHelper = $this->getFileHelper();
		$fixedFile = $fileHelper->normalizePath($fixedFile);
		// a trait is analysed only when its file is analysed too
		$analysedFiles = array_values(array_unique([$fileHelper->normalizePath($file), $fixedFile]));
		$contentsByFile = [$fixedFile => FileReader::read($fixedFile)];
		// original path => path analysed in the current round
		$roundPaths = array_combine($analysedFiles, $analysedFiles);
		$patcher = self::getContainer()->getByType(Patcher::class);
		$tmpDir = sys_get_temp_dir() . '/' . uniqid('phpstan-fix-', true);

		try {
			for ($round = 1;; $round++) {
				[$errors] = $this->gatherAnalyserErrorsWithDelayedErrors(array_values(array_intersect_key($roundPaths, array_flip($analysedFiles))));
				$originalPaths = array_flip($roundPaths);
				$diffGroups = [];
				foreach ($errors as $error) {
					$diffs = [];
					foreach ($error->getFixedErrorDiffs() as $diff) {
						// the fix of an error applies to all its files or none, all of them take part
						$diffFile = $fileHelper->normalizePath($diff->file);
						if ($round > 1) {
							if (!array_key_exists($diffFile, $originalPaths)) {
								// a trait read from its original file
								continue 2;
							}
							$diffFile = $originalPaths[$diffFile];
						}
						$contentsByFile[$diffFile] ??= FileReader::read($diffFile);
						$diffs[] = new FixedErrorDiff($diffFile, $diff->originalHash, $diff->diff);
					}
					if ($diffs === []) {
						continue;
					}

					$diffGroups[] = $diffs;
				}

				if ($diffGroups === []) {
					break;
				}

				$patchResult = $patcher->apply($contentsByFile, $diffGroups);
				$this->assertGreaterThan(0, $patchResult['appliedCount'], 'Some fixes overlap and could not be applied together.');
				$this->assertLessThan(self::FIX_ROUNDS_LIMIT, $round, sprintf('Fixing did not finish after %d rounds.', $round));
				$contentsByFile = $patchResult['contents'];

				// a new directory every round, so nothing cached for the contents of an earlier round is reused;
				// files stay next to each other, unless their names clash
				$roundDir = $tmpDir . '/' . $round;
				$usedNames = [];
				foreach ($contentsByFile as $originalPath => $contents) {
					$name = basename($originalPath);
					$roundPath = isset($usedNames[$name]) ? sprintf('%s/%d/%s', $roundDir, count($usedNames), $name) : $roundDir . '/' . $name;
					$usedNames[$name] = true;
					$roundPaths[$originalPath] = $fileHelper->normalizePath($roundPath);
					FileSystem::write($roundPaths[$originalPath], $contents);
				}
			}
		} finally {
			FileSystem::delete($tmpDir);
		}

		$fixedFileContents = FileReader::read($expectedFile);

		$this->assertSame($this->normalizeLineEndings($fixedFileContents), $this->normalizeLineEndings($contentsByFile[$fixedFile]));
	}

	private function normalizeLineEndings(string $string): string
	{
		return str_replace("\r\n", "\n", $string);
	}

	/**
	 * @param string[] $files
	 * @return list<Error>
	 */
	public function gatherAnalyserErrors(array $files): array
	{
		return $this->gatherAnalyserErrorsWithDelayedErrors($files)[0];
	}

	/**
	 * @param string[] $files
	 * @return array{list<Error>, list<IdentifierRuleError>}
	 */
	private function gatherAnalyserErrorsWithDelayedErrors(array $files): array
	{
		$reflectionProvider = $this->createReflectionProvider();
		$classRule = new DelayedRule(new NonexistentAnalysedClassRule($reflectionProvider));
		$traitRule = new DelayedRule(new NonexistentAnalysedTraitRule($reflectionProvider));
		$ruleRegistry = new DirectRuleRegistry([
			$this->getRule(),
			$classRule,
			$traitRule,
		]);
		$files = array_map([$this->getFileHelper(), 'normalizePath'], $files);
		$analyserResult = $this->getAnalyser($ruleRegistry)->analyse(
			$files,
			null,
			null,
			true,
		);
		if (count($analyserResult->getInternalErrors()) > 0) {
			$this->fail(implode("\n", array_map(static fn (InternalError $internalError) => $internalError->getMessage(), $analyserResult->getInternalErrors())));
		}

		if ($this->shouldFailOnPhpErrors() && count($analyserResult->getAllPhpErrors()) > 0) {
			$this->fail(implode("\n", array_map(
				static fn (Error $error): string => sprintf('%s on %s:%d', $error->getMessage(), $error->getFile(), $error->getLine() ?? 0),
				$analyserResult->getAllPhpErrors(),
			)));
		}

		$finalizer = new AnalyserResultFinalizer(
			$ruleRegistry,
			self::getContainer()->getExtensionsCollection(IgnoreErrorExtension::class),
			self::getContainer()->getByType(RuleErrorTransformer::class),
			self::createScopeFactory($reflectionProvider, self::getContainer()->getService('typeSpecifier')),
			new LocalIgnoresProcessor(),
			self::getContainer()->getByType(UnmatchedIgnoreFixer::class),
			true,
			self::getContainer()->getByType(ValueDependencyCollector::class),
			false,
		);

		return [
			$finalizer->finalize($analyserResult, false, true)->getAnalyserResult()->getUnorderedErrors(),
			array_merge($classRule->getDelayedErrors(), $traitRule->getDelayedErrors()),
		];
	}

	/**
	 * No longer configures the analysis - set the polluteScopeWithLoopInitialAssignments
	 * parameter in a config file returned from getAdditionalConfigFiles() instead.
	 * Returning a different value than the parameter fails the test.
	 */
	protected function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return self::getContainer()->getParameter('polluteScopeWithLoopInitialAssignments');
	}

	/**
	 * No longer configures the analysis - set the polluteScopeWithAlwaysIterableForeach
	 * parameter in a config file returned from getAdditionalConfigFiles() instead.
	 * Returning a different value than the parameter fails the test.
	 */
	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return self::getContainer()->getParameter('polluteScopeWithAlwaysIterableForeach');
	}

	protected function shouldFailOnPhpErrors(): bool
	{
		return true;
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../conf/bleedingEdge.neon',
		];
	}

}
