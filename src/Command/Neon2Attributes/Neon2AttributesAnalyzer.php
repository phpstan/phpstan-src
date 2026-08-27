<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use Nette\Neon\Neon;
use PHPStan\Collectors\RegistryFactory;
use PHPStan\DependencyInjection\AttributeServices\AutoloadRules;
use PHPStan\DependencyInjection\AttributeServices\ComposerProjectFactory;
use PHPStan\DependencyInjection\ValidateServiceTagsExtension;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\Rules\LazyRegistry;
use ReflectionClass;
use ReflectionParameter;
use function addslashes;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function dirname;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function is_int;
use function is_string;
use function preg_match;
use function rtrim;
use function sort;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtr;

/**
 * Decides which `services:` and `rules:` entries of one NEON file can be expressed
 * through PHPStan's DI attributes on the classes themselves. Everything it cannot
 * express deterministically stays in the file, with the reason reported.
 */
final class Neon2AttributesAnalyzer
{

	private const SUPPORTED_SERVICE_KEYS = ['class', 'factory', 'arguments', 'autowired', 'tags'];

	private ?AutoloadRules $rootAutoloadRules = null;

	public function __construct(private FileHelper $fileHelper, private string $projectRoot)
	{
	}

	/**
	 * @throws Neon2AttributesException
	 */
	public function analyze(string $neonFile): Neon2AttributesPlan
	{
		$decoded = Neon::decode(FileReader::read($neonFile));
		if (!is_array($decoded)) {
			throw new Neon2AttributesException(sprintf('File %s does not contain a NEON structure.', $neonFile));
		}

		$conversions = [];
		$skipped = [];

		$rules = $decoded['rules'] ?? [];
		if (is_array($rules)) {
			$entryIndex = 0;
			foreach ($rules as $rule) {
				$this->analyzeRuleEntry($rule, $entryIndex, $conversions, $skipped);
				$entryIndex++;
			}
		}

		$services = $decoded['services'] ?? [];
		if (is_array($services)) {
			$entryIndex = 0;
			foreach ($services as $key => $service) {
				$this->analyzeServiceEntry(is_string($key) ? $key : null, $service, $entryIndex, $conversions, $skipped);
				$entryIndex++;
			}
		}

		return new Neon2AttributesPlan(
			$conversions,
			$skipped,
			$this->collectDirectoriesToDeclare($neonFile, $conversions),
		);
	}

	/**
	 * @param mixed $rule
	 * @param list<ServiceConversion> $conversions
	 * @param list<SkippedEntry> $skipped
	 */
	private function analyzeRuleEntry($rule, int $entryIndex, array &$conversions, array &$skipped): void
	{
		if (!is_string($rule)) {
			$skipped[] = new SkippedEntry('rules', sprintf('entry #%d', $entryIndex), 'The entry is not a plain class name.');
			return;
		}

		$file = $this->getConvertibleClassFile($rule, 'rules', $skipped);
		if ($file === null) {
			return;
		}

		$conversions[] = new ServiceConversion(
			'rules',
			$entryIndex,
			$rule,
			$file,
			'#[RegisteredRule(level: 0)]',
			[],
			['PHPStan\DependencyInjection\RegisteredRule'],
		);
	}

