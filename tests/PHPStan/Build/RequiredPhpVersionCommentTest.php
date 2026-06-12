<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPStan\Php\PhpVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function explode;
use function file_get_contents;
use function preg_match;
use function sprintf;
use function strtok;

final class RequiredPhpVersionCommentTest extends TestCase
{

	/**
	 * Directories whose fixtures are parsed on every CI PHP version (they are
	 * excluded from parallel-lint and skipped via the `// lint` comment instead).
	 *
	 * @return iterable<string, array{string}>
	 */
	public static function dataFixtures(): iterable
	{
		$directory = __DIR__ . '/../Analyser/nsrt';
		$finder = new Finder();
		$finder->followLinks();
		foreach ($finder->files()->name('*.php')->in($directory) as $fileInfo) {
			$path = $fileInfo->getPathname();
			yield $path => [$path];
		}
	}

	#[DataProvider('dataFixtures')]
	public function testFixtureHasRequiredLintComment(string $file): void
	{
		$code = file_get_contents($file);
		if ($code === false) {
			self::fail(sprintf('Could not read %s', $file));
		}

		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$ast = $parser->parse($code);
		if ($ast === null) {
			self::fail(sprintf('Could not parse %s', $file));
		}

		$visitor = new RequiredPhpVersionVisitor();
		$traverser = new NodeTraverser($visitor);
		$traverser->traverse($ast);

		$requiredVersionId = $visitor->getRequiredVersionId();
		if ($requiredVersionId === null) {
			$this->expectNotToPerformAssertions();
			return;
		}

		$requiredVersion = new PhpVersion($requiredVersionId);
		$guaranteedMinVersionId = self::guaranteedMinVersionId($code);

		self::assertGreaterThanOrEqual(
			$requiredVersionId,
			$guaranteedMinVersionId,
			sprintf(
				'Fixture uses %s which requires PHP %s. Add a `<?php // lint >= %s` comment on the first line so the fixture is skipped on older PHP versions in CI.',
				$visitor->getReason(),
				$requiredVersion->getVersionString(),
				$requiredVersion->getVersionString(),
			),
		);
	}

	/**
	 * @return iterable<string, array{string, int|null}>
	 */
	public static function dataDetectedVersion(): iterable
	{
		yield 'plain code' => ['<?php function foo(): int { return 1; }', null];
		yield 'enum' => ['<?php enum Foo { case A; }', 80100];
		yield 'readonly property' => ['<?php class Foo { public readonly int $x; }', 80100];
		yield 'readonly promoted property' => ['<?php class Foo { public function __construct(public readonly int $x) {} }', 80100];
		yield 'intersection type' => ['<?php function foo(A&B $x) {}', 80100];
		yield 'first-class callable' => ['<?php strlen(...);', 80100];
		yield 'readonly class' => ['<?php readonly class Foo {}', 80200];
		yield 'standalone null type' => ['<?php function foo(): null { return null; }', 80200];
		yield 'standalone false type' => ['<?php function foo(): false { return false; }', 80200];
		yield 'standalone true type' => ['<?php function foo(): true { return true; }', 80200];
		yield 'true in union type' => ['<?php function foo(): true|int { return 1; }', 80200];
		yield 'disjunctive normal form type' => ['<?php function foo(): (A&B)|null {}', 80200];
		yield 'typed class constant' => ['<?php class Foo { const int BAR = 1; }', 80300];
		yield 'dynamic class constant fetch' => ['<?php echo Foo::{$bar};', 80300];
		yield 'property hook' => ['<?php class Foo { public int $x { get => 1; } }', 80400];
		yield 'asymmetric visibility' => ['<?php class Foo { public private(set) int $x = 1; }', 80400];
		yield 'pipe operator' => ['<?php $x = 1 |> strlen(...);', 80500];
	}

	#[DataProvider('dataDetectedVersion')]
	public function testDetectedVersion(string $code, ?int $expectedVersionId): void
	{
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$ast = $parser->parse($code);
		self::assertNotNull($ast);

		$visitor = new RequiredPhpVersionVisitor();
		$traverser = new NodeTraverser($visitor);
		$traverser->traverse($ast);

		self::assertSame($expectedVersionId, $visitor->getRequiredVersionId());
	}

	private static function guaranteedMinVersionId(string $code): int
	{
		$firstLine = strtok($code, "\n");
		if ($firstLine === false) {
			return 0;
		}

		if (preg_match('~^<\?php\s*//\s*lint\s*(<=|>=|==|=|<|>)\s*([\d.]+)~i', $firstLine, $matches) !== 1) {
			return 0;
		}

		$operator = $matches[1];
		$versionId = self::versionStringToId($matches[2]);

		// Only lower-bound constraints guarantee that newer syntax is available.
		// "< X" / "<= X" mean the file also runs on older PHP, so it must not use
		// any version-gated feature.
		if ($operator === '>=' || $operator === '>' || $operator === '==' || $operator === '=') {
			return $versionId;
		}

		return 0;
	}

	private static function versionStringToId(string $version): int
	{
		$parts = explode('.', $version);
		$major = (int) $parts[0];
		$minor = (int) ($parts[1] ?? 0);
		$patch = (int) ($parts[2] ?? 0);

		return $major * 10000 + $minor * 100 + $patch;
	}

}
