<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\NodeTraverser;
use PHPStan\Parser\Parser;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\FileTypeMapper;

final class ExportedNameScopeTrackerTest extends PHPStanTestCase
{

	/**
	 * @return array<string, ExportedNameScope> class name => the scope in effect at its declaration
	 */
	private function collectScopes(string $file): array
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('defaultAnalysisParser');

		$visitor = new ExportedNameScopeCollectingVisitor(new ExportedNameScopeTracker());
		$traverser = new NodeTraverser();
		$traverser->addVisitor($visitor);
		$traverser->traverse($parser->parseFile($file));

		return $visitor->getScopes();
	}

	public function testTracksNamespacesAndUses(): void
	{
		$file = __DIR__ . '/data/name-scope.php';
		$scopes = $this->collectScopes($file);

		$first = $scopes['NameScopeA\First'];
		$this->assertSame('NameScopeA', $first->getNamespace());
		$this->assertSame([
			'scope' => 'PHPStan\Analyser\Scope',
			'mutating' => 'PHPStan\Analyser\MutatingScope',
			'type' => 'PHPStan\Type\Type',
			'verbositylevel' => 'PHPStan\Type\VerbosityLevel',
			'classreflection' => 'PHPStan\Reflection\ClassReflection',
		], $first->getUses());
		$this->assertSame([
			'php_eol' => 'PHP_EOL',
			'const_a' => 'PHPStan\Type\CONST_A',
			'const_b' => 'PHPStan\Type\CONST_B',
			'some_const' => 'PHPStan\Reflection\SOME_CONST',
		], $first->getConstUses());

		// A second namespace block starts from nothing.
		$second = $scopes['NameScopeB\Second'];
		$this->assertSame('NameScopeB', $second->getNamespace());
		$this->assertSame(['classreflection' => 'PHPStan\Reflection\ClassReflection'], $second->getUses());
		$this->assertSame([], $second->getConstUses());
	}

	public function testMatchesFileTypeMapper(): void
	{
		$file = __DIR__ . '/data/name-scope.php';
		$fileTypeMapper = self::getContainer()->getByType(FileTypeMapper::class);

		foreach ($this->collectScopes($file) as $className => $trackedScope) {
			$nameScope = $fileTypeMapper->getIntermediaryNameScope($file, $className, null, null);
			$this->assertNotNull($nameScope, $className);
			$this->assertSame($nameScope->getNamespace(), $trackedScope->getNamespace(), $className);
			$this->assertSame($nameScope->getUses(), $trackedScope->getUses(), $className);
			$this->assertSame($nameScope->getConstUses(), $trackedScope->getConstUses(), $className);
		}
	}

}
