<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5271;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class BannerPatternLayer
{

}

class Banner
{

	/** @var list<BannerPatternLayer> */
	private array $patterns = [];

	public function test(): void
	{
		$this->patterns = array_filter($this->patterns, fn($v) => $v instanceof BannerPatternLayer);
	}

	public function testClosure(): void
	{
		$this->patterns = array_filter($this->patterns, function ($v) {
			assertType('Bug5271\BannerPatternLayer', $v);
			assertNativeType('mixed', $v);
			return $v instanceof BannerPatternLayer;
		});
	}

	public function testArrow(): void
	{
		array_filter($this->patterns, fn($v) => assertNativeType('mixed', $v));
	}

}
