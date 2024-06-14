<?php

namespace Bug4209Two;

use function PHPStan\Testing\assertType;

class Customer
{
	public function getName(): string { return 'customer'; }
}

/**
 * @template T
 */
interface Link {
	/**
	 * @return T
	 */
	public function getItem();
}

/**
 * @implements Link<Customer>
 */
class CustomerLink implements Link
{
	/**
	 * @var Customer
	 */
	public $item;

	/**
	 * @param Customer $item
	 */
	public function __construct($item) {
		$this->item = $item;
	}

	/**
	 * @return Customer
	 */
	public function getItem()
	{
		return $this->item;
	}
}

/**
 * @return CustomerLink[]
 */
function get_links(): array {
	return [new CustomerLink(new Customer())];
}

/**
 * @template T
 * @param Link<T>[] $links
 * @return T
 */
function process_customers(array $links) {
	// no-op
}

class Runner {
	public function run(): void
	{
		assertType('Bug4209Two\Customer', process_customers(get_links()));
	}
}
