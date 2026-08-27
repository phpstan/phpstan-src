<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\DI\Container as NetteDIContainer;
use Nette\Neon\Neon;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;
use PHPStan\Command\Neon2Attributes\Neon2AttributesAnalyzer;
use PHPStan\Command\Neon2Attributes\Neon2AttributesException;
use PHPStan\Command\Neon2Attributes\Neon2AttributesPlan;
use PHPStan\Command\Neon2Attributes\NeonEditor;
use PHPStan\Command\Neon2Attributes\PhpAttributeInserter;
use PHPStan\Command\Neon2Attributes\SkippedEntry;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\ShouldNotHappenException;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_keys;
use function count;
use function fclose;
use function getcwd;
use function is_array;
use function is_file;
use function is_resource;
use function is_string;
use function ksort;
use function md5;
use function mkdir;
use function preg_match;
use function proc_close;
use function proc_open;
use function sort;
use function sprintf;
use function stream_get_contents;
use function sys_get_temp_dir;
use function uniqid;
use const PHP_BINARY;
use const PHP_VERSION_ID;

/**
 * Deterministically converts `services:` and `rules:` entries of a NEON file into
 * PHPStan's DI attributes on the classes, declares the edited directories in
 * `attributeServicesDirectories`, and verifies the converted configuration compiles
 * into the same tagged services as the original before keeping the changes.
 */
final class Neon2AttributesCommand extends Command
{

	private const NAME = 'neon2attributes';

	private const FINGERPRINT_DELIMITER = '-----NEON2ATTRIBUTES-FINGERPRINT-----';

	#[Override]
	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Converts services and rules registered in a NEON file into PHPStan DI attributes on their classes')
			->setDefinition([
				new InputArgument('neon-file', InputArgument::REQUIRED, 'Path to the NEON file to convert'),
				new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Only print what would be converted'),
				new InputOption('print-service-fingerprint', null, InputOption::VALUE_NONE, '(internal) Print the tagged-services fingerprint of the configuration'),
			]);
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if (PHP_VERSION_ID < 80000) {
			$output->writeln('<error>The neon2attributes command requires PHP 8.0 or later.</error>');
			return 1;
		}

		$currentWorkingDirectory = getcwd();
		if ($currentWorkingDirectory === false) {
			throw new ShouldNotHappenException();
		}
		$fileHelper = new FileHelper($currentWorkingDirectory);

		$neonFileArgument = $input->getArgument('neon-file');
		if (!is_string($neonFileArgument)) {
			throw new ShouldNotHappenException();
		}
		$neonFile = $fileHelper->normalizePath($fileHelper->absolutizePath($neonFileArgument), '/');
		if (!is_file($neonFile)) {
			$output->writeln(sprintf('<error>File %s does not exist.</error>', $neonFile));
			return 1;
		}

		if ((bool) $input->getOption('print-service-fingerprint')) {
			return $this->printFingerprint($neonFile, $currentWorkingDirectory, $output);
		}

		$analyzer = new Neon2AttributesAnalyzer($fileHelper, $currentWorkingDirectory);
		try {
			$plan = $analyzer->analyze($neonFile);
		} catch (Neon2AttributesException $e) {
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		}

		[$plan, $newContents] = $this->buildPhpFileEdits($plan);

		foreach ($plan->conversions as $conversion) {
			$output->writeln(sprintf('Converting %s → %s', $conversion->className, $conversion->attributeCode));
		}
		foreach ($plan->skipped as $skipped) {
			$output->writeln(sprintf('Keeping %s in `%s`: %s', $skipped->description, $skipped->section, $skipped->reason));
		}

		if (count($plan->conversions) === 0) {
			$output->writeln('Nothing to convert.');
			return 0;
		}

		if ((bool) $input->getOption('dry-run')) {
			$output->writeln(sprintf('Would convert %d entries (dry run, nothing written).', count($plan->conversions)));
			return 0;
		}

