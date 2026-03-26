<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\DerivativeContainerFactory;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Type\ObjectType;
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

		$originalReflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		$originalPhpVersion = PhpVersionStaticAccessor::getInstance();

		try {
			$container = $this->derivativeContainerFactory->create([
				__DIR__ . '/../../conf/config.stubValidator.neon',
			]);

			$fileAnalyser = $container->getByType(FileAnalyser::class);

			$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
			$nodeScopeResolver->setAnalysedFiles($stubFiles);

			$pathRoutingParser = $container->getService('pathRoutingParser');
			$pathRoutingParser->setAnalysedFiles($stubFiles);

			$analysedFiles = array_fill_keys($stubFiles, true);

			$errors = [];
			foreach ($stubFiles as $stubFile) {
				try {
					$tmpErrors = $fileAnalyser->analyseFile(
						$stubFile,
						$analysedFiles,
						new DirectRuleRegistry($container->getServicesByTag(self::SERVICE_RULE_TAG)),
						new CollectorRegistry([]),
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
			ReflectionProviderStaticAccessor::registerInstance($originalReflectionProvider);
			PhpVersionStaticAccessor::registerInstance($originalPhpVersion);
			ObjectType::resetCaches();
		}

		return $errors;
	}

}
