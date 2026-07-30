<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\Fiber\FiberNodeScopeResolver;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\LocalIgnoresProcessor;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\DependencyInjection\DirectExtensionsCollection;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Fixable\Patcher;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use function array_map;
use function array_merge;
use function count;
use function getenv;
use function implode;
use function sprintf;
use function str_replace;
use function strcmp;
use function usort;
use const PHP_VERSION_ID;

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

	protected function createNodeScopeResolver(): NodeScopeResolver
	{
		$readWritePropertiesExtensions = $this->getReadWritePropertiesExtensions();
		$reflectionProvider = $this->createReflectionProvider();
		$typeSpecifier = $this->getTypeSpecifier();

		$enableFnsr = getenv('PHPSTAN_FNSR');
		$className = NodeScopeResolver::class;
		if (PHP_VERSION_ID >= 80100 && $enableFnsr !== '0') {
			$className = FiberNodeScopeResolver::class;
		}

		return new $className(
			self::getContainer(),
			$reflectionProvider,
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			self::getContainer()->getByType(ClassReflectionFactory::class),
			self::getContainer()->getExtensionsCollection(FunctionParameterOutTypeExtension::class),
			self::getContainer()->getExtensionsCollection(MethodParameterOutTypeExtension::class),
			self::getContainer()->getExtensionsCollection(StaticMethodParameterOutTypeExtension::class),
			$this->getParser(),
			self::getContainer()->getByType(FileTypeMapper::class),
			self::getContainer()->getByType(PhpDocInheritanceResolver::class),
			self::getContainer()->getByType(FileHelper::class),
			$typeSpecifier,
			$readWritePropertiesExtensions !== [] ? new DirectExtensionsCollection($readWritePropertiesExtensions) : self::getContainer()->getExtensionsCollection(ReadWritePropertiesExtension::class),
			self::getContainer()->getExtensionsCollection(FunctionParameterClosureThisExtension::class),
			self::getContainer()->getExtensionsCollection(MethodParameterClosureThisExtension::class),
			self::getContainer()->getExtensionsCollection(StaticMethodParameterClosureThisExtension::class),
			self::getContainer()->getExtensionsCollection(FunctionParameterClosureTypeExtension::class),
			self::getContainer()->getExtensionsCollection(MethodParameterClosureTypeExtension::class),
			self::getContainer()->getExtensionsCollection(StaticMethodParameterClosureTypeExtension::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			$this->shouldPolluteScopeWithLoopInitialAssignments(),
			$this->shouldPolluteScopeWithAlwaysIterableForeach(),
			self::getContainer()->getParameter('polluteScopeWithBlock'),
			self::getContainer()->getParameter('exceptions')['implicitThrows'],
			$this->shouldTreatPhpDocTypesAsCertain(),
			self::getContainer()->getByType(ImplicitToStringCallHelper::class),
			self::getContainer()->getByType(ExpressionResultFactory::class),
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
				self::getContainer()->getByType(DependencyResolver::class),
				self::getContainer()->getByType(PackageDependencyResolver::class),
				self::getContainer()->getExtensionsCollection(IgnoreErrorExtension::class),
				self::getContainer()->getByType(RuleErrorTransformer::class),
				new LocalIgnoresProcessor(),
				false,
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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../conf/bleedingEdge.neon',
		];
	}

}
