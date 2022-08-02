<?php declare(strict_types = 1);

namespace Bug7469;

use function PHPStan\Testing\assertType;

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

	$data['languages'] = explode(',', $data['languages']);
	array_walk($data['languages'], static function (&$item) {
		$item = strtolower(trim($item));
	});
	assertType("non-empty-array<'address'|'bankAccount'|'birthDate'|'email'|'firstName'|'ic'|'invoicing'|'invoicingAddress'|'languages'|'lastName'|'note'|'phone'|'radio'|'videoOnline'|'videoTvc'|'voiceExample', mixed>&hasOffsetValue('languages', non-empty-array<int, string>)", $data);

	$data['videoOnline'] = normalizePrice($data['videoOnline']);
	$data['videoTvc'] = normalizePrice($data['videoTvc']);
	$data['radio'] = normalizePrice($data['radio']);

	$data['invoicing'] = $data['invoicing'] === 'ANO';
	assertType("non-empty-array<'address'|'bankAccount'|'birthDate'|'email'|'firstName'|'ic'|'invoicing'|'invoicingAddress'|'languages'|'lastName'|'note'|'phone'|'radio'|'videoOnline'|'videoTvc'|'voiceExample', mixed>&hasOffsetValue('invoicing', bool)&hasOffsetValue('languages', non-empty-array<int, string>)&hasOffsetValue('radio', mixed)&hasOffsetValue('videoOnline', mixed)&hasOffsetValue('videoTvc', mixed)", $data);
}

function normalizePrice($value)
{
	return $value;
}
