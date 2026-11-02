<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PHPStan\File\FileReader;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_diff;
use function array_keys;
use function array_values;
use function glob;
use function implode;
use function is_file;
use function sort;
use function sprintf;
use function str_replace;
use function symlink;
use function sys_get_temp_dir;
use function ucwords;
use function uniqid;
use function unlink;

#[RequiresPhp('>= 8.0.0')]
class Neon2AttributesMigratorTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{0: string, 1: int, 2: list<string>, 3?: list<string>}>
	 */
	public static function dataMigrate(): iterable
	{
		yield 'rules section' => ['rules-section', 80100, [
			'MyRule: RegisteredRule',
		]];

		yield 'plain service' => ['plain-service', 80100, [
			'StringEntry: AutowiredService',
			'ClassEntry: AutowiredService',
		]];

		yield 'tags' => ['tags', 80100, [
			'TaggedExtension: AutowiredService',
			'UntaggedExtension: AutowiredService',
			'TaggedRule: AutowiredService',
			'kept PartiallyTaggedExtension: tags do not match the implemented extension interfaces, they would add phpstan.constants.alwaysUsedClassConstantsExtension',
			'kept UnknownTag: custom tag my.own.tag has no attribute equivalent',
		]];

		yield 'named service' => ['named-service', 80100, [
			'Named: AutowiredService',
			'NamedString: AutowiredService',
		]];

		yield 'factory' => ['factory', 80100, [
			'Created: AutowiredService',
			'CreatedFactory: AutowiredService',
			'StaticallyCreated: AutowiredService',
			'ArrayCreated: AutowiredService',
			'Implementation: AutowiredService',
			'TypedInterface: AutowiredService',
			'SameClass: AutowiredService',
			'SameClassWithArguments: AutowiredService, AutowiredParameter',
			'kept ParameterCreated: factory service is chosen by a parameter',
			'kept Aliased: factory is a reference to another service',
			'kept StaticWithArguments: arguments of a static factory method',
		]];

		yield 'autowired' => ['autowired', 80100, [
			'AsOneType: AutowiredService',
			'AsTypes: AutowiredService',
			'NotAutowired: NonAutowiredService',
			'kept NotAutowiredWithoutName: autowired: false needs a service name',
			'kept NotAutowiredTagged: autowired: false with tags',
		]];

		yield 'arguments' => ['arguments', 80100, [
			'Named: AutowiredService, AutowiredParameter',
			'Positional: AutowiredService, AutowiredParameter',
			'Entity: AutowiredService, AutowiredParameter',
			'OnlyNamed: AutowiredService',
			'DottedReference: AutowiredService, AutowiredParameter',
			'AlreadyParameter: AutowiredService, AutowiredParameter',
			'kept NonString: argument $flag is not a string',
			'kept Inherited: arguments of a constructor declared in Neon2AttributesFixtures\Arguments\ParentWithConstructor',
			'kept Call: argument $value is an expression',
			'kept InlineArray: argument $list is an inline array',
			'kept DifferentParameter: argument $checkUninitializedProperties already has a different #[AutowiredParameter]',
		]];

		yield 'generate factory' => ['generate-factory', 80100, [
			'Value: GenerateFactory',
			'ValueWithArguments: GenerateFactory, AutowiredParameter',
		]];

		$conditional = [
			'ConditionalRule: Conditional',
			'NegatedRule: Conditional',
			'ListRule: Conditional',
			'ConditionalExtension: Conditional, AutowiredParameter',
			'kept TaggedAndConditional: tags combined with conditionalTags',
			'kept DifferentConditions: conditionalTags with different conditions',
			'kept RuleSectionConditional: conditionalTags of a class from the rules section',
		];
		yield 'conditional PHP 8.1' => ['conditional-php81', 80100, $conditional];
		yield 'conditional PHP 8.0' => ['conditional-php80', 80000, $conditional];

		yield 'duplicates' => ['duplicates', 80100, [
			'duplicate TwiceInServices: registered 2 times',
			'duplicate InServicesAndRules: registered 2 times',
			'duplicate InTwoFiles: registered 2 times',
		]];

		yield 'outside directories' => ['outside-directories', 80100, [
			'Inside\InsideService: AutowiredService',
			'outside OutsideService',
			'outside PHPStan\Rules\Properties\PropertyDescriptor',
			'outside OutsideStatic',
			'outside PHPStan\Command\ErrorFormatter\GithubErrorFormatter',
		], ['errorFormatter.github']];

		yield 'unsupported keys' => ['unsupported-keys', 80100, [
			'kept WithSetup: unsupported key setup',
			'kept TagsWithValues: tags with values',
			'kept AbstractService: not an instantiable class',
			'kept AlreadyAttributed: already has #[AutowiredService]',
			'kept alias: not a class',
		]];

		yield 'formatting' => ['formatting', 80100, [
			'Converted: AutowiredService',
			'kept Kept: unsupported key setup',
			'kept AlsoKept: unsupported key setup',
		]];

		yield 'misc' => ['misc', 80100, [
			'FactoryKey: AutowiredService',
			'FactoryEntity: AutowiredService, AutowiredParameter',
			'AutowiredSelf: AutowiredService',
			'TypeEntry: AutowiredService',
			'kept Missing: class not found',
			'kept CreatedInterface: created by another class',
			'kept factory @FactoryEntity::create(): only a factory without a class',
		]];

		yield 'overrides' => ['overrides', 80100, [
			'Own: AutowiredService',
			'kept JunitFormatter: overrides service errorFormatter.junit',
		], ['errorFormatter.junit']];

		yield 'imports' => ['imports', 80100, [
			'SortedImports: AutowiredService',
			'ExistingImport: AutowiredService',
			'ConflictingImport: AutowiredService',
			'WithDocComment: AutowiredService',
		]];
	}

	/**
	 * @param list<string> $expectedReport
	 * @param list<string> $otherServiceNames
	 */
	#[DataProvider('dataMigrate')]
	public function testMigrate(string $case, int $phpVersionId, array $expectedReport, array $otherServiceNames = []): void
	{
		$directory = __DIR__ . '/data/' . $case;
		$configFiles = glob($directory . '/*.neon');
		$this->assertNotFalse($configFiles);
		sort($configFiles);

		$attributeServicesDirectory = $case === 'outside-directories' ? $directory . '/Inside' : $directory;
		$migrator = self::getContainer()->getByType(Neon2AttributesMigrator::class);
		$result = $migrator->migrate($configFiles, [$attributeServicesDirectory], $phpVersionId, $otherServiceNames);

		$namespace = 'Neon2AttributesFixtures\\' . str_replace('-', '', ucwords($case, '-')) . '\\';
		$report = [];
		foreach ($result->getConvertedServices() as $converted) {
			$report[] = sprintf('%s: %s', str_replace($namespace, '', $converted->className), implode(', ', $converted->attributes));
		}
		foreach ($result->getKeptServices() as $kept) {
			$report[] = $kept->reason === null
				? sprintf('%s %s', $kept->category, str_replace($namespace, '', $kept->name))
				: sprintf('%s %s: %s', $kept->category, str_replace($namespace, '', $kept->name), $kept->reason);
		}
		$this->assertSame($expectedReport, $report);

		$changedFiles = $result->getChangedFiles();
		$files = [...$configFiles, ...self::findPhpFiles($directory)];
		foreach ($files as $file) {
			$expectedFile = $file . '.expected';
			$expected = is_file($expectedFile) ? FileReader::read($expectedFile) : FileReader::read($file);
			$this->assertSame($expected, $changedFiles[$file] ?? FileReader::read($file), sprintf('File %s', $file));
		}
		$this->assertSame([], array_values(array_diff(array_keys($changedFiles), $files)));
	}

	/**
	 * A package installed from a path repository is a symlink in vendor/, while reflection reports the real path.
	 */
	public function testSymlinkedDirectory(): void
	{
		$directory = __DIR__ . '/data/plain-service';
		$link = sys_get_temp_dir() . '/neon2attributes-symlink-' . uniqid();
		if (!@symlink($directory, $link)) {
			$this->markTestSkipped('Cannot create a symlink.');
		}

		try {
			$configFiles = glob($link . '/*.neon');
			$this->assertNotFalse($configFiles);
			$result = self::getContainer()->getByType(Neon2AttributesMigrator::class)->migrate($configFiles, [$link], 80100, []);
		} finally {
			unlink($link);
		}

		$this->assertSame([], $result->getKeptServices());
		$this->assertCount(2, $result->getConvertedServices());
	}

	/**
	 * @return list<string>
	 */
	private static function findPhpFiles(string $directory): array
	{
		$files = [];
		foreach ([$directory . '/*.php', $directory . '/*/*.php'] as $pattern) {
			$found = glob($pattern);
			if ($found === false) {
				continue;
			}
			foreach ($found as $file) {
				$files[] = $file;
			}
		}
		sort($files);

		return $files;
	}

}
