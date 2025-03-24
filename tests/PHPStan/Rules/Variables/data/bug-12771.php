<?php declare(strict_types=1);

namespace Bug12771;

final class ErrorReportController
{

	public function __invoke(ServerRequest $request)
	{
		if (
			isset($_SESSION['prev_error_subm_time'], $_SESSION['error_subm_count'])
			&& $_SESSION['error_subm_count'] >= 3
			&& ($_SESSION['prev_error_subm_time'] - time()) <= 3000
		) {
			$_SESSION['error_subm_count'] = 0;
			$_SESSION['prev_errors'] = '';
		} else {
			$_SESSION['prev_error_subm_time'] = time();
			$_SESSION['error_subm_count'] = isset($_SESSION['error_subm_count'])
				? $_SESSION['error_subm_count'] + 1
				: 0;
		}

	}
}
