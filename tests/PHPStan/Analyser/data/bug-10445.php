<?php // lint >= 8.1

namespace Bug10445;

use function PHPStan\Testing\assertType;

enum Error {
	case A;
	case B;
	case C;
}

/**
 * @template T of array
 */
class Response
{
	/**
	 * @param ?T $data
	 * @param Error[] $errors
	 */
	public function __construct(
		public ?array $data,
		public array $errors = [],
	) {
	}

	/**
	 * @return array{
	 *   result: ?T,
	 *   errors?: string[],
	 * }
	 */
	public function format(): array
	{
		$output = [
			'result' => $this->data,
		];
		assertType('array{result: T of array (class Bug10445\Response, argument)|null}', $output);
		if (count($this->errors) > 0) {
			$output['errors'] = array_map(fn ($e) => $e->name, $this->errors);
			assertType("array{result: T of array (class Bug10445\Response, argument)|null, errors: non-empty-array<'A'|'B'|'C'>}", $output);
		} else {
			assertType('array{result: T of array (class Bug10445\Response, argument)|null}', $output);
		}
		assertType("array{result: T of array (class Bug10445\Response, argument)|null, errors?: non-empty-array<'A'|'B'|'C'>}", $output);
		return $output;
	}
}
