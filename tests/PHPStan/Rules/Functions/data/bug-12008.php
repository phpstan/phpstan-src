<?php declare(strict_types = 1);

namespace Bug12008;

interface ProductOverview {
	public function getId(): ?int;
}

/**
 * @template T
 */
readonly class Pagination
{
	/**
	 * @param iterable<T> $records
	 */
	public function __construct(
		public iterable $records,
	) {
	}
}

class HelloWorld
{
	private function respondToApiRequest(Closure|null $data): never {
		exit;
	}

	/**
	 * @param list<ProductOverview> $products
	 */
	public function run(array $products): never {
		$this->respondToApiRequest(function () use ($products) {
			return new Pagination(array_map(
				fn (ProductOverview $product) => [
					'id' => $product->getId(),
				],
				$products,
			));
		});
	}
}
