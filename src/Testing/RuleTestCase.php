<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\IgnoreErrorExtensionProvider;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\LocalIgnoresProcessor;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Fixable\Patcher;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use function array_map;
use function array_merge;
use function count;
use function implode;
use function sprintf;
use function str_replace;

/**
 * @api
 * @template TRule of Rule
 */
abstract class RuleTestCase extends PHPStanTestCase
{

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

	private function getAnalyser(DirectRuleRegistry $ruleRegistry): Analyser
	{
		if ($this->analyser === null) {
			$collectorRegistry = new CollectorRegistry($this->getCollectors());

			$reflectionProvider = $this->createReflectionProvider();
			$typeSpecifier = $this->getTypeSpecifier();

			$readWritePropertiesExtensions = $this->getReadWritePropertiesExtensions();
			$nodeScopeResolver = new NodeScopeResolver(
				$reflectionProvider,
				self::getContainer()->getByType(InitializerExprTypeResolver::class),
				self::getReflector(),
				self::getClassReflectionExtensionRegistryProvider(),
				self::getContainer()->getByType(ParameterOutTypeExtensionProvider::class),
				$this->getParser(),
				self::getContainer()->getByType(FileTypeMapper::class),
				self::getContainer()->getByType(StubPhpDocProvider::class),
				self::getContainer()->getByType(PhpVersion::class),
				self::getContainer()->getByType(SignatureMapProvider::class),
				self::getContainer()->getByType(DeprecationProvider::class),
				self::getContainer()->getByType(AttributeReflectionFactory::class),
				self::getContainer()->getByType(PhpDocInheritanceResolver::class),
				self::getContainer()->getByType(FileHelper::class),
				$typeSpecifier,
				self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
				$readWritePropertiesExtensions !== [] ? new DirectReadWritePropertiesExtensionProvider($readWritePropertiesExtensions) : self::getContainer()->getByType(ReadWritePropertiesExtensionProvider::class),
				self::getContainer()->getByType(ParameterClosureTypeExtensionProvider::class),
				self::createScopeFactory($reflectionProvider, $typeSpecifier),
				$this->shouldPolluteScopeWithLoopInitialAssignments(),
				$this->shouldPolluteScopeWithAlwaysIterableForeach(),
				self::getContainer()->getParameter('polluteScopeWithBlock'),
				[],
				[],
				self::getContainer()->getParameter('universalObjectCratesClasses'),
				self::getContainer()->getParameter('exceptions')['implicitThrows'],
				$this->shouldTreatPhpDocTypesAsCertain(),
				$this->shouldNarrowMethodScopeFromConstructor(),
			);
			$fileAnalyser = new FileAnalyser(
				$this->createScopeFactory($reflectionProvider, $typeSpecifier),
				$nodeScopeResolver,
				$this->getParser(),
				self::getContainer()->getByType(DependencyResolver::class),
				new IgnoreErrorExtensionProvider(self::getContainer()),
				self::getContainer()->getByType(RuleErrorTransformer::class),
				new LocalIgnoresProcessor(),
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

		$expectedErrors = array_map(
			static fn (array $error): string => $strictlyTypedSprintf($error[1], $error[0], $error[2] ?? null),
			$expectedErrors,
		);

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

	public function fix(string $file, string $expectedFile): void
	{
		[$errors] = $this->gatherAnalyserErrorsWithDelayedErrors([$file]);
		$diffs = [];
		foreach ($errors as $error) {
			if ($error->getFixedErrorDiff() === null) {
				continue;
			}
			$diffs[] = $error->getFixedErrorDiff();
		}

		$patcher = self::getContainer()->getByType(Patcher::class);
		$newFileContents = $patcher->applyDiffs($file, $diffs); // @phpstan-ignore missingType.checkedException, missingType.checkedException

		$fixedFileContents = FileReader::read($expectedFile);

		$this->assertSame($this->normalizeLineEndings($fixedFileContents), $this->normalizeLineEndings($newFileContents));
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
				static fn (Error $error): string => sprintf('%s on %s:%d', $error->getMessage(), $error->getFile(), $error->getLine()),
				$analyserResult->getAllPhpErrors(),
			)));
		}

		$finalizer = new AnalyserResultFinalizer(
			$ruleRegistry,
			new IgnoreErrorExtensionProvider(self::getContainer()),
			self::getContainer()->getByType(RuleErrorTransformer::class),
			$this->createScopeFactory($reflectionProvider, $this->getTypeSpecifier()),
			new LocalIgnoresProcessor(),
			true,
		);

		return [
			$finalizer->finalize($analyserResult, false, true)->getAnalyserResult()->getUnorderedErrors(),
			array_merge($classRule->getDelayedErrors(), $traitRule->getDelayedErrors()),
		];
	}

	protected function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return true;
	}

	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return true;
	}

	protected function shouldFailOnPhpErrors(): bool
	{
		return true;
	}

	protected function shouldNarrowMethodScopeFromConstructor(): bool
	{
		return false;
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../conf/bleedingEdge.neon',
		];
	}

}
