<?php declare(strict_types = 1);

namespace PHPStan\Php;

use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use function iterator_to_array;
use const PHP_VERSION_ID;

class PhpMinorVersionIteratorTest extends TestCase
{

	public function testIterator(): void
	{
		$versionIterator = new PhpMinorVersionIterator(new PhpVersion(50104), new PhpVersion(80305));
		$it = $versionIterator->getIterator();
		$arr = iterator_to_array($it);

		$this->assertCount(16, $arr);
		$this->assertSame(50104, $arr[0]->getVersionId());
		$this->assertSame(50200, $arr[1]->getVersionId());
		$this->assertSame(50300, $arr[2]->getVersionId());
		$this->assertSame(50400, $arr[3]->getVersionId());
		$this->assertSame(50500, $arr[4]->getVersionId());
		$this->assertSame(50600, $arr[5]->getVersionId());
		$this->assertSame(70000, $arr[6]->getVersionId());
		$this->assertSame(70100, $arr[7]->getVersionId());
		$this->assertSame(70200, $arr[8]->getVersionId());
		$this->assertSame(70300, $arr[9]->getVersionId());
		$this->assertSame(70400, $arr[10]->getVersionId());
		$this->assertSame(80000, $arr[11]->getVersionId());
		$this->assertSame(80100, $arr[12]->getVersionId());
		$this->assertSame(80200, $arr[13]->getVersionId());
		$this->assertSame(80300, $arr[14]->getVersionId());
		$this->assertSame(80305, $arr[15]->getVersionId());
	}

	public function testIteratorWith(): void
	{
		$versionIterator = new PhpMinorVersionIterator(new PhpVersion(70100), new PhpVersion(70300));
		$it = $versionIterator->getIterator();
		$arr = iterator_to_array($it);

		$this->assertCount(3, $arr);
		$this->assertSame(70100, $arr[0]->getVersionId());
		$this->assertSame(70200, $arr[1]->getVersionId());
		$this->assertSame(70300, $arr[2]->getVersionId());
	}

	// test which is expected to fail, when PHP9 is supported
	public function testPhp9Overflow(): void
	{
		$this->expectException(ShouldNotHappenException::class);

		new PhpMinorVersionIterator(new PhpVersion(80900), new PhpVersion(90200));
	}

	public function testThrowsBeforePhp5(): void
	{
		$this->expectException(ShouldNotHappenException::class);

		new PhpMinorVersionIterator(new PhpVersion(40100), new PhpVersion(70300));
	}

	public function testSupportsCIVersionId(): void
	{
		$versionIterator = new PhpMinorVersionIterator(new PhpVersion(PHP_VERSION_ID), new PhpVersion(PHP_VERSION_ID));
		$it = $versionIterator->getIterator();
		$arr = iterator_to_array($it);

		$this->assertCount(1, $arr);
		$this->assertSame(PHP_VERSION_ID, $arr[0]->getVersionId());
	}

}
