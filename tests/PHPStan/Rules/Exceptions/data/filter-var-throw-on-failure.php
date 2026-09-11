<?php // lint >= 8.5

declare(strict_types = 1);

namespace FilterVarThrowOnFailure;

function validateMac(mixed $value): void
{
	try {
		filter_var($value, FILTER_VALIDATE_MAC, FILTER_THROW_ON_FAILURE);
	} catch (\Filter\FilterFailedException) {
	}
}

function validateMacWithFlagsArray(mixed $value): void
{
	try {
		filter_var($value, FILTER_VALIDATE_MAC, ['flags' => FILTER_THROW_ON_FAILURE]);
	} catch (\Filter\FilterFailedException) {
	}
}

function validateInt(mixed $value): void
{
	try {
		filter_var($value, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
	} catch (\Filter\FilterFailedException) {
	}
}

function validateWithNamedArguments(mixed $value): void
{
	try {
		filter_var(options: FILTER_THROW_ON_FAILURE, value: $value, filter: FILTER_VALIDATE_INT);
	} catch (\Filter\FilterFailedException) {
	}
}

function validateInput(): void
{
	try {
		filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
	} catch (\Filter\FilterFailedException) {
	}
}

function validateInputWithNamedArguments(): void
{
	try {
		filter_input(options: FILTER_THROW_ON_FAILURE, type: INPUT_GET, var_name: 'foo', filter: FILTER_VALIDATE_INT);
	} catch (\Filter\FilterFailedException) {
	}
}

/**
 * @param array<string, mixed> $data
 */
function validateVarArray(array $data): void
{
	try {
		filter_var_array($data, ['foo' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_THROW_ON_FAILURE]]);
	} catch (\Filter\FilterFailedException) {
	}
}

function validateInputArray(): void
{
	try {
		filter_input_array(INPUT_GET, ['foo' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_THROW_ON_FAILURE]]);
	} catch (\Filter\FilterFailedException) {
	}
}
