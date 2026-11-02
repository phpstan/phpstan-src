<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use Nette\Neon\Entity;
use Nette\Neon\Exception as NeonException;
use Nette\Neon\Lexer as NeonLexer;
use Nette\Neon\Node\ArrayItemNode as NeonArrayItemNode;
use Nette\Neon\Node\ArrayNode as NeonArrayNode;
use Nette\Neon\Parser as NeonParser;
use Nette\Neon\TokenStream as NeonTokenStream;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ValidateServiceTagsExtension;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use PHPStan\Neon\Printer as NeonPrinter;
use ReflectionClass;
use ReflectionNamedType;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function implode;
use function in_array;
use function interface_exists;
use function is_a;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;
use function preg_match;
use function realpath;
use function rtrim;
use function sort;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strcasecmp;
use function strrpos;
use function strtolower;
use function strtr;
use function substr;

/**
 * Replaces the `rules`, `services` and `conditionalTags` entries of NEON config files
 * with PHPStan's DI attributes on the registered classes.
 *
 * An entry is converted only when the attributes register the same service: the same class,
 * factory, autowiring, arguments and tags. Everything else stays in the config file
 * and is reported with the reason.
 */
#[AutowiredService]
final class Neon2AttributesMigrator
{

	private const SERVICE_KEYS = ['class', 'type', 'factory', 'create', 'arguments', 'autowired', 'tags'];

	private const NEW_EXPRESSION_MARKER = 'olvlvl\\ComposerAttributeCollector\\NewExpression';

	private const DI_ATTRIBUTES = ['AutowiredService', 'NonAutowiredService', 'RegisteredRule', 'RegisteredCollector', 'GenerateFactory', 'Conditional'];

	public function __construct(
		private NeonPrinter $neonPrinter,
		private PhpClassAttributesWriter $attributesWriter,
	)
	{
	}

