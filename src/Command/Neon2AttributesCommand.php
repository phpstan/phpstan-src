<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Neon\Exception as NeonException;
use Nette\Neon\Neon;
use Override;
use PHPStan\Command\Neon2Attributes\ComposerConfigIncludes;
use PHPStan\Command\Neon2Attributes\ConvertedService;
use PHPStan\Command\Neon2Attributes\KeptService;
use PHPStan\Command\Neon2Attributes\Neon2AttributesMigrator;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\DependencyInjection\AttributeServices\AttributeTargetsProvider;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\ExtensionInterfacesExtension;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\DependencyInjection\ValidateServiceTagsExtension;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\File\RelativePathHelper;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function getcwd;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const PHP_VERSION;
use const PHP_VERSION_ID;

final class Neon2AttributesCommand extends Command
{

	private const NAME = 'migrate:neon2attributes';

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
	)
	{
		parent::__construct();
	}

	#[Override]
	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Replaces services in the project\'s NEON config files with attributes on the service classes.')
			->setDefinition([
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('debug', mode: InputOption::VALUE_NONE, description: 'Show debug information - do not catch internal errors'),
				new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED, description: 'Memory limit for the migration'),
				new InputOption('xdebug', mode: InputOption::VALUE_NONE, description: 'Allow running with Xdebug for debugging purposes (pcov and uopz are still turned off)'),
			]);
	}

	#[Override]
	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		if (!(bool) $input->getOption('debug')) {
			return;
		}

		$application = $this->getApplication();
		if ($application === null) {
			throw new ShouldNotHappenException();
		}
		$application->setCatchExceptions(false);
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$memoryLimit = $input->getOption('memory-limit');
		$debugEnabled = (bool) $input->getOption('debug');
		$allowXdebug = $input->getOption('xdebug');

		if (
			(!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_bool($allowXdebug))
		) {
			throw new ShouldNotHappenException();
		}

		if (PHP_VERSION_ID < 80000) {
			$output->writeln(sprintf('<error>The migration reads and writes PHP attributes, run it on PHP 8.0 or later. You are running PHP %s.</error>', PHP_VERSION));
			return 1;
		}

		// an extension's own config files are included by phpstan/extension-installer in the projects
		// that install it, not by its phpstan.neon, they are migrated too
		$currentWorkingDirectory = getcwd();
		if ($currentWorkingDirectory === false) {
			throw new ShouldNotHappenException();
		}
		$fileHelper = new FileHelper($currentWorkingDirectory);
		$projectConfigFile = $configuration !== null
			? $fileHelper->normalizePath($fileHelper->absolutizePath($configuration))
			: CommandHelper::findDiscoverableConfigFile($currentWorkingDirectory);
		$composerIncludes = ComposerConfigIncludes::findMissing($currentWorkingDirectory, $projectConfigFile);
		$generatedConfigFile = null;
		if (count($composerIncludes) > 0) {
			$generatedConfigFile = sprintf('%s/phpstan-neon2attributes-%s.neon', sys_get_temp_dir(), uniqid());
			FileWriter::write($generatedConfigFile, Neon::encode([
				'includes' => $projectConfigFile !== null ? [$projectConfigFile, ...$composerIncludes] : $composerIncludes,
			], true));
			$output->writeln(sprintf(
				'Note: Using %sextra.phpstan.includes from composer.json: %s.',
				$projectConfigFile !== null ? sprintf('configuration file %s and ', $projectConfigFile) : '',
				implode(', ', $composerIncludes),
			));
			$configuration = $generatedConfigFile;
		}

		try {
			return $this->migrate($input, $output, $memoryLimit, $autoloadFile, $configuration, $allowXdebug, $debugEnabled, $generatedConfigFile);
		} finally {
			if ($generatedConfigFile !== null) {
				@unlink($generatedConfigFile);
			}
		}
	}

	private function migrate(
		InputInterface $input,
		OutputInterface $output,
		?string $memoryLimit,
		?string $autoloadFile,
		?string $configuration,
		bool $allowXdebug,
		bool $debugEnabled,
		?string $generatedConfigFile,
	): int
	{
		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				[],
				$memoryLimit,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				null,
				'0',
				$allowXdebug,
				$debugEnabled,
				null,
				null,
				false,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$container = $inceptionResult->getContainer();
		$style = $inceptionResult->getStdOutput()->getStyle();
		$errorStyle = $inceptionResult->getErrorOutput()->getStyle();

		$phpVersion = $container->getByType(ComposerPhpVersionFactory::class)->getMinVersion() ?? $container->getByType(PhpVersion::class);
		if ($phpVersion->getVersionId() < 80000) {
			$errorStyle->error(sprintf(
				"The project supports PHP %s. PHPStan reads the attributes with native reflection, which needs PHP 8.0.\nRaise the PHP requirement in composer.json to 8.0 or later first.",
				$phpVersion->getVersionString(),
			));
			return 1;
		}

		$directories = $container->getParameter('attributeServicesDirectories');
		if (!is_array($directories) || count($directories) === 0) {
			$errorStyle->error(implode("\n", [
				'The attributeServicesDirectories section is missing in the configuration.',
				'List the directories with the service classes in it first, for example:',
				'',
				'attributeServicesDirectories:',
				'    - src',
			]));
			return 1;
		}

		$packageDependencyResolver = $container->getByType(PackageDependencyResolver::class);
		/** @var list<string> $allConfigFiles */
		$allConfigFiles = $container->getParameter('allConfigFiles');
		$configFiles = array_values(array_filter(
			$allConfigFiles,
			static fn (string $file): bool => str_ends_with($file, '.neon')
				&& $file !== $generatedConfigFile
				&& !str_starts_with($file, 'phar://')
				&& !$packageDependencyResolver->isFileOfInstalledPackage($file),
		));

		/** @var list<string> $directories */
		$result = $container->getByType(Neon2AttributesMigrator::class)->migrate(
			$configFiles,
			$directories,
			$phpVersion->getVersionId(),
			self::findOtherServiceNames(array_values(array_diff($allConfigFiles, $configFiles))),
			ValidateServiceTagsExtension::createInterfaceTagMapping(self::findExtensionInterfaces($allConfigFiles)),
		);

		/** @var RelativePathHelper $relativePathHelper */
		$relativePathHelper = $container->getService('simpleRelativePathHelper');

		foreach ($result->getChangedFiles() as $file => $contents) {
			FileWriter::write($file, $contents);
		}

		$converted = $result->getConvertedServices();
		$kept = $result->getKeptServices();
		$keptByCategory = [
			KeptService::CATEGORY_OUTSIDE => [],
			KeptService::CATEGORY_DUPLICATE => [],
			KeptService::CATEGORY_KEPT => [],
		];
		foreach ($kept as $service) {
			$keptByCategory[$service->category][] = $service;
		}

		$convertedByFile = [];
		foreach ($converted as $service) {
			$convertedByFile[$service->configFile] = ($convertedByFile[$service->configFile] ?? 0) + 1;
		}
		$style->section('Config files');
		$style->listing(array_map(static function (string $file) use ($relativePathHelper, $convertedByFile): string {
			$count = $convertedByFile[$file] ?? 0;

			return sprintf('%s: %s', $relativePathHelper->getRelativePath($file), $count === 0 ? 'no change' : sprintf('%d %s converted', $count, $count === 1 ? 'service' : 'services'));
		}, $configFiles));

		if (count($converted) > 0 && $inceptionResult->getStdOutput()->isVerbose()) {
			$style->section(sprintf('Converted services (%d)', count($converted)));
			$style->listing(array_map(
				static fn (ConvertedService $service): string => sprintf('%s: <info>%s</info>', $service->className, implode(', ', array_map(static fn (string $attribute): string => '#[' . $attribute . ']', $service->attributes))),
				$converted,
			));
		}

		$sections = [
			KeptService::CATEGORY_OUTSIDE => 'Services outside of attributeServicesDirectories (%d)',
			KeptService::CATEGORY_DUPLICATE => 'Classes registered as multiple services (%d)',
			KeptService::CATEGORY_KEPT => 'Services the attributes cannot express (%d)',
		];
		foreach ($sections as $category => $title) {
			$services = $keptByCategory[$category];
			if (count($services) === 0) {
				continue;
			}

			$style->section(sprintf($title, count($services)));
			$style->listing(array_map(static function (KeptService $service) use ($relativePathHelper): string {
				$location = sprintf('<fg=gray>%s:%d</>', $relativePathHelper->getRelativePath($service->configFile), $service->line);
				if ($service->reason === null) {
					return sprintf('%s %s', $service->name, $location);
				}

				return sprintf('%s: <comment>%s</comment> %s', $service->name, $service->reason, $location);
			}, $services));
		}

		$usedAttributes = [];
		foreach ($converted as $service) {
			foreach ($service->attributes as $attribute) {
				$usedAttributes[$attribute] = true;
			}
		}
		if (count($converted) === 0) {
			$style->warning('No services were converted.');
			return 0;
		}

		$keptEntries = 0;
		foreach ($kept as $service) {
			$keptEntries += $service->entries;
		}
		$style->success(sprintf(
			"Converted %d %s, %d %s in the config files.%s\nAttributes used: %s%s",
			count($converted),
			count($converted) === 1 ? 'service' : 'services',
			$keptEntries,
			$keptEntries === 1 ? 'entry stays' : 'entries stay',
			$inceptionResult->getStdOutput()->isVerbose() ? '' : ' Run with -v to list the converted services.',
			implode(', ', array_keys($usedAttributes)),
			$phpVersion->getVersionId() < 80100 && isset($usedAttributes['Conditional'])
				? sprintf("\n#[Conditional] is written in the PHP 8.0 compatible form because the project supports PHP %s.", $phpVersion->getVersionString())
				: '',
		));

		return 0;
	}

	/**
	 * The merged extensionInterfaces sections of the config files.
	 *
	 * @param list<string> $configFiles
	 * @return array<mixed>
	 */
	private static function findExtensionInterfaces(array $configFiles): array
	{
		$extensionInterfaces = [];
		foreach ($configFiles as $file) {
			if (!str_ends_with($file, '.neon')) {
				continue;
			}
			try {
				$config = Neon::decode(FileReader::read($file));
			} catch (CouldNotReadFileException | NeonException) {
				continue;
			}
			if (!is_array($config) || !is_array($config[ExtensionInterfacesExtension::NAME] ?? null)) {
				continue;
			}
			$extensionInterfaces += $config[ExtensionInterfacesExtension::NAME];
		}

		return $extensionInterfaces;
	}

	/**
	 * Services defined by PHPStan's own attributes and by the config files that are not migrated.
	 *
	 * @param list<string> $configFiles
	 * @return list<string>
	 */
	private static function findOtherServiceNames(array $configFiles): array
	{
		$names = [];
		$targets = AttributeTargetsProvider::create();
		foreach ($targets->findTargetClasses(AutowiredService::class) as $target) {
			$names[] = $target->attribute->name;
		}
		foreach ($targets->findTargetClasses(NonAutowiredService::class) as $target) {
			$names[] = $target->attribute->name;
		}
		foreach ($targets->findTargetClasses(Conditional::class) as $target) {
			$attribute = $target->attribute->attribute;
			if (!$attribute instanceof AutowiredService) {
				continue;
			}
			$names[] = $attribute->name;
		}

		foreach ($configFiles as $file) {
			if (!str_ends_with($file, '.neon')) {
				continue;
			}
			try {
				$config = Neon::decode(FileReader::read($file));
			} catch (CouldNotReadFileException | NeonException) {
				continue;
			}
			if (!is_array($config) || !is_array($config['services'] ?? null)) {
				continue;
			}
			foreach (array_keys($config['services']) as $name) {
				$names[] = $name;
			}
		}

		return array_values(array_filter($names, static fn ($name): bool => is_string($name)));
	}

}
