<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\DerivativeContainerFactory;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use Throwable;
use function array_fill_keys;
use function count;
use function sprintf;

#[AutowiredService]
final class StubValidator
{

	public const SERVICE_RULE_TAG = 'phpstan.stubValidator.rule';

	public function __construct(
		private DerivativeContainerFactory $derivativeContainerFactory,
		private Container $mainContainer,
		private StubFilesProvider $stubFilesProvider,
	)
	{
	}

	/**
	 * @param string[] $stubFiles
	 * @return list<Error>
	 */
	public function validate(array $stubFiles, bool $debug): array
	{
		if (count($stubFiles) === 0) {
			return [];
		}

		try {
			$container = $this->derivativeContainerFactory->create([
				__DIR__ . '/../../conf/config.stubValidator.neon',
			], [
				'allStubFiles' => $this->stubFilesProvider->getStubFiles(),
			]);

			$fileAnalyser = $container->getByType(FileAnalyser::class);

			$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
			$nodeScopeResolver->setAnalysedFiles($stubFiles);

			$pathRoutingParser = $container->getService('pathRoutingParser');
			$pathRoutingParser->setAnalysedFiles($stubFiles);

			$analysedFiles = array_fill_keys($stubFiles, true);

			$ruleRegistry = new DirectRuleRegistry($container->getServicesByTag(self::SERVICE_RULE_TAG));
			$collectorRegistry = new CollectorRegistry([]);

			$errors = [];
			foreach ($stubFiles as $stubFile) {
				try {
					$tmpErrors = $fileAnalyser->analyseFile(
						$stubFile,
						$analysedFiles,
						$ruleRegistry,
						$collectorRegistry,
						static function (): void {
						},
					)->getErrors();
					foreach ($tmpErrors as $tmpError) {
						$errors[] = $tmpError->withoutTip()->doNotIgnore();
					}
				} catch (Throwable $e) {
					if ($debug) {
						throw $e;
					}

					$internalErrorMessage = sprintf('Internal error: %s', $e->getMessage());
					$errors[] = (new Error($internalErrorMessage, $stubFile, canBeIgnored: $e))
						->withIdentifier('phpstan.internal')
						->withMetadata([
							InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
							InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
						]);
				}
			}
		} finally {
			// Creating the derived container above re-ran ContainerFactory::postInitializeContainer(),
			// pointing all process-wide statics (ReflectionProviderStaticAccessor,
			// BetterReflection::populate() with the source locator/reflector/stubber, bleeding-edge
			// toggles, ...) at the derived container. Re-initialize them from the main container —
			// previously only the two accessors were restored, so the BetterReflection statics kept
			// the whole derived container alive for the rest of the process, and reflections created
			// through them afterwards (e.g. adapter-internal lookups during result finalization)
			// were built by the derived container's reflector.
			ContainerFactory::postInitializeContainer($this->mainContainer);
		}

		return $errors;
	}

}
