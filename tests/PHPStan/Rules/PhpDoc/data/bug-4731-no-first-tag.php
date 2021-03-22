<?php declare(strict_types = 1);

namespace Bug4731NoFirstTag;

/**
 * @see https://github.com/barbushin/php-imap
 *
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 *
 *
 *
 * @psalm-type PARTSTRUCTURE = object{
 *  id?:string,
 *  encoding:int|mixed,
 *  partStructure:object[],
 *  parameters:PARTSTRUCTURE_PARAM[],
 *  dparameters:object{attribute:string, value:string}[],
 *  parts:array<int, object{disposition?:string}>,
 *  type:int,
 *  subtype:string
 * }
 * @psalm-type HOSTNAMEANDADDRESS_ENTRY = object{host?:string, personal?:string, mailbox:string}
 * @psalm-type HOSTNAMEANDADDRESS = array{0:HOSTNAMEANDADDRESS_ENTRY, 1?:HOSTNAMEANDADDRESS_ENTRY}
 * @psalm-type COMPOSE_ENVELOPE = array{
 *	subject?:string
 * }
 * @psalm-type COMPOSE_BODY = list<array{
 *	type?:int,
 *	encoding?:int,
 *	charset?:string,
 *	subtype?:string,
 *	description?:string,
 *	disposition?:array{filename:string}
 * }>
 *
 * @todo see @todo of Imap::mail_compose()
 */
class HelloWorld
{
	public function sayHello(\DateTimeImmutable $date): void
	{
		echo 'Hello, ' . $date->format('j. n. Y');
	}
}
