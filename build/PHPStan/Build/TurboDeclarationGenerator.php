<?php declare(strict_types = 1);

namespace PHPStan\Build;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;
use function array_filter;
use function array_is_list;
use function array_keys;
use function array_map;
use function array_values;
use function basename;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function ksort;
use function max;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrchr;
use function strrpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trait_exists;
use function trim;
use function var_export;
use const PREG_SET_ORDER;

/**
 * Derives the declarative half of every shadowing class from its PHP twin
 * and renders it as turbo-ext/src/generated/<Stem>.h for the native
 * registration to call: the class declaration (final/abstract, parent, the
 * directly implemented interfaces), the OBJ_PROP_NUM slot of each instance
 * property the twin declares, and the property declarations themselves.
 * Shared by turbo-ext/bin/generate-declarations.php and the drift check in
 * turbo-ext/bin/side-by-side.php.
 *
 * A property whose declaration reg::Class cannot express (a non-empty
 * array default, a string default of a nullable or union type, an
 * intersection type, a defaulted union of classes) leaves its class without
 * declareProperties(); the class keeps declaring its properties by hand.
 */
final class TurboDeclarationGenerator
{

	/** names a slot constant cannot carry verbatim: C++ keywords and the libc macros of common targets */
	/** reg::PackedArg / reg::SigData hold 16-bit offsets and indexes; UINT16_MAX is reg::NoString / reg::NoArg */
	private const PACKED_LIMIT = 65535;

	private const RESERVED = [
		'alignas', 'alignof', 'and', 'auto', 'bool', 'break', 'case', 'catch', 'char', 'class', 'const', 'continue',
		'default', 'delete', 'do', 'double', 'else', 'enum', 'explicit', 'export', 'extern', 'false', 'float', 'for',
		'friend', 'goto', 'if', 'inline', 'int', 'long', 'mutable', 'namespace', 'new', 'noexcept', 'not', 'nullptr',
		'operator', 'or', 'private', 'protected', 'public', 'register', 'return', 'short', 'signed', 'sizeof', 'static',
		'struct', 'switch', 'template', 'this', 'throw', 'true', 'try', 'typedef', 'typeid', 'typename', 'union',
		'unsigned', 'using', 'virtual', 'void', 'volatile', 'while', 'xor',
		'major', 'minor', 'makedev', 'stdin', 'stdout', 'stderr', 'errno', 'assert', 'unix', 'linux',
	];

	/** @var array<string, true>|null pt_type_trait_*() registrar names, read from TypeTraits.cpp */
	private ?array $registrars = null;

	/**
	 * @param array<string, array{php: string, cpp: string, ...}> $manifest
	 */
	public function __construct(private array $manifest)
	{
	}

	/**
	 * @return array<string, string> repository-relative path => content
	 */
	public function render(): array
	{
		$classesPerCpp = [];
		foreach ($this->manifest as $className => $entry) {
			$classesPerCpp[$entry['cpp']][] = $className;
		}

		$files = [];
		foreach ($this->manifest as $className => $entry) {
			if (!class_exists($className)) {
				throw new RuntimeException(sprintf('%s (shadowed by %s) cannot be loaded', $className, $entry['cpp']));
			}
			$stem = basename($entry['cpp'], '.cpp');
			if (count($classesPerCpp[$entry['cpp']]) > 1) {
				$stem .= '_' . (new ReflectionClass($className))->getShortName();
			}
			$files['turbo-ext/src/generated/' . $stem . '.h'] = $this->renderClass(new ReflectionClass($className), $entry['php'], $stem);
		}

		// the traits the shadowed classes use, for the shared trait registrars
		$traits = [];
		$collect = static function (ReflectionClass $classLike) use (&$collect, &$traits): void {
			foreach ($classLike->getTraits() as $trait) {
				$traits[$trait->getName()] = $trait;
				$collect($trait);
			}
		};
		foreach (array_keys($this->manifest) as $className) {
			$collect(new ReflectionClass($className));
		}
		foreach ($traits as $trait) {
			$path = 'turbo-ext/src/generated/' . $trait->getShortName() . '.h';
			if (isset($files[$path])) {
				throw new RuntimeException(sprintf('%s collides with a generated class header', $path));
			}
			$files[$path] = $this->renderTrait($trait);
		}
		ksort($files);

		return $files;
	}

