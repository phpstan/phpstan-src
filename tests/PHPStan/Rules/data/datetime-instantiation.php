<?php
namespace DateTimeInstantiation;
new \DateTime('2020.11.17');

new \DateTimeImmutable('asdfasdf');

new \DateTime('');

$test = '2020.11.17';
new \DateTimeImmutable($test);

/**
 * @param '2020.11.18' $date2
 */
function foo(string $date, string $date2): void {
	new \DateTime($date);
	new \DateTimeImmutable($date2);
}

new \DateTime('2020-04-31');

new \dateTime('2020.11.17');
new \dateTimeImmutablE('2020.11.17');
