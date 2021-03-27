<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Dependency\ExportedNodeResolver;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\Rules\Registry;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

/**
 * @template TRule of \PHPStan\Rules\Rule
 */
abstract class RuleTestCase extends \PHPStan\Testing\TestCase
{

	private ?\PHPStan\Analyser\Analyser $analyser = null;

	/**
	 * @return \PHPStan\Rules\Rule
	 * @phpstan-return TRule
	 */
	abstract protected function getRule(): Rule;

	protected function getTypeSpecifier(): TypeSpecifier
	{
		return $this->createTypeSpecifier(
			new \PhpParser\PrettyPrinter\Standard(),
			$this->createReflectionProvider(),
			$this->getMethodTypeSpecifyingExtensions(),
			$this->getStaticMethodTypeSpecifyingExtensions()
		);
	}

	private function getAnalyser(): Analyser
	{
		if ($this->analyser === null) {
			$registry = new Registry([
				$this->getRule(),
			]);

			$broker = $this->createBroker();
			$printer = new \PhpParser\PrettyPrinter\Standard();
			$typeSpecifier = $this->createTypeSpecifier(
				$printer,
				$broker,
				$this->getMethodTypeSpecifyingExtensions(),
				$this->getStaticMethodTypeSpecifyingExtensions()
			);
			$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
			$fileHelper = new FileHelper($currentWorkingDirectory);
			$currentWorkingDirectory = $fileHelper->normalizePath($currentWorkingDirectory, '/');
			$fileHelper = new FileHelper($currentWorkingDirectory);
			$relativePathHelper = new SimpleRelativePathHelper($currentWorkingDirectory);
			$anonymousClassNameHelper = new AnonymousClassNameHelper($fileHelper, $relativePathHelper);
			$fileTypeMapper = new FileTypeMapper(new DirectReflectionProviderProvider($broker), $this->getParser(), self::getContainer()->getByType(PhpDocStringResolver::class), self::getContainer()->getByType(PhpDocNodeResolver::class), $this->createMock(Cache::class), $anonymousClassNameHelper);
			$phpDocInheritanceResolver = new PhpDocInheritanceResolver($fileTypeMapper);
			$nodeScopeResolver = new NodeScopeResolver(
				$broker,
				self::getReflectors()[0],
				$this->getClassReflectionExtensionRegistryProvider(),
				$this->getParser(),
				$fileTypeMapper,
				self::getContainer()->getByType(PhpVersion::class),
				$phpDocInheritanceResolver,
				$fileHelper,
				$typeSpecifier,
				$this->shouldPolluteScopeWithLoopInitialAssignments(),
				$this->shouldPolluteCatchScopeWithTryAssignments(),
				$this->shouldPolluteScopeWithAlwaysIterableForeach(),
				[],
				[],
				false
			);
			$fileAnalyser = new FileAnalyser(
				$this->createScopeFactory($broker, $typeSpecifier),
				$nodeScopeResolver,
				$this->getParser(),
				new DependencyResolver($fileHelper, $broker, new ExportedNodeResolver($fileTypeMapper, $printer)),
				true
			);
			$this->analyser = new Analyser(
				$fileAnalyser,
				$registry,
				$nodeScopeResolver,
				50
			);
		}

		return $this->analyser;
	}

	/**
	 * @return \PHPStan\Type\MethodTypeSpecifyingExtension[]
	 */
	protected function getMethodTypeSpecifyingExtensions(): array
	{
		return [];
	}

	/**
	 * @return \PHPStan\Type\StaticMethodTypeSpecifyingExtension[]
	 */
	protected function getStaticMethodTypeSpecifyingExtensions(): array
	{
		return [];
	}

	/**
	 * @param string[] $files
	 * @param mixed[] $expectedErrors
	 */
	public function analyse(array $files, array $expectedErrors): void
	{
		$files = array_map([$this->getFileHelper(), 'normalizePath'], $files);
		$analyserResult = $this->getAnalyser()->analyse($files);
		if (count($analyserResult->getInternalErrors()) > 0) {
			$this->fail(implode("\n", $analyserResult->getInternalErrors()));
		}
		$actualErrors = $analyserResult->getUnorderedErrors();

		$strictlyTypedSprintf = static function (int $line, string $message, ?string $tip): string {
			$message = sprintf('%02d: %s', $line, $message);
			if ($tip !== null) {
				$message .= "\n    💡 " . $tip;
			}

			return $message;
		};

		$expectedErrors = array_map(
			static function (array $error) use ($strictlyTypedSprintf): string {
				if (!isset($error[0])) {
					throw new \InvalidArgumentException('Missing expected error message.');
				}
				if (!isset($error[1])) {
					throw new \InvalidArgumentException('Missing expected file line.');
				}
				return $strictlyTypedSprintf($error[1], $error[0], $error[2] ?? null);
			},
			$expectedErrors
		);

		$actualErrors = array_map(
			static function (Error $error) use ($strictlyTypedSprintf): string {
				$line = $error->getLine();
				if ($line === null) {
					return $strictlyTypedSprintf(-1, $error->getMessage(), $error->getTip());
				}
				return $strictlyTypedSprintf($line, $error->getMessage(), $error->getTip());
			},
			$actualErrors
		);

		$this->assertSame(implode("\n", $expectedErrors) . "\n", implode("\n", $actualErrors) . "\n");
	}

	protected function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return false;
	}

	protected function shouldPolluteCatchScopeWithTryAssignments(): bool
	{
		return false;
	}

	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return true;
	}

}