	/**
	 * @param mixed $service
	 * @param list<ServiceConversion> $conversions
	 * @param list<SkippedEntry> $skipped
	 */
	private function analyzeServiceEntry(?string $name, $service, int $entryIndex, array &$conversions, array &$skipped): void
	{
		$description = $name ?? sprintf('entry #%d', $entryIndex);

		if (is_string($service)) {
			$service = ['class' => $service];
		}
		if (!is_array($service)) {
			$skipped[] = new SkippedEntry('services', $description, 'The service definition is not a plain class name or a map.');
			return;
		}

		if ($name === null && isset($service['class']) && is_string($service['class'])) {
			$description = $service['class'];
		}

		foreach (array_keys($service) as $key) {
			if (!is_string($key) || !in_array($key, self::SUPPORTED_SERVICE_KEYS, true)) {
				$skipped[] = new SkippedEntry('services', $description, sprintf('The service definition uses `%s` which cannot be expressed with an attribute.', is_string($key) ? $key : (string) $key));
				return;
			}
		}

		$class = $service['class'] ?? null;
		$factory = $service['factory'] ?? null;
		if ($class === null && is_string($factory) && !str_contains($factory, '::')) {
			$class = $factory;
			$factory = null;
		}
		if (!is_string($class)) {
			$skipped[] = new SkippedEntry('services', $description, 'The service class cannot be determined statically.');
			return;
		}
		$description = $name ?? $class;

		$factoryArgument = null;
		if ($factory !== null) {
			if (!is_string($factory) || preg_match('#^@[\w\\\\]+::\w+$#', $factory) !== 1) {
				$skipped[] = new SkippedEntry('services', $description, 'Only a `@service::method` factory can be expressed with an attribute.');
				return;
			}
			$factoryArgument = $factory;
		}

		$file = $this->getConvertibleClassFile($class, 'services', $skipped, $description);
		if ($file === null) {
			return;
		}

		$reflection = new ReflectionClass($class); /** @phpstan-ignore argument.type */

		$parameterAttributes = $this->buildParameterAttributes($service['arguments'] ?? [], $reflection, $description, $skipped);
		if ($parameterAttributes === null) {
			return;
		}

		$tags = $service['tags'] ?? [];
		if (!is_array($tags)) {
			$skipped[] = new SkippedEntry('services', $description, 'The tags cannot be determined statically.');
			return;
		}
		foreach ($tags as $tag) {
			if (is_string($tag)) {
				continue;
			}

			$skipped[] = new SkippedEntry('services', $description, 'Tags with attributes cannot be expressed with an attribute.');
			return;
		}

		$autowired = $service['autowired'] ?? true;

		if ($tags === [LazyRegistry::RULE_TAG] || $tags === [RegistryFactory::COLLECTOR_TAG]) {
			if ($name !== null) {
				$skipped[] = new SkippedEntry('services', $description, 'A named rule or collector service cannot be expressed with an attribute.');
				return;
			}
			if ($factoryArgument !== null) {
				$skipped[] = new SkippedEntry('services', $description, 'A rule or collector with a factory cannot be expressed with an attribute.');
				return;
			}

			$attribute = $tags === [LazyRegistry::RULE_TAG]
				? 'RegisteredRule'
				: 'RegisteredCollector';
			$conversions[] = new ServiceConversion(
				'services',
				$entryIndex,
				$class,
				$file,
				sprintf('#[%s(level: 0)]', $attribute),
				$parameterAttributes,
				$this->collectUseImports($attribute, $parameterAttributes),
			);
			return;
		}

		$derivableTags = [];
		foreach (ValidateServiceTagsExtension::getInterfaceTagMapping() as $interface => $tag) {
			if (!$reflection->implementsInterface($interface)) {
				continue;
			}

			$derivableTags[] = $tag;
		}

		foreach ($tags as $tag) {
			if (in_array($tag, $derivableTags, true)) {
				continue;
			}

			$skipped[] = new SkippedEntry('services', $description, sprintf('The tag %s cannot be derived from the implemented interfaces.', $tag));
			return;
		}

		$sortedTags = array_values($tags);
		sort($sortedTags);
		$sortedDerivable = $derivableTags;
		sort($sortedDerivable);

		if ($sortedTags === $sortedDerivable) {
			$autoTag = true;
		} elseif (count($tags) === 0) {
			$autoTag = false;
		} else {
			$skipped[] = new SkippedEntry('services', $description, 'The class implements more tagged extension interfaces than the entry declares as tags.');
			return;
		}

		if ($autowired === false) {
			if ($name === null) {
				$skipped[] = new SkippedEntry('services', $description, 'A non-autowired service without a name cannot be expressed with an attribute.');
				return;
			}
			if (count($derivableTags) > 0 || count($tags) > 0) {
				$skipped[] = new SkippedEntry('services', $description, 'A non-autowired service is never auto-tagged, so its tags cannot be expressed with an attribute.');
				return;
			}

			$arguments = [sprintf("name: '%s'", addslashes($name))];
			if ($factoryArgument !== null) {
				$arguments[] = sprintf("factory: '%s'", addslashes($factoryArgument));
			}
			$conversions[] = new ServiceConversion(
				'services',
				$entryIndex,
				$class,
				$file,
				sprintf('#[NonAutowiredService(%s)]', implode(', ', $arguments)),
				$parameterAttributes,
				$this->collectUseImports('NonAutowiredService', $parameterAttributes),
			);
			return;
		}

		$arguments = [];
		if ($name !== null) {
			$arguments[] = sprintf("name: '%s'", addslashes($name));
		}
		if ($factoryArgument !== null) {
			$arguments[] = sprintf("factory: '%s'", addslashes($factoryArgument));
		}
		if ($autowired !== true) {
			$asValue = $this->renderAutowiredAs($autowired);
			if ($asValue === null) {
				$skipped[] = new SkippedEntry('services', $description, 'The autowired value cannot be expressed with an attribute.');
				return;
			}
			$arguments[] = sprintf('as: %s', $asValue);
		}
		if (!$autoTag) {
			$arguments[] = 'autoTag: false';
		}

		$conversions[] = new ServiceConversion(
			'services',
			$entryIndex,
			$class,
			$file,
			count($arguments) === 0
				? '#[AutowiredService]'
				: sprintf('#[AutowiredService(%s)]', implode(', ', $arguments)),
			$parameterAttributes,
			$this->collectUseImports('AutowiredService', $parameterAttributes),
		);
	}