	/**
	 * @param ReflectionClass<object> $class
	 */
	private function renderClass(ReflectionClass $class, string $phpFile, string $stem): string
	{
		$guard = 'PHPSTANTURBO_GENERATED_' . strtoupper((string) preg_replace('~(?<=[a-z0-9])(?=[A-Z])~', '_', $stem)) . '_H';
		$out = [];
		$out[] = '/* Generated by turbo-ext/bin/generate-declarations.php from';
		$out[] = ' * ' . $phpFile . ' — do not edit. */';
		$out[] = '';
		$out[] = '#ifndef ' . $guard;
		$out[] = '#define ' . $guard;
		$out[] = '';
		$out[] = '#include "../reg.h"';
		$out[] = '';
		$out[] = 'namespace ptdecl::' . $stem . ' {';
		$out[] = '';

		$slots = $this->instanceSlots($class);
		$own = [];
		foreach ($slots as $index => $slot) {
			if ($slot[0] !== $class->getName()) {
				continue;
			}

			$own[] = sprintf('inline constexpr uint32_t %s = %d;', $this->cName($slot[1]), $index);
		}
		if ($own !== []) {
			$out[] = '/* the OBJ_PROP_NUM slots of the instance properties the class declares (the inherited ones come first) */';
			$out[] = 'namespace slot {';
			foreach ($own as $line) {
				$out[] = $line;
			}
			$out[] = '} // namespace slot';
			$out[] = '';
		}

		$out[] = 'inline void declareClass(reg::Class &cls)';
		$out[] = '{';
		$body = [];
		if ($class->isFinal()) {
			$body[] = 'cls.final();';
		}
		if ($class->isAbstract() && !$class->isInterface()) {
			$body[] = 'cls.abstract_();';
		}
		$parent = $class->getParentClass();
		if ($parent !== false) {
			$body[] = sprintf('cls.parent(%s);', $this->cString($parent->getName()));
		}
		$interfaces = $this->directInterfaces($class);
		if ($interfaces !== []) {
			$body[] = sprintf('cls.implements({ %s });', implode(', ', array_map(fn (string $name): string => $this->cString($name), $interfaces)));
		}
		if ($body === []) {
			$body[] = '(void) cls;';
		}
		foreach ($body as $line) {
			$out[] = "\t" . $line;
		}
		$out[] = '}';

		$traitProperties = [];
		foreach ($class->getTraits() as $trait) {
			foreach ($trait->getProperties() as $property) {
				$traitProperties[$property->getName()] = true;
			}
		}
		$declarations = [];
		$unrepresentable = null;
		foreach ($class->getProperties() as $property) {
			if ($property->getDeclaringClass()->getName() !== $class->getName() || isset($traitProperties[$property->getName()])) {
				continue;
			}
			try {
				$declarations[] = $this->renderProperty($property);
			} catch (RuntimeException $e) {
				$unrepresentable = sprintf('$%s: %s', $property->getName(), $e->getMessage());
				break;
			}
		}
		$out[] = '';
		if ($unrepresentable !== null) {
			$out[] = sprintf('/* no declareProperties(): %s */', $unrepresentable);
		} else {
			$out[] = '/* the properties the class declares itself, in declaration order (a used trait\'s come from its registrar) */';
			$out[] = 'inline void declareProperties(reg::Class &cls)';
			$out[] = '{';
			if ($declarations === []) {
				$out[] = "\t(void) cls;";
			}
			foreach ($declarations as $declaration) {
				$out[] = "\t" . $declaration;
			}
			$out[] = '}';
		}

		$registrars = $this->traitRegistrars($class);
		if ($registrars !== []) {
			$out[] = '';
			$out[] = '/* the shared registrars of the traits the twin uses, in its own order (a used trait\'s own traits after it) */';
			$out[] = 'inline void registerTraits(reg::Class &cls)';
			$out[] = '{';
			foreach ($registrars as $registrar) {
				$out[] = sprintf("\tpt_type_trait_%s(cls);", $registrar);
			}
			$out[] = '}';
		}

		$signatures = $this->renderSignatures($class);
		if ($signatures['sigs'] !== []) {
			$out[] = '';
			foreach ($this->signatureBlock($signatures, 'the signatures of the methods the class declares itself (a used trait\'s are in the trait\'s header)') as $line) {
				$out[] = $line;
			}
		}

		$out[] = '';
		$out[] = '} // namespace ptdecl::' . $stem;
		$out[] = '';
		$out[] = '#endif';
		$out[] = '';

		return implode("\n", $out);
	}

