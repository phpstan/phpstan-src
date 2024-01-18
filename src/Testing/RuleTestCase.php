<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * @api
 * @template TRule of Rule
 */
abstract class RuleTestCase extends PHPStanTestCase
{

	private ?Analyser $analyser = null;

	/**
	 * @phpstan-return TRule
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

	private function getAnalyser(): Analyser
	{
		if ($this->analyser === null) {
			$ruleRegistry = new DirectRuleRegistry([
				$this->getRule(),
			]);
			$collectorRegistry = new CollectorRegistry($this->getCollectors());

			$reflectionProvider = $this->createReflectionProvider();
			$typeSpecifier = $this->getTypeSpecifier();

			$readWritePropertiesExtensions = $this->getReadWritePropertiesExtensions();
			$nodeScopeResolver = new NodeScopeResolver(
				$reflectionProvider,
				self::getContainer()->getByType(InitializerExprTypeResolver::class),
				self::getReflector(),
				self::getClassReflectionExtensionRegistryProvider(),
				$this->getParser(),
				self::getContainer()->getByType(FileTypeMapper::class),
				self::getContainer()->getByType(StubPhpDocProvider::class),
				self::getContainer()->getByType(PhpVersion::class),
				self::getContainer()->getByType(SignatureMapProvider::class),
				self::getContainer()->getByType(PhpDocInheritanceResolver::class),
				self::getContainer()->getByType(FileHelper::class),
				$typeSpecifier,
				self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
				$readWritePropertiesExtensions !== [] ? new DirectReadWritePropertiesExtensionProvider($readWritePropertiesExtensions) : self::getContainer()->getByType(ReadWritePropertiesExtensionProvider::class),
				self::createScopeFactory($reflectionProvider, $typeSpecifier),
				$this->shouldPolluteScopeWithLoopInitialAssignments(),
				$this->shouldPolluteScopeWithAlwaysIterableForeach(),
				[],
				[],
				self::getContainer()->getParameter('universalObjectCratesClasses'),
				self::getContainer()->getParameter('exceptions')['implicitThrows'],
				$this->shouldTreatPhpDocTypesAsCertain(),
				self::getContainer()->getParameter('featureToggles')['detectDeadTypeInMultiCatch'],
			);
			$fileAnalyser = new FileAnalyser(
				$this->createScopeFactory($reflectionProvider, $typeSpecifier),
				$nodeScopeResolver,
				$this->getParser(),
				self::getContainer()->getByType(DependencyResolver::class),
				new RuleErrorTransformer(),
				true,
			);
			$this->analyser = new Analyser(
				$fileAnalyser,
				$ruleRegistry,
				$collectorRegistry,
				$nodeScopeResolver,
				50,
				$reflectionProvider,
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
		$actualErrors = $this->gatherAnalyserErrors($files);
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

		$this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
	}

	/**
	 * @param string[] $files
	 * @return list<Error>
	 */
	public function gatherAnalyserErrors(array $files): array
	{
		$files = array_map([$this->getFileHelper(), 'normalizePath'], $files);
		$analyserResult = $this->getAnalyser()->analyse(
			$files,
			null,
			null,
			true,
		);
		if (count($analyserResult->getInternalErrors()) > 0) {
			$this->fail(implode("\n", $analyserResult->getInternalErrors()));
		}

		$actualErrors = $analyserResult->getUnorderedErrors();
		$ruleErrorTransformer = new RuleErrorTransformer();
		if (count($analyserResult->getCollectedData()) > 0) {
			$ruleRegistry = new DirectRuleRegistry([
				$this->getRule(),
			]);

			$nodeType = CollectedDataNode::class;
			$node = new CollectedDataNode($analyserResult->getCollectedData(), false);
			$scopeFactory = $this->createScopeFactory($this->createReflectionProvider(), $this->getTypeSpecifier());
			$scope = $scopeFactory->create(ScopeContext::create('irrelevant'));
			foreach ($ruleRegistry->getRules($nodeType) as $rule) {
				$ruleErrors = $rule->processNode($node, $scope);
				foreach ($ruleErrors as $ruleError) {
					$actualErrors[] = $ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getLine());
				}
			}
		}

		return $actualErrors;
	}

	protected function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return false;
	}

	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
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
