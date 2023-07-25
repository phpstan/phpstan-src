<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Stmt\Namespace_;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CachedParserTest extends PHPStanTestCase
{

	/**
	 * @dataProvider dataParseFileClearCache
	 * @param int $cachedNodesByStringCountMax
	 * @param int $cachedNodesByStringCountExpected
	 */
	public function testParseFileClearCache(
		int $cachedNodesByStringCountMax,
		int $cachedNodesByStringCountExpected
	): void
	{
		$parser = new CachedParser(
			$this->getParserMock(),
			$cachedNodesByStringCountMax
		);

		$this->assertEquals(
			$cachedNodesByStringCountMax,
			$parser->getCachedNodesByStringCountMax()
		);

		// Add strings to cache
		for ($i = 0; $i <= $cachedNodesByStringCountMax; $i++) {
			$parser->parseString('string' . $i);
		}

		$this->assertEquals(
			$cachedNodesByStringCountExpected,
			$parser->getCachedNodesByStringCount()
		);

		$this->assertCount(
			$cachedNodesByStringCountExpected,
			$parser->getCachedNodesByString()
		);
	}

	public function dataParseFileClearCache(): \Generator
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

	private function getParserMock(): Parser&MockObject
	{
		$mock = $this->createMock(Parser::class);

		$mock->method('parseFile')->willReturn([$this->getPhpParserNodeMock()]);
		$mock->method('parseString')->willReturn([$this->getPhpParserNodeMock()]);

		return $mock;
	}

	private function getPhpParserNodeMock(): \PhpParser\Node&MockObject
	{
		return $this->createMock(\PhpParser\Node::class);
	}

	public function testParseTheSameFileWithDifferentMethod(): void
	{
		$fileHelper = self::getContainer()->getByType(FileHelper::class);
		$pathRoutingParser = new PathRoutingParser(
			$fileHelper,
			self::getContainer()->getService('currentPhpVersionRichParser'),
			self::getContainer()->getService('currentPhpVersionSimpleDirectParser'),
			self::getContainer()->getService('php8Parser'),
		);
		$parser = new CachedParser($pathRoutingParser, 500);
		$path = $fileHelper->normalizePath(__DIR__ . '/data/test.php');
		$pathRoutingParser->setAnalysedFiles([$path]);
		$contents = FileReader::read($path);
		$stmts = $parser->parseString($contents);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertNull($stmts[0]->stmts[0]->getAttribute('parent'));

		$stmts = $parser->parseFile($path);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]->stmts[0]->getAttribute('parent'));

		$stmts = $parser->parseString($contents);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]);
		$this->assertInstanceOf(Namespace_::class, $stmts[0]->stmts[0]->getAttribute('parent'));
	}

}