	/**
	 * Attribute code per constructor parameter for the entry's `arguments`,
	 * null (with a skip recorded) when they cannot be expressed.
	 *
	 * @param mixed $arguments
	 * @param ReflectionClass<object> $reflection
	 * @param list<SkippedEntry> $skipped
	 * @return array<string, string>|null
	 */
	private function buildParameterAttributes($arguments, ReflectionClass $reflection, string $description, array &$skipped): ?array
	{
		if (!is_array($arguments)) {
			$skipped[] = new SkippedEntry('services', $description, 'The arguments cannot be determined statically.');
			return null;
		}
		if (count($arguments) === 0) {
			return [];
		}

		$constructor = $reflection->getConstructor();
		$parameters = $constructor === null ? [] : $constructor->getParameters();
		$parameterNames = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $parameters);

		$parameterAttributes = [];
		foreach ($arguments as $argumentKey => $argumentValue) {
			if (!is_string($argumentValue) || (preg_match('#^@[\w\\\\]+$#D', $argumentValue) !== 1 && preg_match('#^%[\w.-]+%$#D', $argumentValue) !== 1)) {
				$skipped[] = new SkippedEntry('services', $description, sprintf('The argument %s is not a %%parameter%% or @service reference.', is_int($argumentKey) ? sprintf('#%d', $argumentKey) : $argumentKey));
				return null;
			}

			if (is_int($argumentKey)) {
				if (!array_key_exists($argumentKey, $parameterNames)) {
					$skipped[] = new SkippedEntry('services', $description, sprintf('The constructor has no parameter #%d.', $argumentKey));
					return null;
				}
				$parameterName = $parameterNames[$argumentKey];
			} else {
				if (!in_array($argumentKey, $parameterNames, true)) {
					$skipped[] = new SkippedEntry('services', $description, sprintf('The constructor has no parameter $%s.', $argumentKey));
					return null;
				}
				$parameterName = $argumentKey;
			}

			if ($argumentValue === '%' . $parameterName . '%') {
				$parameterAttributes[$parameterName] = '#[AutowiredParameter]';
			} else {
				$parameterAttributes[$parameterName] = sprintf("#[AutowiredParameter(ref: '%s')]", addslashes($argumentValue));
			}
		}