		$originalFingerprint = $this->computeFingerprintInSubprocess($neonFile, $output);
		if ($originalFingerprint === null) {
			$output->writeln('<comment>The original configuration does not compile on its own - the conversion cannot be verified automatically.</comment>');
		}

		try {
			$newContents[$neonFile] = $this->computeNeonContent($neonFile, $plan);
		} catch (Neon2AttributesException $e) {
			$output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		}

		$backups = [];
		foreach ($newContents as $file => $newContent) {
			$backups[$file] = FileReader::read($file);
		}
		foreach ($newContents as $file => $newContent) {
			FileWriter::write($file, $newContent);
		}

		if ($originalFingerprint !== null) {
			$convertedFingerprint = $this->computeFingerprintInSubprocess($neonFile, $output);
			if ($convertedFingerprint === null || $convertedFingerprint !== $originalFingerprint) {
				foreach ($backups as $file => $originalContent) {
					FileWriter::write($file, $originalContent);
				}

				if ($convertedFingerprint === null) {
					$output->writeln('<error>The converted configuration does not compile - all changes were rolled back.</error>');
				} else {
					$output->writeln('<error>The converted configuration compiles into different tagged services than the original - all changes were rolled back.</error>');
					$this->printFingerprintDiff($originalFingerprint, $convertedFingerprint, $output);
				}

				return 1;
			}

			$output->writeln('Verified: the converted configuration compiles into the same tagged services as the original.');
		}

		$output->writeln(sprintf('Converted %d entries; %d entries stay in the NEON file.', count($plan->conversions), count($plan->skipped)));

