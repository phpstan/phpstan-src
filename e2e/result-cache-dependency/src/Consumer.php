<?php

declare(strict_types=1);

class Consumer
{
}

function checkoutLabelLength(): int
{
	return strlen(configValue('checkout.label')) + strlen(configValue('checkout.label'));
}

function profileNameLength(): int
{
	return strlen(configValue('profile.name'));
}

function defaultConnectionValueLength(): int
{
	return strlen(configuredConnectionValue('database.default'));
}
