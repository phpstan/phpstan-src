<?php

namespace Bug8048;

interface CustomResponseInterface
{
}

final class CustomResponse implements CustomResponseInterface
{
}

final class ApiService
{
	/**
	 * @template T of CustomResponseInterface
	 * @param class-string<T> $class
	 * @return T|null
	 */
	public function request(string $class): ?CustomResponseInterface
	{
		return new CustomResponse();
	}
}

final class Consumer
{
	public function test(): void
	{
		$apiService = new ApiService();
		$result = $apiService->request(CustomResponse::class);
	}
}
