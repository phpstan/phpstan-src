<?php

namespace LdapExopPasswd;

class Foo
{

	/**
	 * @param resource $r
	 */
	public function doFoo($r): void
	{
		ldap_exop_passwd($r);
	}

}