	/**
	 * @param ReflectionClass<object> $trait
	 */
	private function renderTrait(ReflectionClass $trait): string
	{
		$stem = $trait->getShortName();
		$guard = 'PHPSTANTURBO_GENERATED_' . strtoupper((string) preg_replace('~(?<=[a-z0-9])(?=[A-Z])~', '_', $stem)) . '_H';
		$out = [
			'/* Generated by turbo-ext/bin/generate-declarations.php from',
			' * ' . $this->relativeFile((string) $trait->getFileName()) . ' — do not edit. */',
			'',
			'#ifndef ' . $guard,
			'#define ' . $guard,
			'',
			'#include "../reg.h"',
			'',
			'namespace ptdecl::' . $stem . ' {',
			'',
		];
		foreach ($this->signatureBlock($this->renderSignatures($trait), 'the signatures of the methods the trait declares itself') as $line) {
			$out[] = $line;
		}
		$out[] = '';
		$out[] = '} // namespace ptdecl::' . $stem;
		$out[] = '';
		$out[] = '#endif';
		$out[] = '';

		return implode("\n", $out);
	}

	/**
	 * The shared trait registrars the twin's `use` declarations imply: its
	 * traits in declaration order, each followed by the traits it uses
	 * itself, mapped to their pt_type_trait_*() registrar and deduplicated
	 * (reg::Class::traitMethod() lets the first registrar win, as PHP lets
	 * the class body win over a used trait).
	 *
	 * @param ReflectionClass<object> $class
	 * @return list<string>
	 */
	private function traitRegistrars(ReflectionClass $class): array
	{
		$file = $class->getFileName();
		if ($file === false) {
			return [];
		}
		$seen = [];

		return $this->flattenTraitUses($file, $seen);
	}

	/**
	 * @param array<string, true> $seen
	 * @return list<string>
	 */
	private function flattenTraitUses(string $file, array &$seen): array
	{
		$registrars = [];
		foreach ($this->traitUses($file) as $trait) {
			if (isset($seen[$trait])) {
				continue;
			}
			$seen[$trait] = true;
			$registrar = $this->registrarOf($trait);
			if ($registrar !== null) {
				$registrars[] = $registrar;
			}
			if (!trait_exists($trait)) {
				continue;
			}
			$traitFile = (new ReflectionClass($trait))->getFileName();
			if ($traitFile === false) {
				continue;
			}

			foreach ($this->flattenTraitUses($traitFile, $seen) as $nested) {
				$registrars[] = $nested;
			}
		}

		return $registrars;
	}

	/**
	 * The `use Trait;` and `use Trait { ... }` declarations of the first
	 * class-like in the file, resolved through its imports.
	 *
	 * @return list<string>
	 */
	private function traitUses(string $file): array
	{
		$source = file_get_contents($file);
		if ($source === false) {
			return [];
		}
		$imports = [];
		preg_match_all('~^use ([\w\\\\]+)(?:\s+as\s+(\w+))?;~m', $source, $importMatches, PREG_SET_ORDER);
		foreach ($importMatches as $match) {
			$imports[($match[2] ?? '') !== '' ? $match[2] : substr((string) strrchr('\\' . $match[1], '\\'), 1)] = $match[1];
		}
		$start = max(strpos($source, 'trait ') === false ? -1 : strpos($source, 'trait '), strpos($source, 'class ') === false ? -1 : strpos($source, 'class '));
		if ($start < 0) {
			return [];
		}
		$bodyStart = strpos($source, '{', $start);
		$body = $bodyStart === false ? '' : substr($source, $bodyStart);
		$names = [];
		preg_match_all('~^\t+use ([\w\\\\,\s]+?)\s*(?:;|\{)~m', $body, $useMatches, PREG_SET_ORDER);
		foreach ($useMatches as $match) {
			foreach (explode(',', $match[1]) as $name) {
				$name = trim($name);
				if ($name === '') {
					continue;
				}
				$names[] = $imports[$name] ?? $name;
			}
		}

		return $names;
	}

