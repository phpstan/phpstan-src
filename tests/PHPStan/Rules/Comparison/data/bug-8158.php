<?php

namespace Bug8158;

function phone_number_starts_with_zero(string $phone_number): bool
{
	return $phone_number && $phone_number[0] === '0';
}
