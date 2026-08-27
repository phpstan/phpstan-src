<?php

declare(strict_types=1);

function secondCheckoutLabelLength(): int
{
	return strlen(configValue('checkout.label'));
}