	/** the pt_type_trait_*() registrar implementing a trait, if the extension has one */
	private function registrarOf(string $trait): ?string
	{
		if ($this->registrars === null) {
			$source = (string) file_get_contents(dirname(__DIR__, 3) . '/turbo-ext/src/TypeTraits.cpp');
			$this->registrars = [];
			preg_match_all('~^void pt_type_trait_(\w+)\(reg::Class &cls\)$~m', $source, $registrarMatches, PREG_SET_ORDER);
			foreach ($registrarMatches as $match) {
				$this->registrars[$match[1]] = true;
			}
		}
		$separator = strrpos($trait, '\\');
		$short = $separator === false ? $trait : substr($trait, $separator + 1);
		foreach ([preg_replace('~(Type)?Trait$~', '', $short), preg_replace('~Trait$~', '', $short)] as $base) {
			$name = strtolower((string) preg_replace('~(?<!^)(?=[A-Z])~', '_', (string) $base));
			if (isset($this->registrars[$name])) {
				return $name;
			}
		}

		return null;
	}

	/**
	 * The generated signature of every method declared in the class-like's
	 * own file (a used trait's methods are declared in the trait's), and
	 * the string and parameter tables they index into. The records carry
	 * offsets rather than pointers: a pointer in a constant table is a
	 * load-time relocation, and these tables were 89% of the extension's
	 * relocations (see reg::Sig).
	 *
	 * @param ReflectionClass<object> $class
	 * @return array{tables: list<string>, sigs: list<string>}
	 */
	private function renderSignatures(ReflectionClass $class): array
	{
		// the strings in table order, and their offsets by value (keyed with a
		// prefix: a numeric default like "0" would otherwise become an int key)
		$strings = [];
		$offsets = [];
		$stringsSize = 0;
		$string = static function (?string $value) use (&$strings, &$offsets, &$stringsSize): string {
			if ($value === null) {
				return 'reg::NoString';
			}
			$key = 's' . $value;
			if (!isset($offsets[$key])) {
				if ($stringsSize >= self::PACKED_LIMIT) {
					throw new RuntimeException('the signature string table outgrew its 16-bit offsets');
				}
				$offsets[$key] = $stringsSize;
				$strings[] = $value;
				$stringsSize += strlen($value) + 1;
			}

			return (string) $offsets[$key];
		};
		$table = [];
		$sigs = [];
		foreach ($class->getMethods() as $method) {
			if ($method->getDeclaringClass()->getName() !== $class->getName() || $method->getFileName() !== $class->getFileName()) {
				continue;
			}
			$id = $this->cName($method->getName());
			try {
				$args = [];
				foreach ($method->getParameters() as $parameter) {
					[$mask, $className] = $this->signatureType($parameter->getType());
					$default = $parameter->isDefaultValueAvailable() ? $this->defaultSource($parameter) : null;
					$args[] = [$parameter->getName(), $mask, $className, $parameter->isPassedByReference(), $parameter->isVariadic(), $default];
				}
				$returnType = $method->getReturnType() ?? $method->getTentativeReturnType();
				$return = null;
				if ($returnType !== null) {
					$return = ['', ...$this->signatureType($returnType), false, false, null];
				}
			} catch (RuntimeException $e) {
				$sigs[] = sprintf('/* %s(): no signature — %s */', $method->getName(), $e->getMessage());
				continue;
			}
			$first = count($table);
			foreach ($return !== null ? [...$args, $return] : $args as [$name, $mask, $className, $byRef, $variadic, $default]) {
				$pieces = [$string($name), $mask];
				if ($className !== null || $byRef || $variadic || $default !== null) {
					$pieces[] = $string($className);
				}
				if ($byRef || $variadic || $default !== null) {
					$pieces[] = $byRef ? 'true' : 'false';
					$pieces[] = $variadic ? 'true' : 'false';
				}
				if ($default !== null) {
					$pieces[] = $string($default);
				}
				$table[] = sprintf("\treg::packed(%s), /* %s%s */", implode(', ', $pieces), $method->getName(), $name !== '' ? ' $' . $name : ' return');
			}
			if (count($table) >= self::PACKED_LIMIT) {
				throw new RuntimeException('the signature parameter table outgrew its 16-bit indexes');
			}
			$sigs[] = sprintf(
				'inline constexpr sigtab::Sig %s = { { %s /* %s */, %d, %d, %d, %s, %s } };',
				$id,
				$string($method->getName()),
				$method->getName(),
				$method->getNumberOfRequiredParameters(),
				$first,
				count($args),
				$return !== null ? (string) ($first + count($args)) : 'reg::NoArg',
				$this->methodFlags($method),
			);
		}
		if ($sigs === []) {
			return ['tables' => [], 'sigs' => []];
		}

		$tables = ['inline constexpr char strings[] ='];
		$last = count($strings) - 1;
		foreach ($strings as $i => $value) {
			$tables[] = sprintf("\t%s%s /* %d */", $i === $last ? $this->cString($value) : substr($this->cString($value), 0, -1) . '\\0"', $i === $last ? ';' : '', $offsets['s' . $value]);
		}
		if ($strings === []) {
			$tables[] = "\t\"\";";
		}
		$tables[] = 'inline constexpr reg::PackedArg args[] = {';
		foreach ($table !== [] ? $table : ["\treg::packed(0, 0), /* placeholder: C++ has no empty arrays */"] as $line) {
			$tables[] = $line;
		}
		$tables[] = '};';
		$tables[] = 'using Sig = reg::Sig<strings, args>;';

		return ['tables' => $tables, 'sigs' => $sigs];
	}