	/**
	 * @param list<string> $configFiles NEON config files to migrate
	 * @param list<string> $attributeServicesDirectories
	 * @param int $phpVersionId the lowest PHP version the attributes must work on, for #[Conditional]
	 * @param list<string> $otherServiceNames names of services defined outside of $configFiles, an entry with the same name overrides them
	 */
	public function migrate(array $configFiles, array $attributeServicesDirectories, int $phpVersionId, array $otherServiceNames): Neon2AttributesResult
	{
		// reflection reports real paths, a package from a path repository is a symlink in vendor/
		$directories = array_map(static fn (string $directory): string => rtrim(strtr(realpath($directory) ?: $directory, '\\', '/'), '/'), $attributeServicesDirectories);

		/** @var array<string, array{code: string, tokens: NeonTokenStream, root: NeonArrayNode}> $files */
		$files = [];
		/** @var list<array{file: string, section: string, item: NeonArrayItemNode}> $entries */
		$entries = [];
		/** @var array<string, array{file: string, item: NeonArrayItemNode, tags: array<mixed>}> $conditionalTags by lowercase class name */
		$conditionalTags = [];
		foreach ($configFiles as $file) {
			try {
				$code = FileReader::read($file);
				$tokens = (new NeonLexer())->tokenize($code);
				$root = (new NeonParser())->parse($tokens);
			} catch (CouldNotReadFileException | NeonException) {
				continue;
			}
			if (!$root instanceof NeonArrayNode) {
				continue;
			}

			$files[$file] = ['code' => $code, 'tokens' => $tokens, 'root' => $root];
			foreach ($root->items as $sectionItem) {
				$section = $sectionItem->key !== null ? $sectionItem->key->toValue() : null;
				if (!$sectionItem->value instanceof NeonArrayNode) {
					continue;
				}
				if (in_array($section, ['rules', 'services'], true)) {
					foreach ($sectionItem->value->items as $item) {
						$entries[] = ['file' => $file, 'section' => $section, 'item' => $item];
					}
				} elseif ($section === 'conditionalTags') {
					foreach ($sectionItem->value->items as $item) {
						$class = $item->key !== null ? $item->key->toValue() : null;
						$tags = $item->value->toValue();
						if (!is_string($class) || !is_array($tags)) {
							continue;
						}
						$conditionalTags[strtolower($class)] = ['file' => $file, 'item' => $item, 'tags' => $tags];
					}
				}
			}
		}

		$definitions = [];
		$classCounts = [];
		foreach ($entries as $i => $entry) {
			$definition = $this->createDefinition($entry['section'], $entry['item']);
			$definitions[$i] = $definition;
			if (is_string($definition)) {
				continue;
			}
			$lowerClass = strtolower($definition->className);
			$classCounts[$lowerClass] = ($classCounts[$lowerClass] ?? 0) + 1;
		}

		$converted = [];
		$kept = [];
		$reportedDuplicates = [];
		/** @var array<string, list<NeonArrayItemNode>> $removedItems by config file */
		$removedItems = [];
		/** @var array<string, list<ClassAttributes>> $classEdits by PHP file */
		$classEdits = [];
		foreach ($entries as $i => $entry) {
			$definition = $definitions[$i];
			$line = $entry['item']->startLine ?? 0;
			if (is_string($definition)) {
				$name = $this->getEntryName($entry['item']);
				$kept[] = self::isOutsideClass($name, $directories)
					? new KeptService(KeptService::CATEGORY_OUTSIDE, $name, null, $entry['file'], $line)
					: new KeptService(KeptService::CATEGORY_KEPT, $name, $definition, $entry['file'], $line);
				continue;
			}

			$lowerClass = strtolower($definition->className);
			if ($classCounts[$lowerClass] > 1) {
				if (!array_key_exists($lowerClass, $reportedDuplicates)) {
					$reportedDuplicates[$lowerClass] = true;
					$kept[] = new KeptService(KeptService::CATEGORY_DUPLICATE, $definition->className, sprintf('registered %d times', $classCounts[$lowerClass]), $entry['file'], $line, $classCounts[$lowerClass]);
				}
				continue;
			}

			if (!class_exists($definition->className) && !interface_exists($definition->className)) {
				$kept[] = new KeptService(KeptService::CATEGORY_KEPT, $definition->className, 'class not found', $entry['file'], $line);
				continue;
			}

			$reflection = new ReflectionClass($definition->className);
			$fileName = $reflection->getFileName();
			if ($fileName === false || !self::isInDirectories(strtr($fileName, '\\', '/'), $directories)) {
				$kept[] = new KeptService(KeptService::CATEGORY_OUTSIDE, $reflection->getName(), null, $entry['file'], $line);
				continue;
			}
			if ($definition->name !== null && in_array($definition->name, $otherServiceNames, true)) {
				// attributes only add services, they cannot replace one
				$kept[] = new KeptService(KeptService::CATEGORY_KEPT, $reflection->getName(), sprintf('overrides service %s', $definition->name), $entry['file'], $line);
				continue;
			}

			$conditional = $conditionalTags[$lowerClass] ?? null;
			$classAttributes = $this->createClassAttributes($entry['section'], $definition, $reflection, $conditional === null ? null : $conditional['tags'], $phpVersionId);
			if (is_string($classAttributes)) {
				$kept[] = new KeptService(KeptService::CATEGORY_KEPT, $reflection->getName(), $classAttributes, $entry['file'], $line);
				continue;
			}

			$classEdits[$fileName][] = $classAttributes;
			$removedItems[$entry['file']][] = $entry['item'];
			if ($conditional !== null) {
				$removedItems[$conditional['file']][] = $conditional['item'];
			}

			$attributes = [$classAttributes->attribute->name];
			if (count($classAttributes->parameterAttributes) > 0) {
				$attributes[] = 'AutowiredParameter';
			}
			$converted[] = new ConvertedService($classAttributes->reflection->getName(), $attributes, $entry['file']);
		}

		$changedFiles = [];
		foreach ($removedItems as $file => $items) {
			$newCode = $this->removeItems($files[$file], $items);
			if ($newCode === $files[$file]['code']) {
				continue;
			}

			$changedFiles[$file] = $newCode;
		}
		foreach ($classEdits as $file => $edits) {
			$changedFiles[$file] = $this->attributesWriter->write($file, $edits);
		}

		return new Neon2AttributesResult($changedFiles, $converted, $kept);
	}

