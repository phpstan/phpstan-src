<?php declare(strict_types = 1);

namespace Bug15657Rule;

/**
 * @param array<string, string> $meta
 */
function readProps(array $meta): string
{
	// 17 entries - with 16 entries, all keys are known to exist after the loop
	$metaKeyToProp = [
		'_description' => 'description',
		'_regular_price' => 'regular_price',
		'_sale_price' => 'sale_price',
		'_date_on_sale_from' => 'date_on_sale_from',
		'_date_on_sale_to' => 'date_on_sale_to',
		'_manage_stock' => 'manage_stock',
		'_stock_status' => 'stock_status',
		'_virtual' => 'virtual',
		'_downloadable' => 'downloadable',
		'_sku' => 'sku',
		'_stock_quantity' => 'stock_quantity',
		'_weight' => 'weight',
		'_length' => 'length',
		'_width' => 'width',
		'_height' => 'height',
		'_backorders' => 'backorders',
		'_tax_class' => 'tax_class',
	];

	$props = [];
	foreach ($metaKeyToProp as $metaKey => $prop) {
		$props[$prop] = $meta[$metaKey] ?? '';
	}

	return $props['tax_class'];
}