	/**
	 * @param array{tables: list<string>, sigs: list<string>} $signatures
	 * @return list<string>
	 */
	private function signatureBlock(array $signatures, string $comment): array
	{
		return [
			'/* the string and parameter tables the signatures below index into (see reg::Sig) */',
			'namespace sigtab {',
			...$signatures['tables'],
			'} // namespace sigtab',
			'',
			'/* ' . $comment . ' */',
			'namespace sig {',
			...$signatures['sigs'],
			'} // namespace sig',
		];
	}

	private function methodFlags(ReflectionMethod $method): string
	{
		$flags = [$method->isPrivate() ? 'ZEND_ACC_PRIVATE' : ($method->isProtected() ? 'ZEND_ACC_PROTECTED' : 'ZEND_ACC_PUBLIC')];
		if ($method->isStatic()) {
			$flags[] = 'ZEND_ACC_STATIC';
		}
		if ($method->isFinal()) {
			$flags[] = 'ZEND_ACC_FINAL';
		}
		if ($method->isAbstract()) {
			$flags[] = 'ZEND_ACC_ABSTRACT';
		}

		return implode(' | ', $flags);
	}

	/**
	 * @return array{string, string|null} MAY_BE_* mask expression, literal class name(s)
	 */
	private function signatureType(?ReflectionType $type): array
	{
		if ($type === null) {
			return ['0', null];
		}
		if ($type instanceof ReflectionIntersectionType) {
			throw new RuntimeException('an intersection type');
		}
		$builtin = [
			'int' => 'MAY_BE_LONG', 'float' => 'MAY_BE_DOUBLE', 'string' => 'MAY_BE_STRING', 'bool' => 'MAY_BE_BOOL',
			'array' => 'MAY_BE_ARRAY', 'null' => 'MAY_BE_NULL', 'false' => 'MAY_BE_FALSE', 'true' => 'MAY_BE_TRUE',
			'mixed' => 'MAY_BE_ANY', 'object' => 'MAY_BE_OBJECT', 'callable' => 'MAY_BE_CALLABLE', 'iterable' => '_ZEND_TYPE_ITERABLE_BIT',
			'void' => 'MAY_BE_VOID', 'never' => 'MAY_BE_NEVER', 'static' => 'MAY_BE_STATIC',
		];
		$members = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
		$masks = [];
		$classes = [];
		foreach ($members as $member) {
			if (!$member instanceof ReflectionNamedType) {
				throw new RuntimeException('a nested intersection in a union');
			}
			if (isset($builtin[$member->getName()])) {
				$masks[$builtin[$member->getName()]] = true;
			} else {
				$classes[] = $member->getName();
			}
		}
		if ($type instanceof ReflectionNamedType && $type->allowsNull() && !in_array($type->getName(), ['mixed', 'null'], true)) {
			$masks['MAY_BE_NULL'] = true;
		}
		$order = ['MAY_BE_NULL', 'MAY_BE_FALSE', 'MAY_BE_TRUE', 'MAY_BE_BOOL', 'MAY_BE_LONG', 'MAY_BE_DOUBLE', 'MAY_BE_STRING', 'MAY_BE_ARRAY', 'MAY_BE_OBJECT', 'MAY_BE_CALLABLE', '_ZEND_TYPE_ITERABLE_BIT', 'MAY_BE_VOID', 'MAY_BE_NEVER', 'MAY_BE_STATIC', 'MAY_BE_ANY'];
		$ordered = array_values(array_filter($order, static fn (string $m): bool => isset($masks[$m])));

		return [$ordered === [] ? '0' : implode(' | ', $ordered), $classes === [] ? null : implode('|', $classes)];
	}

