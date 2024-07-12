<?php

namespace UselessFunctionReturn;

class FooClass
{
	public function fine(bool $bool): void
	{
		error_log(
			"Email-Template couldn't be found by parameters:" . print_r([
				'template' => 1,
				'spracheid' => 2,
			], true)
		);

		$x = print_r([
			'template' => 1,
			'spracheid' => 2,
		], true);

		print_r([
			'template' => 1,
			'spracheid' => 2,
		]);

		error_log(
			"Email-Template couldn't be found by parameters:" . print_r([
				'template' => 1,
				'spracheid' => 2,
			], $bool)
		);

		print_r([
			'template' => 1,
			'spracheid' => 2,
		], $bool);

		$x = print_r([
			'template' => 1,
			'spracheid' => 2,
		], $bool);
	}

	public function missesReturn(): void
	{
		error_log(
			"Email-Template couldn't be found by parameters:" . print_r([
				'template' => 1,
				'spracheid' => 2,
			])
		);
	}

	public function missesReturnVarDump(): string
	{
		return "Email-Template couldn't be found by parameters:" . var_export([
				'template' => 1,
				'spracheid' => 2,
			]);
	}

	public function explicitNoReturn(): void
	{
		error_log("Email-Template couldn't be found by parameters:" . print_r([
				'template' => 1,
				'spracheid' => 2,
			], false)
		);
	}

	public function showSource(string $s): void
	{
		error_log("Email-Template couldn't be found by parameters:" . show_source($s));
	}

}
