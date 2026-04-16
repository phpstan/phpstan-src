<?php declare(strict_types = 1);

namespace BenchAndChainTruthyBlowup;

/**
 * Regression test for O(N²) in deep BooleanAnd chains.
 * Without the flattening optimization, each level recursed through
 * specifyTypesInCondition and filterByTruthyValue, creating O(N²) scope operations.
 * Slow at the original BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4.
 */
function test(string $x): void {
	if ($x !== "val_1" && $x !== "val_2" && $x !== "val_3" && $x !== "val_4" && $x !== "val_5" && $x !== "val_6" && $x !== "val_7" && $x !== "val_8" && $x !== "val_9" && $x !== "val_10" && $x !== "val_11" && $x !== "val_12" && $x !== "val_13" && $x !== "val_14" && $x !== "val_15" && $x !== "val_16" && $x !== "val_17" && $x !== "val_18" && $x !== "val_19" && $x !== "val_20" && $x !== "val_21" && $x !== "val_22" && $x !== "val_23" && $x !== "val_24" && $x !== "val_25" && $x !== "val_26" && $x !== "val_27" && $x !== "val_28" && $x !== "val_29" && $x !== "val_30" && $x !== "val_31" && $x !== "val_32" && $x !== "val_33" && $x !== "val_34" && $x !== "val_35" && $x !== "val_36" && $x !== "val_37" && $x !== "val_38" && $x !== "val_39" && $x !== "val_40" && $x !== "val_41" && $x !== "val_42" && $x !== "val_43" && $x !== "val_44" && $x !== "val_45" && $x !== "val_46" && $x !== "val_47" && $x !== "val_48" && $x !== "val_49" && $x !== "val_50" && $x !== "val_51" && $x !== "val_52" && $x !== "val_53" && $x !== "val_54" && $x !== "val_55" && $x !== "val_56" && $x !== "val_57" && $x !== "val_58" && $x !== "val_59" && $x !== "val_60" && $x !== "val_61" && $x !== "val_62" && $x !== "val_63" && $x !== "val_64" && $x !== "val_65" && $x !== "val_66" && $x !== "val_67" && $x !== "val_68" && $x !== "val_69" && $x !== "val_70" && $x !== "val_71" && $x !== "val_72" && $x !== "val_73" && $x !== "val_74" && $x !== "val_75" && $x !== "val_76" && $x !== "val_77" && $x !== "val_78" && $x !== "val_79" && $x !== "val_80" && $x !== "val_81" && $x !== "val_82" && $x !== "val_83" && $x !== "val_84" && $x !== "val_85" && $x !== "val_86" && $x !== "val_87" && $x !== "val_88" && $x !== "val_89" && $x !== "val_90" && $x !== "val_91" && $x !== "val_92" && $x !== "val_93" && $x !== "val_94" && $x !== "val_95" && $x !== "val_96" && $x !== "val_97" && $x !== "val_98" && $x !== "val_99" && $x !== "val_100") {
		echo $x;
	}
}
