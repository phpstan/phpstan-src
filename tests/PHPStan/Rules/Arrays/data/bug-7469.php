<?php declare(strict_types = 1);

namespace Bug7469;

function doFoo() {
	$line = file_get_contents('php://input');

	$keys = [
		'lastName',
		'firstName',
		'languages',
		'phone',
		'email',
		'voiceExample',
		'videoOnline',
		'videoTvc',
		'radio',
		'birthDate',
		'address',
		'bankAccount',
		'ic',
		'invoicingAddress',
		'invoicing', // to bool
		'note',
	];

	$data = array_combine($keys, $line);

	if ($data === false) {
		throw new \Exception('Unable to combine');
	}

	$data['languages'] = explode(',', $data['languages']);
	array_walk($data['languages'], static function (&$item) {
		$item = strtolower(trim($item));
	});

	$data['videoOnline'] = normalizePrice($data['videoOnline']);
	$data['videoTvc'] = normalizePrice($data['videoTvc']);
	$data['radio'] = normalizePrice($data['radio']);

	$data['invoicing'] = $data['invoicing'] === 'ANO';
}

function normalizePrice($value)
{
	return $value;
}
