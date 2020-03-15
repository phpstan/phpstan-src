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
use PHPStan\File\FileHelper;
use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\Rules\Registry;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

/**
 * @template TRule of \PHPStan\Rules\Rule
 */
abstract class RuleTestCase extends \PHPStan\Testing\TestCase
{

	/** @var \PHPStan\Analyser\Analyser|null */
	private $analyser;

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
			$fileHelper = $this->getFileHelper();
			$typeSpecifier = $this->createTypeSpecifier(
				$printer,
				$broker,
				$this->getMethodTypeSpecifyingExtensions(),
				$this->getStaticMethodTypeSpecifyingExtensions()
			);
			$currentWorkingDirectory = $this->getCurrentWorkingDirectory();
			$nodeScopeResolver = new NodeScopeResolver(
				$broker,
				$this->getParser(),
				new FileTypeMapper($this->getParser(), self::getContainer()->getByType(PhpDocStringResolver::class), self::getContainer()->getByType(PhpDocNodeResolver::class), $this->createMock(Cache::class), new AnonymousClassNameHelper(new FileHelper($currentWorkingDirectory), new FuzzyRelativePathHelper($currentWorkingDirectory, [], DIRECTORY_SEPARATOR))),
				$fileHelper,
				$typeSpecifier,
				$this->shouldPolluteScopeWithLoopInitialAssignments(),
				$this->shouldPolluteCatchScopeWithTryAssignments(),
				$this->shouldPolluteScopeWithAlwaysIterableForeach(),
				[],
				[]
			);
			$fileAnalyser = new FileAnalyser(
				$this->createScopeFactory($broker, $typeSpecifier),
				$nodeScopeResolver,
				$this->getParser(),
				new DependencyResolver($broker),
				$fileHelper
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
		$actualErrors = $this->getAnalyser()->analyse($files)->getErrors();

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
					throw new \PHPStan\ShouldNotHappenException(sprintf('Error (%s) line should not be null.', $error->getMessage()));
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
