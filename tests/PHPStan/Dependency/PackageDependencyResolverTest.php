<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

final class PackageDependencyResolverTest extends PHPStanTestCase
{

	public function testResolvePackage(): void
	{
		$fixtureRoot = __DIR__ . '/data/package-resolver';
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$resolver = new PackageDependencyResolver([$fixtureRoot], $fileHelper);

		// Files under a project package install path resolve to that package.
		$this->assertSame('acme/widget', $resolver->resolvePackage($fixtureRoot . '/vendor/acme/widget/src/Widget.php'));
		$this->assertSame('acme/gadget', $resolver->resolvePackage($fixtureRoot . '/vendor/acme/gadget/Gadget.php'));

		// The root package is skipped, so project sources belong to no package.
		$this->assertNull($resolver->resolvePackage($fixtureRoot . '/src/App.php'));

		// A path outside the project entirely.
		$this->assertNull($resolver->resolvePackage('/outside/the/project/File.php'));
	}

	public function testExtractComposerPackageVersions(): void
	{
		$resolver = new PackageDependencyResolver([], self::getContainer()->getByType(FileHelper::class));

		// reference is preferred, then version, then pretty_version, else the empty string.
		$this->assertSame(
			['acme/a' => 'ref-a', 'acme/b' => '2.0.0', 'acme/c' => 'dev-main', 'acme/d' => ''],
			$resolver->extractComposerPackageVersions([
				[
					'versions' => [
						'acme/a' => ['reference' => 'ref-a', 'version' => '1.0.0'],
						'acme/b' => ['version' => '2.0.0'],
						'acme/c' => ['pretty_version' => 'dev-main'],
						'acme/d' => [],
					],
				],
			]),
		);

		// Multiple installed.php entries (one per autoload path) merge.
		$this->assertSame(
			['acme/a' => 'ref-a', 'acme/b' => 'ref-b'],
			$resolver->extractComposerPackageVersions([
				['versions' => ['acme/a' => ['reference' => 'ref-a']]],
				['versions' => ['acme/b' => ['reference' => 'ref-b']]],
			]),
		);

		// Unparseable shapes return null, so the caller falls back to a full re-analysis.
		$this->assertNull($resolver->extractComposerPackageVersions(null));
		$this->assertNull($resolver->extractComposerPackageVersions('not-an-array'));
		$this->assertNull($resolver->extractComposerPackageVersions([['no-versions-key' => []]]));
	}

	public function testGetChangedComposerPackages(): void
	{
		$resolver = new PackageDependencyResolver([], self::getContainer()->getByType(FileHelper::class));

		$cached = ['composerInstalled' => [['versions' => [
			'acme/stable' => ['reference' => 'r1'],
			'acme/bumped' => ['reference' => 'old'],
			'acme/removed' => ['reference' => 'r2'],
		]]]];
		$current = ['composerInstalled' => [['versions' => [
			'acme/stable' => ['reference' => 'r1'],
			'acme/bumped' => ['reference' => 'new'],
			'acme/added' => ['reference' => 'r3'],
		]]]];

		// A bumped reference, a newly added package and a removed package all count as changed;
		// the unchanged package does not.
		$this->assertSame(
			['acme/bumped', 'acme/added', 'acme/removed'],
			$resolver->getChangedComposerPackages($cached, $current),
		);

		// Identical metas mean nothing changed.
		$this->assertSame([], $resolver->getChangedComposerPackages($cached, $cached));

		// An unparseable meta returns null, so the caller falls back to a full re-analysis.
		$this->assertNull($resolver->getChangedComposerPackages(['composerInstalled' => 'broken'], $current));
	}

}
