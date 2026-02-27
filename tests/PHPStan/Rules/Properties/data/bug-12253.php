<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug12253;

use stdClass;

class Payload
{
	/** @var array<array<string, mixed>> */
	private(set) readonly array $validation;

	/** @var array<string, string> */
	private array $ids = [];

	public function __construct(private readonly stdClass $payload)
	{
		$this->parseValidation();
	}

	private function parseValidation(): void
	{
		$validations = [];

		foreach ($this->payload->validation as $key => $validation) {
			$validations[] = [
				'id' => $key,
				'field_id' => $this->ids[$validation->field_id],
				'rule' => $validation->rule,
				'value' => $this->validationValue($validation->value),
				'message' => $validation->message,
			];
		}

		$this->validation = $validations;
	}

	private function validationValue(mixed $value): mixed
	{
		if (is_null($value)) {
			return null;
		}

		return $this->ids[$value] ?? $value;
	}
}

class PayloadWithoutAsymmetricVisibility
{
	/** @var array<array<string, mixed>> */
	private readonly array $validation;

	/** @var array<string, string> */
	private array $ids = [];

	public function __construct(private readonly stdClass $payload)
	{
		$this->parseValidation();
	}

	private function parseValidation(): void
	{
		$validations = [];

		foreach ($this->payload->validation as $key => $validation) {
			$validations[] = [
				'id' => $key,
				'field_id' => $this->ids[$validation->field_id],
				'rule' => $validation->rule,
				'value' => $this->validationValue($validation->value),
				'message' => $validation->message,
			];
		}

		$this->validation = $validations;
	}

	private function validationValue(mixed $value): mixed
	{
		if (is_null($value)) {
			return null;
		}

		return $this->ids[$value] ?? $value;
	}
}
