<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ExprCacheHelper;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;

class CachedParserTest extends PHPStanTestCase
{

	#[DataProvider('dataParseFileClearCache')]
	public function testParseFileClearCache(
		int $cachedNodesByStringCountMax,
		int $cachedNodesByStringCountExpected,
	): void
	{
		$parser = new CachedParser(
			$this->getParserStub(),
			$cachedNodesByStringCountMax,
		);

		$this->assertSame(
			$cachedNodesByStringCountMax,
			$parser->getCachedNodesByStringCountMax(),
		);

		// Add strings to cache
		for ($i = 0; $i <= $cachedNodesByStringCountMax; $i++) {
			$parser->parseString('string' . $i);
		}

		$this->assertSame(
			$cachedNodesByStringCountExpected,
			$parser->getCachedNodesByStringCount(),
		);

		$this->assertCount(
			$cachedNodesByStringCountExpected,
			$parser->getCachedNodesByString(),
		);
	}

	/**
	 * @return Generator<string, array{cachedNodesByStringCountMax: int,cachedNodesByStringCountExpected: int}>
	 */
	public static function dataParseFileClearCache(): Generator
	{
		yield 'even' => [
			'cachedNodesByStringCountMax' => 50,
			'cachedNodesByStringCountExpected' => 50,
		];

		yield 'odd' => [
			'cachedNodesByStringCountMax' => 51,
			'cachedNodesByStringCountExpected' => 51,
		];
	}

	private function getParserStub(): Parser&Stub
	{
		$mock = $this->createStub(Parser::class);

		$mock->method('parseFile')->willReturn([$this->getPhpParserNodeStub()]);
		$mock->method('parseString')->willReturn([$this->getPhpParserNodeStub()]);

		return $mock;
	}

	private function getPhpParserNodeStub(): Node&Stub
	{
		return $this->createStub(Node::class);
	}

	public function testParseTheSameFileWithDifferentMethod(): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$pathRoutingParser = new PathRoutingParser(
			$fileHelper,
			self::getContainer()->getService('currentPhpVersionRichParser'),
			self::getContainer()->getService('currentPhpVersionSimpleDirectParser'),
			self::getContainer()->getService('php8Parser'),
			null,
		);
		$parser = new CachedParser($pathRoutingParser, 500);
		$path = $fileHelper->normalizePath(__DIR__ . '/data/test.php');
		$pathRoutingParser->setAnalysedFiles([$path]);
		$contents = FileReader::read($path);
		$stmts = $parser->parseString($contents);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertNull($stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));

		$stmts = $parser->parseFile($path);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertSame(1, $stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));

		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[1]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[1]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[1]->expr->expr);
		$this->assertSame(2, $stmts[0]->stmts[1]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));

		$stmts = $parser->parseString($contents);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertSame(1, $stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));

		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[1]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[1]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[1]->expr->expr);
		$this->assertSame(2, $stmts[0]->stmts[1]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));
	}

	public function testWithExprCacheHelper(): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$pathRoutingParser = new PathRoutingParser(
			$fileHelper,
			self::getContainer()->getService('currentPhpVersionRichParser'),
			self::getContainer()->getService('currentPhpVersionSimpleDirectParser'),
			self::getContainer()->getService('php8Parser'),
			null,
		);
		$parser = new CachedParser($pathRoutingParser, 500);
		$path = $fileHelper->normalizePath(__DIR__ . '/data/parser-cache-bug.php');
		$pathRoutingParser->setAnalysedFiles([$path]);
		$contents = FileReader::read($path);
		$stmts = $parser->parseString($contents);

		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$ns = $stmts[0];

		$this->assertInstanceOf(Node\Stmt\Class_::class, $ns->stmts[1]);
		$class = $ns->stmts[1];

		$this->assertInstanceOf(Node\Stmt\Property::class, $class->stmts[0]);
		$property = $class->stmts[0];
		$this->assertInstanceOf(Node\AttributeGroup::class, $property->attrGroups[0]);
		$group = $property->attrGroups[0];
		$this->assertInstanceOf(Node\Attribute::class, $group->attrs[0]);
		$attribute = $group->attrs[0];

		$expr = $attribute->args[0]->value;
		$this->assertSame(['startLine' => 8, 'startTokenPos' => 21, 'startFilePos' => 88, 'endLine' => 8, 'endTokenPos' => 21, 'endFilePos' => 94, 'kind' => 1, 'rawValue' => "'hello'"], $expr->getAttributes());
		$exported = ExprCacheHelper::export($expr);
		$reImported = ExprCacheHelper::import($exported);
		$this->assertSame(['startLine' => 8, 'startTokenPos' => 21, 'startFilePos' => 88, 'endLine' => 8, 'endTokenPos' => 21, 'endFilePos' => 94, 'kind' => 1, 'rawValue' => "'hello'"], $reImported->getAttributes());

		$this->assertInstanceOf(Node\Stmt\Property::class, $class->stmts[1]);
		$property = $class->stmts[1];
		$this->assertInstanceOf(Node\AttributeGroup::class, $property->attrGroups[0]);
		$group = $property->attrGroups[0];
		$this->assertInstanceOf(Node\Attribute::class, $group->attrs[0]);
		$attribute = $group->attrs[0];

		$expr = $attribute->args[0]->value;
		$this->assertSame(['startLine' => 10, 'startTokenPos' => 35, 'startFilePos' => 137, 'endLine' => 10, 'endTokenPos' => 35, 'endFilePos' => 143, 'kind' => 1, 'rawValue' => "'hello'"], $expr->getAttributes());
		$exported = ExprCacheHelper::export($expr);
		unset($exported['attributes']['startLine']); // modify attributes
		$reImported = ExprCacheHelper::import($exported);
		// assert that we get back the default start-line instead of a stale cached startLine of previous same value expression
		$this->assertSame(['startLine' => 1, 'startTokenPos' => 35, 'startFilePos' => 137, 'endLine' => 10, 'endTokenPos' => 35, 'endFilePos' => 143, 'kind' => 1, 'rawValue' => "'hello'"], $reImported->getAttributes());
	}

}
