<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9204;

use function PHPStan\Testing\assertType;

function (): void {
	$goodObject = (new class() {
		/**
		 * @var array<string, mixed>
		 */
		private array $properties;

		/**
		 * @param array<string, mixed> $properties
		 */
		public function __construct(array $properties = [])
		{
			$this->properties = $properties;
		}
		public function __isset(string $offset): bool
		{
			return isset($this->properties[$offset]);
		}
		public function __get(string $offset): mixed
		{
			return $this->properties[$offset] ?? null;
		}
	});
	$objects = [
		new $goodObject(['id' => 42]),
		new $goodObject(['id' => 1337]),
	];

	$columns = array_column($objects, 'id');
	assertType('list', $columns);
};
