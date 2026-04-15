<?php declare(strict_types = 1);

namespace Bug14462;

/** @return array{menu: array<non-empty-string, bool>} */
function get_config(): array {
	return ['menu' => []];
}

$config = get_config();

$data = [ ];
if ($config['menu']['notefrais']) {
	$data[] = [ 'name' => 'notefrais', 'menu' => 'notefrais_base' ];
}
if ($config['menu']['achat']) {
	$data[] = [ 'name' => 'achat', 'menu' => 'achat_base' ];
}

if ($config['menu']['vente-commande_planning'] || $config['menu']['vente-commande']) {
	$data[] = [ 'name' => 'vente' , 'menu' => 'vente_order_recent' ];
}
if ($config['menu']['vente-commande_planning']) {
	$data[] = [ 'name' => 'vente', 'menu' => 'vente_base_planned' ];
}
if ($config['menu']['vente-commande']) {
	$data[] = [ 'name' => 'vente', 'menu' => 'vente_base_com' ];
}
if ($config['menu']['carte']) {
	$data[] = [ 'name' => 'carte', 'menu' => '' ];
}
if ($config['menu']['crm']) {
	$data[] = [ 'name' => 'crm', 'menu' => 'crm_suivi' ];
}
if ($config['menu']['inventaire']) {
	$data[] = [ 'name' => 'inventaire', 'menu' => 'inventaire_base' ];
}


foreach ($data as $row) {
	$stack = [ ];
	if ($row['menu'] === 'vente_order_recent') {
		$stack[] = 'f';
	}
	else {
		$stack[] = 'g';
	}
}
