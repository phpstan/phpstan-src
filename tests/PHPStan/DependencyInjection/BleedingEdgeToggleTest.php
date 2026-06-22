<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Override;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class BleedingEdgeToggleTest extends TestCase
{

	private bool $backup;

	#[Override]
	protected function setUp(): void
	{
		$this->backup = BleedingEdgeToggle::isBleedingEdge();
	}

	#[Override]
	protected function tearDown(): void
	{
		BleedingEdgeToggle::setBleedingEdge($this->backup);
	}

	public function testTogglesDuringCallbackAndRestoresAfterwards(): void
	{
		BleedingEdgeToggle::setBleedingEdge(false);

		$observed = BleedingEdgeToggle::withBleedingEdge(true, static fn (): bool => BleedingEdgeToggle::isBleedingEdge());

		$this->assertTrue($observed);
		$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
	}

	public function testRestoresPreviousValueWhenAlreadyEnabled(): void
	{
		BleedingEdgeToggle::setBleedingEdge(true);

		$observed = BleedingEdgeToggle::withBleedingEdge(false, static fn (): bool => BleedingEdgeToggle::isBleedingEdge());

		$this->assertFalse($observed);
		$this->assertTrue(BleedingEdgeToggle::isBleedingEdge());
	}

	public function testReturnsCallbackResult(): void
	{
		$result = BleedingEdgeToggle::withBleedingEdge(true, fn (): string => $this->makeValue());

		$this->assertSame('value', $result);
	}

	public function testRestoresPreviousValueWhenCallbackThrows(): void
	{
		BleedingEdgeToggle::setBleedingEdge(false);

		$thrown = false;
		try {
			BleedingEdgeToggle::withBleedingEdge(true, static function (): void {
				throw new RuntimeException('boom');
			});
		} catch (Throwable $e) {
			$thrown = $e instanceof RuntimeException && $e->getMessage() === 'boom';
		}

		$this->assertTrue($thrown);
		$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
	}

	public function testThrowsAndRestoresWhenCallbackYields(): void
	{
		BleedingEdgeToggle::setBleedingEdge(false);

		$thrown = false;
		try {
			BleedingEdgeToggle::withBleedingEdge(true, static function () {
				yield 1;
			});
		} catch (ShouldNotHappenException) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
	}

	public function testProducesDataSetsWhileToggleIsSet(): void
	{
		BleedingEdgeToggle::setBleedingEdge(false);

		$dataSets = BleedingEdgeToggle::withBleedingEdge(true, static fn (): array => [
			BleedingEdgeToggle::isBleedingEdge(),
			BleedingEdgeToggle::isBleedingEdge(),
		]);

		$this->assertSame([true, true], $dataSets);
		$this->assertFalse(BleedingEdgeToggle::isBleedingEdge());
	}

	private function makeValue(): string
	{
		return 'value';
	}

}
