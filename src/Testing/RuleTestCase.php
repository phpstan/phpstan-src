<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Rules\Registry;
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

	protected function getTypeSpecifier(): TypeSpecifier
	{
		return self::getContainer()->getService('typeSpecifier');
	}

	private function getAnalyser(): Analyser
	{
		if ($this->analyser === null) {
			$registry = new Registry([
				$this->getRule(),
			]);

			$reflectionProvider = $this->createReflectionProvider();
			$typeSpecifier = $this->getTypeSpecifier();
			$nodeScopeResolver = new NodeScopeResolver(
				$reflectionProvider,
				self::getReflector(),
				$this->getClassReflectionExtensionRegistryProvider(),
				$this->getParser(),
				self::getContainer()->getByType(FileTypeMapper::class),
				self::getContainer()->getByType(StubPhpDocProvider::class),
				self::getContainer()->getByType(PhpVersion::class),
				self::getContainer()->getByType(PhpDocInheritanceResolver::class),
				self::getContainer()->getByType(FileHelper::class),
				$typeSpecifier,
				self::getContainer()->getByType(DynamicThrowTypeExtensionProvider::class),
				$this->shouldPolluteScopeWithLoopInitialAssignments(),
				$this->shouldPolluteScopeWithAlwaysIterableForeach(),
				[],
				[],
				true,
				false
			);
			$fileAnalyser = new FileAnalyser(
				$this->createScopeFactory($reflectionProvider, $typeSpecifier),
				$nodeScopeResolver,
				$this->getParser(),
				self::getContainer()->getByType(DependencyResolver::class),
				true,
			);
			$this->analyser = new Analyser(
				$fileAnalyser,
				$registry,
				$nodeScopeResolver,
				50,
			);
		}

		return $this->analyser;
	}

	/**
	 * @param string[] $files
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function analyse(array $files, array $expectedErrors): void
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
