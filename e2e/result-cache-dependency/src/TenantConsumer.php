<?php

declare(strict_types=1);

function tenantCheckoutLabelLength(): int
{
	return strlen(tenantConfigValue('checkout.label'));
}