	/**
	 * @return ServiceDefinition|string the definition, or why it cannot be converted
	 */
	private function createDefinition(string $section, NeonArrayItemNode $item): ServiceDefinition|string
	{
		$value = $item->value->toValue();
		if ($section === 'rules') {
			if (!is_string($value)) {
				return 'not a class';
			}

			return new ServiceDefinition($value, null, [], null, true, [], null);
		}

		$key = $item->key !== null ? $item->key->toValue() : null;
		$name = is_string($key) ? $key : null;
		if (is_string($value) || $value instanceof Entity) {
			$value = ['class' => $value];
		}
		if (!is_array($value)) {
			return 'not a class';
		}

		if (array_key_exists('implement', $value)) {
			return $this->createFactoryDefinition($name, $value);
		}

		foreach (array_keys($value) as $serviceKey) {
			if (!is_string($serviceKey) || !in_array($serviceKey, self::SERVICE_KEYS, true)) {
				return sprintf('unsupported key %s', $serviceKey);
			}
		}

		if (array_key_exists('class', $value) && array_key_exists('type', $value)) {
			return 'both class and type';
		}

		$arguments = $value['arguments'] ?? [];
		// without a factory, Nette creates the type like a class
		$class = $value['class'] ?? $value['type'] ?? null;
		$factory = self::normalizeFactory($value['factory'] ?? $value['create'] ?? null);
		if ($class === null && !self::isServiceMethodFactory($factory)) {
			// `factory: ClassName` and `factory: ClassName(...)` create the class itself
			$class = $factory;
			$factory = null;
			if (array_key_exists('arguments', $value) && $class instanceof Entity) {
				return 'arguments in two places';
			}
		}
		if ($class instanceof Entity && is_string($class->value) && !str_starts_with($class->value, '@')) {
			$arguments = $class->attributes;
			$class = $class->value;
		}
		if ($class === null && $factory !== null) {
			return 'only a factory without a class';
		}
		if (!is_string($class) || str_starts_with($class, '@')) {
			return 'not a class';
		}
		if (!is_array($arguments)) {
			return 'arguments are not a list';
		}

		// `class: Foo` with `factory: Foo` or `factory: Foo(...)` creates the class itself
		$factoryClass = $factory instanceof Entity ? $factory->value : $factory;
		if (is_string($factoryClass) && strcasecmp(ltrim($factoryClass, '\\'), ltrim($class, '\\')) === 0) {
			if ($factory instanceof Entity) {
				if (array_key_exists('arguments', $value)) {
					return 'arguments in two places';
				}
				$arguments = $factory->attributes;
			}
			$factory = null;
		}
		if ($factory !== null && !self::isServiceMethodFactory($factory)) {
			$factoryValue = $factory instanceof Entity ? $factory->value : $factory;
			if (!is_string($factoryValue)) {
				return 'factory is not a method of another service';
			}
			if (!str_starts_with($factoryValue, '@')) {
				if (str_contains($factoryValue, '::')) {
					return 'static factory method';
				}
				if (is_a($factoryValue, $class, true)) {
					return sprintf('service type differs from the created class %s', $factoryValue);
				}

				return 'created by another class';
			}
			if (str_contains($factoryValue, '%')) {
				return 'factory service is chosen by a parameter';
			}
			if (!str_contains($factoryValue, '::')) {
				return 'factory is a reference to another service';
			}

			return 'factory is not a method of another service';
		}
		if ($factory instanceof Entity) {
			$factory = $factory->value;
		}

		$tags = $value['tags'] ?? [];
		if (!is_array($tags)) {
			return 'tags with values';
		}
		foreach ($tags as $tagKey => $tag) {
			if (!is_int($tagKey) || !is_string($tag)) {
				return 'tags with values';
			}
		}

		return new ServiceDefinition($class, $name, $arguments, $factory, $value['autowired'] ?? true, array_values($tags), null);
	}

