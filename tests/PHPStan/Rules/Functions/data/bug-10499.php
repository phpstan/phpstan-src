<?php

namespace Bug10499;

$username = $currpass = $newpass = $error = '';

if (pam_auth($username, $currpass, $error, false)) {
	if (pam_chpass($username, $currpass, $newpass)) {
		//
	}
}
