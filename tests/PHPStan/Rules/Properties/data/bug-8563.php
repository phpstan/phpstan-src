<?php

namespace Bug8563;

class BankAccount {

	readonly string $bic;
	readonly string $iban;
	readonly string $label;

	function __construct(object $data = new \stdClass) {
		$this->bic = $data->bic ?? "";
		$this->iban = $data->iban ?? "";
		$this->label = $data->label ?? "";
	}
}