	/**
	 * @param array<mixed> $value
	 */
	private function createFactoryDefinition(?string $name, array $value): ServiceDefinition|string
	{
		foreach (array_keys($value) as $serviceKey) {
			if ($serviceKey !== 'implement' && $serviceKey !== 'arguments') {
				return sprintf('unsupported key %s', $serviceKey);
			}
		}
		if ($name !== null) {
			return 'a generated factory with a name';
		}

		$interface = $value['implement'];
		$arguments = $value['arguments'] ?? [];
		if (!is_string($interface) || !is_array($arguments)) {
			return 'not a class';
		}
		if (!interface_exists($interface)) {
			return sprintf('interface %s not found', $interface);
		}

		$reflection = new ReflectionClass($interface);
		if (!$reflection->hasMethod('create')) {
			return sprintf('%s has no create() method', $interface);
		}
		$returnType = $reflection->getMethod('create')->getReturnType();
		if (!$returnType instanceof ReflectionNamedType || $returnType->isBuiltin()) {
			return sprintf('%s::create() does not return a class', $interface);
		}

		return new ServiceDefinition($returnType->getName(), null, $arguments, null, true, [], $reflection->getName());
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @param array<mixed>|null $conditionalTags
	 * @return ClassAttributes|string the attributes, or why the definition cannot be converted
	 */
	private function createClassAttributes(string $section, ServiceDefinition $definition, ReflectionClass $reflection, ?array $conditionalTags, int $phpVersionId): ClassAttributes|string
	{
		if (!$reflection->isInstantiable() && $definition->generatedFactory === null && $definition->factory === null) {
			return 'not an instantiable class';
		}
		foreach ($reflection->getAttributes() as $attribute) {
			$shortName = self::getShortName($attribute->getName());
			if (str_starts_with($attribute->getName(), 'PHPStan\\DependencyInjection\\') && in_array($shortName, self::DI_ATTRIBUTES, true)) {
				return sprintf('already has #[%s]', $shortName);
			}
		}

		$parameterAttributes = $this->createParameterAttributes($reflection, $definition->arguments);
		if (is_string($parameterAttributes)) {
			return $parameterAttributes;
		}

		if ($definition->generatedFactory !== null) {
			return new ClassAttributes($reflection, new AttributeSpec('GenerateFactory', ['interface' => new ClassReference($definition->generatedFactory)]), $parameterAttributes);
		}

		if ($section === 'rules') {
			if ($conditionalTags !== null) {
				return 'conditionalTags of a class from the rules section';
			}

			return new ClassAttributes($reflection, new AttributeSpec('RegisteredRule', ['level' => 0]), $parameterAttributes);
		}

		$interfaceTags = [];
		foreach (ValidateServiceTagsExtension::getInterfaceTagMapping() as $interface => $tag) {
			if (!$reflection->implementsInterface($interface)) {
				continue;
			}
			$interfaceTags[] = $tag;
		}
		$interfaceTags = array_values(array_unique($interfaceTags));
		sort($interfaceTags);

		$knownTags = array_values(ValidateServiceTagsExtension::getInterfaceTagMapping());
		foreach ($definition->tags as $tag) {
			if (!in_array($tag, $knownTags, true)) {
				return sprintf('custom tag %s has no attribute equivalent', $tag);
			}
		}

		if ($definition->autowired === false) {
			if ($definition->name === null) {
				return 'autowired: false needs a service name';
			}
			if (count($definition->tags) > 0 || $conditionalTags !== null) {
				return 'autowired: false with tags';
			}

			$arguments = ['name' => $definition->name];
			if ($definition->factory !== null) {
				$arguments['factory'] = $definition->factory;
			}

			return new ClassAttributes($reflection, new AttributeSpec('NonAutowiredService', $arguments), $parameterAttributes);
		}

		$arguments = [];
		if ($definition->name !== null) {
			$arguments['name'] = $definition->name;
		}
		if ($definition->factory !== null) {
			$arguments['factory'] = $definition->factory;
		}
		$as = $this->createAs($reflection, $definition->autowired);
		if (is_string($as)) {
			return $as;
		}
		if ($as !== null) {
			$arguments['as'] = $as;
		}

		if ($conditionalTags !== null) {
			if (count($definition->tags) > 0) {
				return 'tags combined with conditionalTags';
			}

			$condition = $this->createCondition($conditionalTags, $interfaceTags);
			if (is_string($condition)) {
				return $condition;
			}

			$inner = new AttributeSpec('AutowiredService', $arguments);

			return new ClassAttributes($reflection, new AttributeSpec('Conditional', [
				// PHP 8.0 does not support `new` in attribute arguments, AttributeServicesDiscoverer instantiates this array
				'attribute' => $phpVersionId >= 80100 ? $inner : [self::NEW_EXPRESSION_MARKER => [new ClassReference(AutowiredService::class), $inner->arguments]],
				'if' => count($condition) === 1 ? $condition[0] : $condition,
			]), $parameterAttributes);
		}

		$neonTags = array_values(array_unique($definition->tags));
		sort($neonTags);
		if (count($neonTags) === 0) {
			if (count($interfaceTags) > 0) {
				$arguments['autoTag'] = false;
			}
		} elseif ($neonTags !== $interfaceTags) {
			$missing = array_values(array_diff($interfaceTags, $neonTags));
			if (count($missing) > 0) {
				return sprintf('tags do not match the implemented extension interfaces, they would add %s', implode(', ', $missing));
			}

			return sprintf('tags do not match the implemented extension interfaces, they would not add %s', implode(', ', array_diff($neonTags, $interfaceTags)));
		}

		return new ClassAttributes($reflection, new AttributeSpec('AutowiredService', $arguments), $parameterAttributes);
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @return ClassReference|list<ClassReference>|string|null null for the default autowiring
	 */
	private function createAs(ReflectionClass $reflection, mixed $autowired): ClassReference|array|string|null
	{
		if ($autowired === true) {
			return null;
		}
		if ($autowired === 'self') {
			return new ClassReference($reflection->getName());
		}
		if (is_string($autowired)) {
			return new ClassReference($autowired);
		}
		if (is_array($autowired) && count($autowired) > 0) {
			$types = [];
			foreach ($autowired as $key => $type) {
				if (!is_int($key) || !is_string($type)) {
					return 'unsupported autowired value';
				}
				$types[] = new ClassReference($type === 'self' ? $reflection->getName() : $type);
			}

			return $types;
		}

		return 'unsupported autowired value';
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @param array<mixed> $arguments
	 * @return array<string, AttributeSpec>|string attributes by constructor parameter name, or why they cannot be written
	 */
	private function createParameterAttributes(ReflectionClass $reflection, array $arguments): array|string
	{
		if (count($arguments) === 0) {
			return [];
		}

		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			return 'arguments of a class without a constructor';
		}
		if (strtolower($constructor->getDeclaringClass()->getName()) !== strtolower($reflection->getName())) {
			return sprintf('arguments of a constructor declared in %s', $constructor->getDeclaringClass()->getName());
		}

		$parameters = $constructor->getParameters();
		$attributes = [];
		foreach ($arguments as $key => $value) {
			$parameter = null;
			foreach ($parameters as $position => $candidate) {
				if ($key === $position || $key === $candidate->getName()) {
					$parameter = $candidate;
					break;
				}
			}
			if ($parameter === null) {
				return sprintf('argument %s is not a constructor parameter', is_int($key) ? '#' . ($key + 1) : '$' . $key);
			}

			$parameterName = $parameter->getName();
			if (is_array($value)) {
				return sprintf('argument $%s is an inline array', $parameterName);
			}
			if ($value instanceof Entity) {
				return sprintf('argument $%s is an expression', $parameterName);
			}
			if (!is_string($value)) {
				return sprintf('argument $%s is not a string', $parameterName);
			}
			if (str_starts_with($value, '@') && preg_match('#^@[\w.\\\\]+$#D', $value) !== 1) {
				return sprintf('argument $%s is not a service reference', $parameterName);
			}
			$existingAttributes = $parameter->getAttributes(AutowiredParameter::class);
			if (count($existingAttributes) > 0) {
				if (($existingAttributes[0]->newInstance()->ref ?? '%' . $parameterName . '%') !== $value) {
					return sprintf('argument $%s already has a different #[AutowiredParameter]', $parameterName);
				}
				continue;
			}

			$attributes[$parameterName] = new AttributeSpec(
				'AutowiredParameter',
				$value === '%' . $parameterName . '%' ? [] : ['ref' => $value],
			);
		}

		return $attributes;
	}

	/**
	 * @param array<mixed> $conditionalTags
	 * @param list<string> $interfaceTags
	 * @return non-empty-list<string>|string the conditions that all must be met, or why they cannot be converted
	 */
	private function createCondition(array $conditionalTags, array $interfaceTags): string|array
	{
		$conditions = [];
		foreach ($conditionalTags as $condition) {
			$converted = self::convertCondition($condition);
			if ($converted === null) {
				return 'conditionalTags with a condition that is not a parameter';
			}
			$conditions[] = $converted;
		}
		$first = $conditions[0];
		foreach ($conditions as $condition) {
			if ($condition !== $first) {
				return 'conditionalTags with different conditions';
			}
		}

		$tags = array_keys($conditionalTags);
		sort($tags);
		if ($tags !== $interfaceTags) {
			return 'conditionalTags do not match the implemented extension interfaces';
		}

		return is_string($first) ? [$first] : $first;
	}

	/**
	 * @return string|non-empty-list<string>|null
	 */
	private static function convertCondition(mixed $condition): string|array|null
	{
		if (is_array($condition)) {
			$list = [];
			foreach ($condition as $key => $item) {
				$converted = is_int($key) ? self::convertCondition($item) : null;
				if (!is_string($converted)) {
					return null;
				}
				$list[] = $converted;
			}

			return count($list) > 0 ? $list : null;
		}
		if ($condition instanceof Entity) {
			if ($condition->value !== 'not' || count($condition->attributes) !== 1) {
				return null;
			}
			$negated = self::convertCondition($condition->attributes[0] ?? null);
			if (!is_string($negated)) {
				return null;
			}

			return sprintf('not(%s)', $negated);
		}
		if (is_string($condition) && str_contains($condition, '%')) {
			return $condition;
		}

		return null;
	}

	/**
	 * @param array{code: string, tokens: NeonTokenStream, root: NeonArrayNode} $file
	 * @param list<NeonArrayItemNode> $items
	 */
	private function removeItems(array $file, array $items): string
	{
		$root = $file['root'];
		$newRoot = clone $root;
		$newRoot->items = [];
		foreach ($root->items as $sectionItem) {
			if (!$sectionItem->value instanceof NeonArrayNode) {
				$newRoot->items[] = $sectionItem;
				continue;
			}

			$remaining = [];
			foreach ($sectionItem->value->items as $item) {
				if (in_array($item, $items, true)) {
					continue;
				}
				$remaining[] = $item;
			}
			if (count($remaining) === count($sectionItem->value->items)) {
				$newRoot->items[] = $sectionItem;
				continue;
			}
			if (count($remaining) === 0) {
				continue;
			}

			$newValue = clone $sectionItem->value;
			$newValue->items = $remaining;
			$newSectionItem = clone $sectionItem;
			$newSectionItem->value = $newValue;
			$newRoot->items[] = $newSectionItem;
		}

		$code = $file['code'];
		if (count($newRoot->items) === 0) {
			return '';
		}
		$printed = $this->neonPrinter->printFormatPreserving($newRoot, $root, $file['tokens']);
		// nette/neon removes \r, it is put back when every line break had it
		$codeWithoutCr = str_replace("\r", '', $code);
		if ($codeWithoutCr !== $code && str_replace("\n", "\r\n", $codeWithoutCr) === $code) {
			$printed = str_replace("\n", "\r\n", $printed);
		}

		return $printed;
	}

	private function getEntryName(NeonArrayItemNode $item): string
	{
		$key = $item->key !== null ? $item->key->toValue() : null;
		if (is_string($key)) {
			return $key;
		}

		$value = $item->value->toValue();
		if (is_string($value)) {
			return $value;
		}
		$class = is_array($value) ? $value['class'] ?? $value['type'] ?? null : null;
		if (is_string($class)) {
			return $class;
		}
		if ($class instanceof Entity && is_string($class->value)) {
			return $class->value;
		}
		$factory = is_array($value) ? $value['factory'] ?? null : null;
		if ($factory instanceof Entity && is_string($factory->value)) {
			$factory = $factory->value . '()';
		}
		if (is_string($factory)) {
			return sprintf('factory %s', $factory);
		}

		$printed = $item->value->toString();

		return str_contains($printed, "\n") ? 'unnamed service' : $printed;
	}

	/**
	 * Turns `[@service, method]` into `@service::method`.
	 */
	private static function normalizeFactory(mixed $factory): mixed
	{
		if (is_array($factory) && array_keys($factory) === [0, 1] && is_string($factory[0]) && is_string($factory[1]) && str_starts_with($factory[0], '@')) {
			return $factory[0] . '::' . $factory[1];
		}

		return $factory;
	}

	private static function isServiceMethodFactory(mixed $factory): bool
	{
		if ($factory instanceof Entity && $factory->attributes === []) {
			$factory = $factory->value;
		}

		return is_string($factory) && preg_match('#^@[\w\\\\.]+::\w+$#D', $factory) === 1;
	}

	/**
	 * @param list<string> $directories
	 */
	private static function isOutsideClass(string $name, array $directories): bool
	{
		if (!class_exists($name) && !interface_exists($name)) {
			return false;
		}
		$fileName = (new ReflectionClass($name))->getFileName();

		return $fileName === false || !self::isInDirectories(strtr($fileName, '\\', '/'), $directories);
	}

	/**
	 * @param list<string> $directories
	 */
	private static function isInDirectories(string $file, array $directories): bool
	{
		$file = strtr(realpath($file) ?: $file, '\\', '/');
		foreach ($directories as $directory) {
			if (str_starts_with($file, $directory . '/')) {
				return true;
			}
		}

		return false;
	}

	private static function getShortName(string $className): string
	{
		$position = strrpos($className, '\\');

		return $position === false ? $className : substr($className, $position + 1);
	}

}
