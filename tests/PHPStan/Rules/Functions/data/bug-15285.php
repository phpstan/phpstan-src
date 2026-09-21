<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15285CallCallables;

use Closure;
use RuntimeException;

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

}
