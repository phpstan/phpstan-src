<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use function array_map;

class PhpDocsResolverTest extends PHPStanTestCase
{

	public function testParameterNamesFromPhpBuiltNodes(): void
	{
		$file = __DIR__ . '/data/php-docs-resolver-parameter-names.php';
		require_once $file;

		$reflectionProvider = self::createReflectionProvider();
		$scope = self::createScopeFactory($reflectionProvider, self::getContainer()->getService('typeSpecifier'))
			->create(ScopeContext::create($file))
			->enterClass($reflectionProvider->getClass('PhpDocsResolverParameterNames\Foo'));

		// the names are PHP literals (interned strings), unlike the ones
		// the parser allocates
		$node = new ClassMethod(new Identifier('doFoo'), [
			'params' => [
				new Param(new Variable('a')),
				new Param(new Variable('count')),
			],
		], [
			'comments' => [
				new Doc("/**\n\t * @param non-empty-string \$a\n\t * @param positive-int \$count\n\t */"),
			],
		]);

		$phpDocParameterTypes = self::getContainer()->getByType(PhpDocsResolver::class)->getPhpDocs($scope, $node)[1];

		$this->assertSame([
			'a' => 'non-empty-string',
			'count' => 'int<1, max>',
		], array_map(static fn ($type) => $type->describe(VerbosityLevel::precise()), $phpDocParameterTypes));
	}

}
