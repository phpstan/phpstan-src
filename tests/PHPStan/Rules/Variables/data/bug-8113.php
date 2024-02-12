<?php declare(strict_types=1);

namespace Bug8113;

use function PHPStan\Testing\assertType;

function () {
	/** @var mixed[][] $review */
	$review = array(
		'Review' => array('id' => 23,
			'User' => array(
				'first_name' => 'x',
			),
		),
		'SurveyInvitation' => array(
			'is_too_old_to_follow' => 'yes',
		),
		'User' => array(
			'first_name' => 'x',
		),
	);

	assertType('array<array>', $review);

	if (
		array_key_exists('review', $review['SurveyInvitation']) &&
		$review['SurveyInvitation']['review'] === null
	) {
		assertType("array<array>&hasOffsetValue('SurveyInvitation', array&hasOffsetValue('review', null))", $review);
		$review['Review'] = [
			'id' => null,
			'text' => null,
			'answer' => null,
		];
		assertType("non-empty-array<array>&hasOffsetValue('Review', array{id: null, text: null, answer: null})&hasOffsetValue('SurveyInvitation', array&hasOffsetValue('review', null))", $review);
		unset($review['SurveyInvitation']['review']);
		assertType("non-empty-array<array>&hasOffsetValue('Review', array<mixed~'review', mixed>)&hasOffsetValue('SurveyInvitation', array<mixed~'review', mixed>)", $review);
	}
	assertType('array<array>', $review);
	if (array_key_exists('User', $review['Review'])) {
		assertType("array<array>&hasOffsetValue('Review', array&hasOffset('User'))", $review);
		$review['User'] = $review['Review']['User'];
		assertType("hasOffsetValue('Review', array&hasOffset('User'))&hasOffsetValue('User', mixed)&non-empty-array", $review);
		unset($review['Review']['User']);
		assertType("hasOffsetValue('Review', array<mixed~'User', mixed>)&hasOffsetValue('User', array<mixed~'User', mixed>)&non-empty-array", $review);
	}
	assertType("array&hasOffsetValue('Review', array)", $review);
};