		return 0;
	}

	/**
	 * Applies the attribute insertions file by file. A file the inserter cannot edit
	 * deterministically (an unusual layout) demotes its conversions back to kept entries
	 * instead of aborting the whole run.
	 *
	 * @return array{Neon2AttributesPlan, array<string, string>} adjusted plan, php file => new content
	 */
	private function buildPhpFileEdits(Neon2AttributesPlan $plan): array
	{
		$conversionsByFile = [];
		foreach ($plan->conversions as $conversion) {
			$conversionsByFile[$conversion->phpFile][] = $conversion;
		}

		$inserter = new PhpAttributeInserter();
		$survivingConversions = [];
		$skipped = $plan->skipped;
		$newContents = [];
		foreach ($conversionsByFile as $file => $conversions) {
			try {
				$newContents[$file] = $inserter->insert(FileReader::read($file), $conversions);
			} catch (Neon2AttributesException $e) {
				foreach ($conversions as $conversion) {
					$skipped[] = new SkippedEntry($conversion->section, $conversion->className, $e->getMessage());
				}
				continue;
			}

			foreach ($conversions as $conversion) {
				$survivingConversions[] = $conversion;
			}
		}

		return [new Neon2AttributesPlan($survivingConversions, $skipped, $plan->directoriesToDeclare), $newContents];
	}

	/**
	 * @throws Neon2AttributesException
	 */
	private function computeNeonContent(string $neonFile, Neon2AttributesPlan $plan): string
	{
		$sectionIndexes = ['rules' => [], 'services' => []];
		$sectionCounts = ['rules' => 0, 'services' => 0];
		foreach ($plan->conversions as $conversion) {
			$sectionIndexes[$conversion->section][] = $conversion->entryIndex;
		}
		$decoded = Neon::decode(FileReader::read($neonFile));
		if (is_array($decoded)) {
			foreach (['rules', 'services'] as $section) {
				$sectionValue = $decoded[$section] ?? [];
				$sectionCounts[$section] = is_array($sectionValue) ? count($sectionValue) : 0;
			}
		}

		$editor = new NeonEditor();
		$neonContent = FileReader::read($neonFile);
		foreach (['rules', 'services'] as $section) {
			$neonContent = $editor->removeEntries($neonContent, $section, $sectionIndexes[$section], $sectionCounts[$section]);
		}

		return $editor->addDirectoriesSection($neonContent, $plan->directoriesToDeclare);
	}

	private function printFingerprint(string $neonFile, string $currentWorkingDirectory, OutputInterface $output): int
	{
		$tmpDir = sys_get_temp_dir() . '/phpstan-neon2attributes-' . md5(uniqid(more_entropy: true));
		@mkdir($tmpDir, 0777, true);

		// with a rule level in play (level 0 keeps the diff minimal), so that classes converted
		// to #[RegisteredRule]/#[RegisteredCollector] register just like their rules:/tagged
		// originals; without it autowiredAttributeServices.level stays null and none would
		$container = (new ContainerFactory($currentWorkingDirectory))->create($tmpDir, [__DIR__ . '/../../conf/config.level0.neon', $neonFile], [], [$currentWorkingDirectory]);
		$fingerprint = $this->buildFingerprint($container);

		$output->writeln(self::FINGERPRINT_DELIMITER);
		$output->writeln(Json::encode($fingerprint));
		$output->writeln(self::FINGERPRINT_DELIMITER);

		return 0;
	}

	/**
	 * Tag => sorted service class names of the compiled container. What Ondrej and Caleb
	 * compared by hand in the issue - same classes, same tags, same multiplicities.
	 *
	 * @return array<string, list<string>>
	 */
	private function buildFingerprint(Container $container): array
	{
		$netteContainer = $container->getByType(NetteDIContainer::class);

		$tagsProperty = new ReflectionProperty(NetteDIContainer::class, 'tags');
		/** @var array<string, array<int|string, mixed>> $tags */
		$tags = $tagsProperty->getValue($netteContainer);

		$fingerprint = [];
		foreach ($tags as $tag => $services) {
			$types = [];
			foreach (array_keys($services) as $serviceName) {
				$types[] = $netteContainer->getServiceType((string) $serviceName);
			}
			sort($types);
			$fingerprint[$tag] = $types;
		}

		ksort($fingerprint);

		return $fingerprint;
	}

	/**
	 * @return array<string, list<string>>|null
	 */
	private function computeFingerprintInSubprocess(string $neonFile, OutputInterface $output): ?array
	{
		$phpstanBinary = $_SERVER['argv'][0] ?? null;
		if (!is_string($phpstanBinary)) {
			return null;
		}

		$descriptorSpec = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open(
			[PHP_BINARY, $phpstanBinary, self::NAME, '--print-service-fingerprint', $neonFile],
			$descriptorSpec,
			$pipes,
		);
		if (!is_resource($process)) {
			return null;
		}

		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		if ($exitCode !== 0 || $stdout === false) {
			if ($output->isVerbose() && $stderr !== false && $stderr !== '') {
				$output->writeln($stderr);
			}

			return null;
		}

		$matches = [];
		if (preg_match('/' . self::FINGERPRINT_DELIMITER . '\n(.*)\n' . self::FINGERPRINT_DELIMITER . '/s', $stdout, $matches) !== 1) {
			return null;
		}

		try {
			$decoded = Json::decode($matches[1], Json::FORCE_ARRAY);
		} catch (JsonException) {
			return null;
		}

		return is_array($decoded) ? $decoded : null;
	}

	/**
	 * @param array<string, list<string>> $original
	 * @param array<string, list<string>> $converted
	 */
	private function printFingerprintDiff(array $original, array $converted, OutputInterface $output): void
	{
		foreach ($original as $tag => $types) {
			$convertedTypes = $converted[$tag] ?? [];
			if ($types === $convertedTypes) {
				continue;
			}

			$output->writeln(sprintf('Tag %s: %d services originally, %d after conversion.', $tag, count($types), count($convertedTypes)));
		}
		foreach ($converted as $tag => $types) {
			if (isset($original[$tag])) {
				continue;
			}

			$output->writeln(sprintf('Tag %s: 0 services originally, %d after conversion.', $tag, count($types)));
		}
	}

}