	private function defaultSource(ReflectionParameter $parameter): string
	{
		if ($parameter->isDefaultValueConstant()) {
			return (string) $parameter->getDefaultValueConstantName();
		}
		$value = $parameter->getDefaultValue();
		if ($value === null) {
			return 'null';
		}
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		if (is_int($value)) {
			return (string) $value;
		}
		if (is_string($value)) {
			return var_export($value, true);
		}
		if (is_array($value) && array_is_list($value) && array_filter($value, static fn ($item): bool => !is_int($item) && !is_bool($item)) === []) {
			return '[' . implode(', ', array_map(static fn ($item): string => var_export($item, true), $value)) . ']';
		}

		throw new RuntimeException('a default value the arginfo source cannot spell');
	}

	private function relativeFile(string $path): string
	{
		$root = dirname(__DIR__, 3) . '/';

		return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
	}

	/**
	 * The instance properties in OBJ_PROP_NUM order: the parent's first, then
	 * the class's own in declaration order (a used trait's after them).
	 *
	 * @param ReflectionClass<object> $class
	 * @return list<array{string, string}> declaring class, property name
	 */
	private function instanceSlots(ReflectionClass $class): array
	{
		$parent = $class->getParentClass();
		$slots = $parent !== false ? $this->instanceSlots($parent) : [];
		foreach ($class->getProperties() as $property) {
			if ($property->isStatic() || $property->getDeclaringClass()->getName() !== $class->getName()) {
				continue;
			}
			$slots[] = [$class->getName(), $property->getName()];
		}

		return $slots;
	}

	/**
	 * The interfaces the class declaration itself names: neither inherited
	 * from the parent nor extended by another of them.
	 *
	 * @param ReflectionClass<object> $class
	 * @return list<string>
	 */
	private function directInterfaces(ReflectionClass $class): array
	{
		$parent = $class->getParentClass();
		$inherited = $parent !== false ? $parent->getInterfaceNames() : [];
		$candidates = array_values(array_filter($class->getInterfaceNames(), static fn (string $name): bool => !in_array($name, $inherited, true)));

		return array_values(array_filter($candidates, static function (string $name) use ($candidates): bool {
			foreach ($candidates as $other) {
				if ($other !== $name && in_array($name, (new ReflectionClass($other))->getInterfaceNames(), true)) {
					return false;
				}
			}

			return true;
		}));
	}

