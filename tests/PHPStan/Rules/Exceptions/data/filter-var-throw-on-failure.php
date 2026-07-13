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
