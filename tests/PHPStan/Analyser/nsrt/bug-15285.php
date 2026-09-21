<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15285;

use Closure;
use Generator;
use LogicException;
use RuntimeException;
use function PHPStan\Testing\assertType;

final class CartController
{

	public function redirect(string $destination): never
	{
		throw new RuntimeException($destination);
	}

	/**
	 * @param Closure(string, (Closure(): never)|null=): never $errorCallback
	 */
	public function addToCart(bool $isAvailable, Closure $errorCallback): void
	{
		if ($isAvailable) {
			return;
		}

		$errorCallback(
			'This offer is no longer available.',
			fn (): null => $this->redirect('/product'),
		);
	}

	public function dump(): void
	{
		assertType('Closure(): never', fn () => $this->redirect('/product'));
		assertType('Closure(): never', fn (): null => $this->redirect('/product'));
		assertType('Closure(): never', fn (): int => throw new LogicException());
		assertType('Closure(): never', fn (): ?int => throw new LogicException());

		assertType('Closure(): never', function () {
			$this->redirect('/product');
		});
		assertType('Closure(): never', function (): null {
			$this->redirect('/product');
		});
		assertType('Closure(): never', function (): ?int {
			throw new LogicException();
		});

		assertType('static-Closure(): never', static fn (): ?int => throw new LogicException());
		assertType('static-Closure(): never', static function (): null {
			throw new LogicException();
		});

		assertType('Closure(): never', function (): ?Generator {
			throw new LogicException();
		});
		assertType('Closure(): Generator<int, 1, mixed, void>', function (): ?Generator {
			yield 1;
		});
	}

}

/**
 * @param list<never> $emptyList
 */
function neverCallableParameters(array $emptyList): void
{
	array_map(function (?int $i): void {
		assertType('never', $i);
	}, $emptyList);

	array_map(fn (?int $i) => assertType('never', $i), $emptyList);

	array_map(function (null $i): void {
		assertType('never', $i);
	}, $emptyList);
}

/**
 * @param callable(never...): void $cb
 */
function neverVariadicCallable(callable $cb): void
{
}

function neverVariadicCallableParameters(): void
{
	neverVariadicCallable(function (?int $a, ?string $b): void {
		assertType('never', $a);
		assertType('never', $b);
	});

	neverVariadicCallable(fn (?int $a) => assertType('never', $a));
}
