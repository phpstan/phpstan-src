<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
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

}
