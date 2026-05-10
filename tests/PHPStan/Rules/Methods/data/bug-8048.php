<?php declare(strict_types=1);

namespace Bug8048;

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
	(new ApiService())->request(null);
	(new ApiService())->request(CustomResponse::class);
	$x = rand(0, 1) ? CustomResponse::class : null;
	(new ApiService())->request($x);
};
