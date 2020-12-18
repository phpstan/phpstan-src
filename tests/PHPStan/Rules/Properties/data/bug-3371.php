<?php

namespace Bug3371;

class HelloWorld
{

	/**
	 * returns last error message or false if no errors occurred
	 *
	 * @param \mysqli|null|false $mysqli mysql link
	 *
	 * @return string|bool error or false
	 */
	public function getError($mysqli)
	{
		$error_number = 0;
		$mysqliValid = $mysqli !== null && $mysqli !== false;

		if ($mysqliValid) {
			$error_number = $mysqli->errno;
			$error_message = $mysqli->error;
		}
		if ($mysqliValid && $error_number === 0) {
			$error_number = (int) $mysqli->connect_errno;
			$error_message = $mysqli->connect_error;
		}


		if ($mysqli !== null && $mysqli !== false) {
			$error_number = $mysqli->errno;
			$error_message = $mysqli->error;
		}
		if ($mysqli !== null && $mysqli !== false && $error_number === 0) {
			$error_number = (int) $mysqli->connect_errno;
			$error_message = $mysqli->connect_error;
		}


		return 'string';
	}
}
