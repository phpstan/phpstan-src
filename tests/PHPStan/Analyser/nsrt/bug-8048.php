<?php declare(strict_types=1);

namespace Bug8048Nsrt;

use function PHPStan\Testing\assertType;

interface CustomResponseInterface {}

class CustomResponse implements CustomResponseInterface {}

class ApiService
{
	/**
	 * @template T of CustomResponseInterface
	 *
	 * @param class-string<T>|null $responseType
	 *
	 * @return ($responseType is class-string<T> ? T : null)
	 */
	public function request(?string $responseType = null): ?CustomResponseInterface
	{
		if ($responseType === null) {
			return null;
		}

		return new CustomResponse();
	}
}

function (): void {
	assertType('null', (new ApiService())->request(null));
	assertType('Bug8048Nsrt\CustomResponse', (new ApiService())->request(CustomResponse::class));
};
