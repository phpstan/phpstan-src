<?php

namespace BenchComplexUnion;

class WP_User {
	/** @return array<string, mixed> */
	public function to_array(): array { return []; }
}

/** @var array<string, list<callable>> */
$hooks = [];

/** @return mixed */
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
	global $hooks;
	if (isset($hooks[$hook])) { foreach ($hooks[$hook] as $cb) { $value = $cb($value); } }
	return $value;
}

function do_action(string $hook, mixed ...$args): void {
	global $hooks;
	if (isset($hooks[$hook])) { foreach ($hooks[$hook] as $cb) { $cb(...$args); } }
}

/** @param array|object $data */
function insert($data): int {
	if ($data instanceof \stdClass) { $data = get_object_vars($data); }
	elseif ($data instanceof WP_User) { $data = $data->to_array(); }

	if (!empty($data["ID"])) {
		$id = (int)$data["ID"];
		$update = true;
		$old_1 = $data["old_1"] ?? "";
		$old_2 = $data["old_2"] ?? "";
		$old_3 = $data["old_3"] ?? "";
		$old_4 = $data["old_4"] ?? "";
		$old_5 = $data["old_5"] ?? "";
		$old_6 = $data["old_6"] ?? "";
		$old_7 = $data["old_7"] ?? "";
		$old_8 = $data["old_8"] ?? "";
		$old_9 = $data["old_9"] ?? "";
		$old_10 = $data["old_10"] ?? "";
	} else {
		$id = 0;
		$update = false;
		$old_1 = "";
		$old_2 = "";
		$old_3 = "";
		$old_4 = "";
		$old_5 = "";
		$old_6 = "";
		$old_7 = "";
		$old_8 = "";
		$old_9 = "";
		$old_10 = "";
	}

	$meta_1 = apply_filters("pre_f1", empty($data["f1"]) ? "" : $data["f1"]);
	$meta_2 = apply_filters("pre_f2", empty($data["f2"]) ? "" : $data["f2"]);
	$meta_3 = apply_filters("pre_f3", empty($data["f3"]) ? "" : $data["f3"]);
	$meta_4 = apply_filters("pre_f4", empty($data["f4"]) ? "" : $data["f4"]);
	$meta_5 = apply_filters("pre_f5", empty($data["f5"]) ? "" : $data["f5"]);
	$meta_6 = apply_filters("pre_f6", empty($data["f6"]) ? "" : $data["f6"]);
	$meta_7 = apply_filters("pre_f7", empty($data["f7"]) ? "" : $data["f7"]);
	$meta_8 = apply_filters("pre_f8", empty($data["f8"]) ? "" : $data["f8"]);
	$meta_9 = apply_filters("pre_f9", empty($data["f9"]) ? "" : $data["f9"]);
	$meta_10 = apply_filters("pre_f10", empty($data["f10"]) ? "" : $data["f10"]);
	$meta_11 = apply_filters("pre_f11", empty($data["f11"]) ? "" : $data["f11"]);
	$meta_12 = apply_filters("pre_f12", empty($data["f12"]) ? "" : $data["f12"]);
	$meta_13 = apply_filters("pre_f13", empty($data["f13"]) ? "" : $data["f13"]);
	$meta_14 = apply_filters("pre_f14", empty($data["f14"]) ? "" : $data["f14"]);
	$meta_15 = apply_filters("pre_f15", empty($data["f15"]) ? "" : $data["f15"]);
	$meta_16 = apply_filters("pre_f16", empty($data["f16"]) ? "" : $data["f16"]);
	$meta_17 = apply_filters("pre_f17", empty($data["f17"]) ? "" : $data["f17"]);
	$meta_18 = apply_filters("pre_f18", empty($data["f18"]) ? "" : $data["f18"]);
	$meta_19 = apply_filters("pre_f19", empty($data["f19"]) ? "" : $data["f19"]);
	$meta_20 = apply_filters("pre_f20", empty($data["f20"]) ? "" : $data["f20"]);

	do_action("after_insert", $id, $data);
	return $id;
}