	private function renderProperty(ReflectionProperty $property): string
	{
		$visibility = [$property->isPrivate() ? 'ZEND_ACC_PRIVATE' : ($property->isProtected() ? 'ZEND_ACC_PROTECTED' : 'ZEND_ACC_PUBLIC')];
		if ($property->isStatic()) {
			$visibility[] = 'ZEND_ACC_STATIC';
		}
		if ($property->isReadOnly()) {
			$visibility[] = 'ZEND_ACC_READONLY';
		}
		$flags = implode(' | ', $visibility);
		$name = $this->cString($property->getName());
		$hasDefault = $property->hasDefaultValue() && !$property->isPromoted();
		$default = $hasDefault ? $property->getDefaultValue() : null;

		if (!$property->hasType()) {
			if ($default === null) {
				return sprintf('cls.property(%s, %s, reg::PropertyKind::Null, 0);', $name, $flags);
			}
			if ($default === []) {
				return sprintf('cls.property(%s, %s, reg::PropertyKind::EmptyArray, 0);', $name, $flags);
			}
			if (is_bool($default)) {
				return sprintf('cls.property(%s, %s, reg::PropertyKind::Bool, %d);', $name, $flags, $default ? 1 : 0);
			}
			if (is_int($default)) {
				return sprintf('cls.property(%s, %s, reg::PropertyKind::Long, %d);', $name, $flags, $default);
			}
			throw new RuntimeException('an untyped default reg::Class cannot declare');
		}

		[$masks, $classes] = $this->typeParts($property->getType());
		$mask = $masks === [] ? '0' : implode(' | ', $masks);
		$classArg = count($classes) === 0 ? '' : ', ' . $this->cString(implode('|', $classes));

		if (count($classes) > 1) {
			if ($hasDefault || array_filter($masks, static fn (string $m): bool => $m !== 'MAY_BE_NULL') !== []) {
				throw new RuntimeException('a union of classes with a default or scalar members');
			}
			return sprintf('cls.property(%s, %s, reg::PropertyKind::TypedClassUnion, %s%s);', $name, $flags, $mask, $classArg);
		}
		if (!$hasDefault) {
			return sprintf('cls.property(%s, %s, reg::PropertyKind::Typed, %s%s);', $name, $flags, $mask, $classArg);
		}
		if ($default === null) {
			return sprintf('cls.property(%s, %s, reg::PropertyKind::TypedNull, %s%s);', $name, $flags, $mask, $classArg);
		}
		if ($default === [] && $classes === []) {
			return sprintf('cls.property(%s, %s, reg::PropertyKind::TypedEmptyArray, %s);', $name, $flags, $mask);
		}
		if (is_bool($default) && $classes === [] && $masks === ['MAY_BE_BOOL']) {
			return sprintf('cls.property(%s, %s, reg::PropertyKind::TypedBool, %d);', $name, $flags, $default ? 1 : 0);
		}
		if (is_int($default) && $classes === [] && $masks === ['MAY_BE_LONG']) {
			return sprintf('cls.property(%s, %s, reg::PropertyKind::TypedLong, %d);', $name, $flags, $default);
		}
		if ($default === false) {
			return sprintf('cls.property(%s, %s, reg::PropertyKind::TypedFalse, %s%s);', $name, $flags, $mask, $classArg);
		}
		if (is_string($default) && $classes === [] && $masks === ['MAY_BE_STRING']) {
			return sprintf('cls.typedStringProperty(%s, %s, %s);', $name, $flags, $this->cString($default));
		}

		throw new RuntimeException('a typed default reg::Class cannot declare');
	}

	/**
	 * @return array{list<string>, list<string>} MAY_BE_* masks, class names
	 */
	private function typeParts(?ReflectionType $type): array
	{
		if ($type instanceof ReflectionIntersectionType) {
			throw new RuntimeException('an intersection type');
		}
		$builtin = [
			'int' => 'MAY_BE_LONG', 'float' => 'MAY_BE_DOUBLE', 'string' => 'MAY_BE_STRING', 'bool' => 'MAY_BE_BOOL',
			'array' => 'MAY_BE_ARRAY', 'null' => 'MAY_BE_NULL', 'false' => 'MAY_BE_FALSE', 'true' => 'MAY_BE_TRUE',
			'mixed' => 'MAY_BE_ANY', 'object' => 'MAY_BE_OBJECT',
		];
		$members = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
		$masks = [];
		$classes = [];
		foreach ($members as $member) {
			if (!$member instanceof ReflectionNamedType) {
				throw new RuntimeException('a nested intersection in a union');
			}
			$typeName = $member->getName();
			if (isset($builtin[$typeName])) {
				$masks[$builtin[$typeName]] = true;
			} else {
				$classes[] = $typeName;
			}
		}
		if ($type instanceof ReflectionNamedType && $type->allowsNull() && $type->getName() !== 'mixed' && $type->getName() !== 'null') {
			$masks['MAY_BE_NULL'] = true;
		}
		$ordered = array_values(array_filter(
			['MAY_BE_NULL', 'MAY_BE_FALSE', 'MAY_BE_TRUE', 'MAY_BE_BOOL', 'MAY_BE_LONG', 'MAY_BE_DOUBLE', 'MAY_BE_STRING', 'MAY_BE_ARRAY', 'MAY_BE_OBJECT', 'MAY_BE_ANY'],
			static fn (string $m): bool => isset($masks[$m]),
		));

		return [$ordered, $classes];
	}

	private function cString(string $value): string
	{
		return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
	}

	private function cName(string $propertyName): string
	{
		return in_array($propertyName, self::RESERVED, true) ? $propertyName . '_' : $propertyName;
	}

}