		return $parameterAttributes;
	}

	/**
	 * @param mixed $autowired
	 */
	private function renderAutowiredAs($autowired): ?string
	{
		if (is_string($autowired)) {
			return '\\' . $autowired . '::class';
		}

		if (!is_array($autowired)) {
			return null;
		}

		$rendered = [];
		foreach ($autowired as $autowiredClass) {
			if (!is_string($autowiredClass)) {
				return null;
			}

			$rendered[] = '\\' . $autowiredClass . '::class';
		}

		return '[' . implode(', ', $rendered) . ']';
	}

	/**
	 * File of the class when the class can carry attributes editable by this command,
	 * null (with a skip recorded) otherwise.
	 *
	 * @param 'services'|'rules' $section
	 * @param list<SkippedEntry> $skipped
	 */
	private function getConvertibleClassFile(string $class, string $section, array &$skipped, ?string $description = null): ?string
	{
		$description ??= $class;

		if (!class_exists($class) && !interface_exists($class)) {
			$skipped[] = new SkippedEntry($section, $description, 'The class cannot be autoloaded.');
			return null;
		}

		$reflection = new ReflectionClass($class);
		$file = $reflection->getFileName();
		if ($file === false) {
			$skipped[] = new SkippedEntry($section, $description, 'The class has no source file.');
			return null;
		}

		$file = $this->fileHelper->normalizePath($file, '/');
		$normalizedRoot = rtrim($this->fileHelper->normalizePath($this->projectRoot, '/'), '/');
		if (!str_starts_with($file, $normalizedRoot . '/') || str_contains($file, '/vendor/')) {
			$skipped[] = new SkippedEntry($section, $description, 'The class is not part of this project.');
			return null;
		}

		foreach ($reflection->getAttributes() as $attribute) {
			if (!str_starts_with($attribute->getName(), 'PHPStan\\DependencyInjection\\')) {
				continue;
			}

			$skipped[] = new SkippedEntry($section, $description, sprintf('The class already carries the %s attribute.', $attribute->getName()));
			return null;
		}

		if ($this->findCoveringAutoloadDirectory($file) === null) {
			$skipped[] = new SkippedEntry($section, $description, 'The class file is not covered by a psr-4 or classmap autoload rule of composer.json.');
			return null;
		}

		return $file;
	}

	/**
	 * @param array<string, string> $parameterAttributes
	 * @return list<string>
	 */
	private function collectUseImports(string $attributeShortName, array $parameterAttributes): array
	{
		$imports = ['PHPStan\DependencyInjection\\' . $attributeShortName];
		if (count($parameterAttributes) > 0) {
			$imports[] = 'PHPStan\DependencyInjection\AutowiredParameter';
		}

		return $imports;
	}

	/**
	 * @param list<ServiceConversion> $conversions
	 * @return list<string>
	 */
	private function collectDirectoriesToDeclare(string $neonFile, array $conversions): array
	{
		$relativePathHelper = new ParentDirectoryRelativePathHelper(dirname($this->fileHelper->normalizePath($neonFile, '/')));

		$directories = [];
		foreach ($conversions as $conversion) {
			$directory = $this->findCoveringAutoloadDirectory($conversion->phpFile);
			if ($directory === null) {
				continue;
			}

			$directories[$directory] = true;
		}

		$relative = array_map(
			static fn (string $directory): string => strtr($relativePathHelper->getRelativePath($directory), '\\', '/'),
			array_keys($directories),
		);
		sort($relative);

		return $relative;
	}

	private function findCoveringAutoloadDirectory(string $file): ?string
	{
		$rules = $this->getRootAutoloadRules();
		if ($rules === null) {
			return null;
		}

		foreach ($rules->psr4 as $baseDirectories) {
			foreach ($baseDirectories as $baseDirectory) {
				if (str_starts_with($file, $baseDirectory . '/')) {
					return $baseDirectory;
				}
			}
		}

		foreach ($rules->classmapPaths as $classmapPath) {
			if ($file === $classmapPath || str_starts_with($file, $classmapPath . '/')) {
				return $classmapPath;
			}
		}

		return null;
	}

	private function getRootAutoloadRules(): ?AutoloadRules
	{
		if ($this->rootAutoloadRules !== null) {
			return $this->rootAutoloadRules;
		}

		$project = (new ComposerProjectFactory($this->fileHelper))->create($this->projectRoot);
		if ($project === null) {
			return null;
		}

		return $this->rootAutoloadRules = $project->rootAutoload->union($project->rootAutoloadDev);
	}

}
