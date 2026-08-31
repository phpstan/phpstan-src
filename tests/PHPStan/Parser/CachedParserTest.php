<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PHPStan\BetterReflection\Reflection\ExprCacheHelper;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use function file_put_contents;
use function sprintf;
use function str_repeat;
use function sys_get_temp_dir;
use function time;
use function touch;
use function uniqid;
use function unlink;

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
			1048576,
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

	public function testSourceBytesLimitEvictsAboveFloor(): void
	{
		// byte capacity (5000 / 100 = 50 entries) binds before the count limit (500)
		$parser = new CachedParser($this->getParserStub(), 500, 5000);

		for ($i = 0; $i < 100; $i++) {
			$parser->parseString(sprintf('%0100d', $i));
		}

		$this->assertSame(50, $parser->getCachedNodesByStringCount());
	}

	public function testSourceBytesLimitNeverEvictsBelowFloor(): void
	{
		// byte capacity (1000 / 100 = 10 entries) is below the floor of 32,
		// so the floor wins and the cache keeps growing to 32 + 1 entries
		$parser = new CachedParser($this->getParserStub(), 500, 1000);

		for ($i = 0; $i < 100; $i++) {
			$parser->parseString(sprintf('%0100d', $i));
		}

		$this->assertSame(33, $parser->getCachedNodesByStringCount());
	}

	public function testSourceLargerThanBytesLimitDoesNotFlushCache(): void
	{
		$parser = new CachedParser($this->getParserStub(), 500, 5000);

		for ($i = 0; $i < 20; $i++) {
			$parser->parseString(sprintf('%0100d', $i));
		}
		$this->assertSame(20, $parser->getCachedNodesByStringCount());

		// an entry bigger than the whole byte limit must not evict the
		// resident entries (count is below the floor) and is cached itself
		$parser->parseString(sprintf('%010000d', 0));
		$this->assertSame(21, $parser->getCachedNodesByStringCount());
	}

	public function testConstructionWithoutSourceBytesMaxStaysBackwardCompatible(): void
	{
		// third-party extensions (e.g. Larastan's migrationsParser service)
		// instantiate CachedParser without the $cachedSourceBytesMax argument
		$parser = new CachedParser($this->getParserStub(), 500);

		for ($i = 0; $i < 100; $i++) {
			$parser->parseString(sprintf('%0100d', $i));
		}

		$this->assertSame(100, $parser->getCachedNodesByStringCount());
	}

	public function testSourceBytesLimitZeroMeansUnlimited(): void
	{
		$parser = new CachedParser($this->getParserStub(), 500, 0);

		for ($i = 0; $i < 100; $i++) {
			$parser->parseString(sprintf('%0100d', $i));
		}

		$this->assertSame(100, $parser->getCachedNodesByStringCount());
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
		$parser = new CachedParser($pathRoutingParser, 500, 4194304);
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

	public function testParseFileBeforeAnalysedFilesAreSet(): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$pathRoutingParser = new PathRoutingParser(
			$fileHelper,
			self::getContainer()->getService('currentPhpVersionRichParser'),
			self::getContainer()->getService('currentPhpVersionSimpleDirectParser'),
			self::getContainer()->getService('php8Parser'),
			null,
		);
		$parser = new CachedParser($pathRoutingParser, 500, 4194304);
		$path = $fileHelper->normalizePath(__DIR__ . '/data/test.php');

		$stmts = $parser->parseFile($path);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertNull($stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));

		$pathRoutingParser->setAnalysedFiles([$path]);

		$stmts = $parser->parseFile($path);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertSame(1, $stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));
	}

	public function testParseStringEntryIsNotUpgradedBeforeAnalysedFilesAreSet(): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$pathRoutingParser = new PathRoutingParser(
			$fileHelper,
			self::getContainer()->getService('currentPhpVersionRichParser'),
			self::getContainer()->getService('currentPhpVersionSimpleDirectParser'),
			self::getContainer()->getService('php8Parser'),
			null,
		);
		$parser = new CachedParser($pathRoutingParser, 500, 4194304);
		$path = $fileHelper->normalizePath(__DIR__ . '/data/test.php');

		$stringStmts = $parser->parseString(FileReader::read($path));
		$stmts = $parser->parseFile($path);
		$this->assertSame($stringStmts, $stmts);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertNull($stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));

		$pathRoutingParser->setAnalysedFiles([$path]);

		$stmts = $parser->parseFile($path);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Node\Stmt\Expression::class, $stmts[0]->stmts[0]);
		$this->assertInstanceOf(Node\Expr\Assign::class, $stmts[0]->stmts[0]->expr);
		$this->assertInstanceOf(Node\Expr\New_::class, $stmts[0]->stmts[0]->expr->expr);
		$this->assertSame(1, $stmts[0]->stmts[0]->expr->expr->class->getAttribute(AnonymousClassVisitor::ATTRIBUTE_LINE_INDEX));
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
		$parser = new CachedParser($pathRoutingParser, 500, 4194304);
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
		$group = $property->attrGroups[0];
		$attribute = $group->attrs[0];

		$expr = $attribute->args[0]->value;
		$this->assertSame(['startLine' => 8, 'startTokenPos' => 21, 'startFilePos' => 88, 'endLine' => 8, 'endTokenPos' => 21, 'endFilePos' => 94, 'kind' => 1, 'rawValue' => "'hello'"], $expr->getAttributes());
		$exported = ExprCacheHelper::export($expr);
		$reImported = ExprCacheHelper::import($exported);
		$this->assertSame(['startLine' => 8, 'startTokenPos' => 21, 'startFilePos' => 88, 'endLine' => 8, 'endTokenPos' => 21, 'endFilePos' => 94, 'kind' => 1, 'rawValue' => "'hello'"], $reImported->getAttributes());

		$this->assertInstanceOf(Node\Stmt\Property::class, $class->stmts[1]);
		$property = $class->stmts[1];
		$group = $property->attrGroups[0];
		$attribute = $group->attrs[0];

		$expr = $attribute->args[0]->value;
		$this->assertSame(['startLine' => 10, 'startTokenPos' => 35, 'startFilePos' => 137, 'endLine' => 10, 'endTokenPos' => 35, 'endFilePos' => 143, 'kind' => 1, 'rawValue' => "'hello'"], $expr->getAttributes());
		$exported = ExprCacheHelper::export($expr);
		unset($exported['attributes']['startLine']); // modify attributes
		$reImported = ExprCacheHelper::import($exported);
		// assert that we get back the default start-line instead of a stale cached startLine of previous same value expression
		$this->assertSame(['startLine' => 1, 'startTokenPos' => 35, 'startFilePos' => 137, 'endLine' => 10, 'endTokenPos' => 35, 'endFilePos' => 143, 'kind' => 1, 'rawValue' => "'hello'"], $reImported->getAttributes());
	}

	public function testParseFileSkipsReadingUnchangedFileAndRereadsAfterChange(): void
	{
		$parser = new CachedParser($this->getContentEchoingParserStub(), 500);
		$path = sys_get_temp_dir() . '/phpstan-cached-parser-' . uniqid() . '.php';
		$baseTime = time() - 10;

		try {
			file_put_contents($path, 'contents A');
			touch($path, $baseTime);
			$this->assertSame('contents A', $parser->parseFile($path)[0]->getAttribute('content'));

			// Same-length contents change with an unchanged mtime is not detectable
			// by the [mtime, size] key: the memoized contents are returned without re-reading.
			file_put_contents($path, 'contents B');
			touch($path, $baseTime);
			$this->assertSame('contents A', $parser->parseFile($path)[0]->getAttribute('content'));

			// A size change invalidates the memo even when the mtime is unchanged.
			file_put_contents($path, 'contents B longer');
			touch($path, $baseTime);
			$this->assertSame('contents B longer', $parser->parseFile($path)[0]->getAttribute('content'));

			// A newer mtime invalidates the memo, so the file is read again.
			file_put_contents($path, 'contents C longer');
			touch($path, $baseTime + 10);
			$this->assertSame('contents C longer', $parser->parseFile($path)[0]->getAttribute('content'));
		} finally {
			@unlink($path);
		}
	}

	public function testFileContentsMemoIsBoundedByTotalSourceBytes(): void
	{
		$parser = new CachedParser($this->getContentEchoingParserStub(), 500);
		$baseTime = time() - 10;
		$bigA = sys_get_temp_dir() . '/phpstan-cached-parser-a-' . uniqid() . '.php';
		$bigB = sys_get_temp_dir() . '/phpstan-cached-parser-b-' . uniqid() . '.php';
		$huge = sys_get_temp_dir() . '/phpstan-cached-parser-h-' . uniqid() . '.php';

		try {
			// A file larger than the memo limit is never memoized: a content change
			// with an unchanged mtime is still picked up because the file is re-read.
			file_put_contents($huge, 'huge first ' . str_repeat('x', 600_000));
			touch($huge, $baseTime);
			$this->assertStringStartsWith('huge first', $parser->parseFile($huge)[0]->getAttribute('content'));
			file_put_contents($huge, 'huge second ' . str_repeat('x', 600_000));
			touch($huge, $baseTime);
			$this->assertStringStartsWith('huge second', $parser->parseFile($huge)[0]->getAttribute('content'));

			// Two ~300 KB files exceed the limit together: memoizing the second
			// evicts the first (least recently used), so a content change to the
			// first with an unchanged mtime is picked up by the forced re-read.
			file_put_contents($bigA, 'big-a first ' . str_repeat('a', 300_000));
			touch($bigA, $baseTime);
			$this->assertStringStartsWith('big-a first', $parser->parseFile($bigA)[0]->getAttribute('content'));

			file_put_contents($bigB, 'big-b ' . str_repeat('b', 300_000));
			touch($bigB, $baseTime);
			$this->assertStringStartsWith('big-b', $parser->parseFile($bigB)[0]->getAttribute('content'));

			file_put_contents($bigA, 'big-a second ' . str_repeat('a', 300_000));
			touch($bigA, $baseTime);
			$this->assertStringStartsWith('big-a second', $parser->parseFile($bigA)[0]->getAttribute('content'));
		} finally {
			@unlink($bigA);
			@unlink($bigB);
			@unlink($huge);
		}
	}

	private function getContentEchoingParserStub(): Parser&Stub
	{
		$mock = $this->createStub(Parser::class);
		$mock->method('parseFile')->willReturnCallback(
			static fn (string $file): array => [new Nop(['content' => FileReader::read($file)])],
		);

		return $mock;
	}

}
