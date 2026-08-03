<?php declare(strict_types = 1);

namespace BenchBigConstantStringUnion;

/**
 * Comparing two unions of constant strings used to cost one isSuperTypeOf() call per
 * pair of members. Every call site below makes PHPStan compare, accept or narrow such
 * a union against another one.
 */

/** @param 'v0'|'v1'|'v2'|'v3'|'v4'|'v5'|'v6'|'v7'|'v8'|'v9'|'v10'|'v11'|'v12'|'v13'|'v14'|'v15'|'v16'|'v17'|'v18'|'v19'|'v20'|'v21'|'v22'|'v23'|'v24'|'v25'|'v26'|'v27'|'v28'|'v29'|'v30'|'v31'|'v32'|'v33'|'v34'|'v35'|'v36'|'v37'|'v38'|'v39'|'v40'|'v41'|'v42'|'v43'|'v44'|'v45'|'v46'|'v47'|'v48'|'v49'|'v50'|'v51'|'v52'|'v53'|'v54'|'v55'|'v56'|'v57'|'v58'|'v59'|'v60'|'v61'|'v62'|'v63'|'v64'|'v65'|'v66'|'v67'|'v68'|'v69'|'v70'|'v71'|'v72'|'v73'|'v74'|'v75'|'v76'|'v77'|'v78'|'v79'|'v80'|'v81'|'v82'|'v83'|'v84'|'v85'|'v86'|'v87'|'v88'|'v89'|'v90'|'v91'|'v92'|'v93'|'v94'|'v95'|'v96'|'v97'|'v98'|'v99'|'v100'|'v101'|'v102'|'v103'|'v104'|'v105'|'v106'|'v107'|'v108'|'v109'|'v110'|'v111'|'v112'|'v113'|'v114'|'v115'|'v116'|'v117'|'v118'|'v119'|'v120'|'v121'|'v122'|'v123'|'v124'|'v125'|'v126'|'v127'|'v128'|'v129'|'v130'|'v131'|'v132'|'v133'|'v134'|'v135'|'v136'|'v137'|'v138'|'v139'|'v140'|'v141'|'v142'|'v143'|'v144'|'v145'|'v146'|'v147'|'v148'|'v149' $value */
function accept(string $value): void
{
}

/** @return 'v0'|'v1'|'v2'|'v3'|'v4'|'v5'|'v6'|'v7'|'v8'|'v9'|'v10'|'v11'|'v12'|'v13'|'v14'|'v15'|'v16'|'v17'|'v18'|'v19'|'v20'|'v21'|'v22'|'v23'|'v24'|'v25'|'v26'|'v27'|'v28'|'v29'|'v30'|'v31'|'v32'|'v33'|'v34'|'v35'|'v36'|'v37'|'v38'|'v39'|'v40'|'v41'|'v42'|'v43'|'v44'|'v45'|'v46'|'v47'|'v48'|'v49'|'v50'|'v51'|'v52'|'v53'|'v54'|'v55'|'v56'|'v57'|'v58'|'v59'|'v60'|'v61'|'v62'|'v63'|'v64'|'v65'|'v66'|'v67'|'v68'|'v69'|'v70'|'v71'|'v72'|'v73'|'v74'|'v75'|'v76'|'v77'|'v78'|'v79'|'v80'|'v81'|'v82'|'v83'|'v84'|'v85'|'v86'|'v87'|'v88'|'v89'|'v90'|'v91'|'v92'|'v93'|'v94'|'v95'|'v96'|'v97'|'v98'|'v99'|'v100'|'v101'|'v102'|'v103'|'v104'|'v105'|'v106'|'v107'|'v108'|'v109'|'v110'|'v111'|'v112'|'v113'|'v114'|'v115'|'v116'|'v117'|'v118'|'v119'|'v120'|'v121'|'v122'|'v123'|'v124'|'v125'|'v126'|'v127'|'v128'|'v129'|'v130'|'v131'|'v132'|'v133'|'v134'|'v135'|'v136'|'v137'|'v138'|'v139'|'v140'|'v141'|'v142'|'v143'|'v144'|'v145'|'v146'|'v147'|'v148'|'v149' */
function produce(): string
{
	return 'v0';
}

/** @param 'v0'|'v2'|'v4'|'v6'|'v8'|'v10'|'v12'|'v14'|'v16'|'v18'|'v20'|'v22'|'v24'|'v26'|'v28'|'v30'|'v32'|'v34'|'v36'|'v38'|'v40'|'v42'|'v44'|'v46'|'v48'|'v50'|'v52'|'v54'|'v56'|'v58'|'v60'|'v62'|'v64'|'v66'|'v68'|'v70'|'v72'|'v74'|'v76'|'v78'|'v80'|'v82'|'v84'|'v86'|'v88'|'v90'|'v92'|'v94'|'v96'|'v98'|'v100'|'v102'|'v104'|'v106'|'v108'|'v110'|'v112'|'v114'|'v116'|'v118'|'v120'|'v122'|'v124'|'v126'|'v128'|'v130'|'v132'|'v134'|'v136'|'v138'|'v140'|'v142'|'v144'|'v146'|'v148' $value */
function acceptEven(string $value): void
{
}

function run(): void
{
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
	accept(produce());
	acceptEven(produce()); // @phpstan-ignore argument.type
}

function narrow(): void
{
	$v0 = produce();
	if ($v0 === 'v0') {
		echo $v0;
	}
	if (in_array($v0, ['v0', 'v1'], true)) {
		echo $v0;
	}
	$v1 = produce();
	if ($v1 === 'v1') {
		echo $v1;
	}
	if (in_array($v1, ['v1', 'v2'], true)) {
		echo $v1;
	}
	$v2 = produce();
	if ($v2 === 'v2') {
		echo $v2;
	}
	if (in_array($v2, ['v2', 'v3'], true)) {
		echo $v2;
	}
	$v3 = produce();
	if ($v3 === 'v3') {
		echo $v3;
	}
	if (in_array($v3, ['v3', 'v4'], true)) {
		echo $v3;
	}
	$v4 = produce();
	if ($v4 === 'v4') {
		echo $v4;
	}
	if (in_array($v4, ['v4', 'v5'], true)) {
		echo $v4;
	}
	$v5 = produce();
	if ($v5 === 'v5') {
		echo $v5;
	}
	if (in_array($v5, ['v5', 'v6'], true)) {
		echo $v5;
	}
	$v6 = produce();
	if ($v6 === 'v6') {
		echo $v6;
	}
	if (in_array($v6, ['v6', 'v7'], true)) {
		echo $v6;
	}
	$v7 = produce();
	if ($v7 === 'v7') {
		echo $v7;
	}
	if (in_array($v7, ['v7', 'v8'], true)) {
		echo $v7;
	}
	$v8 = produce();
	if ($v8 === 'v8') {
		echo $v8;
	}
	if (in_array($v8, ['v8', 'v9'], true)) {
		echo $v8;
	}
	$v9 = produce();
	if ($v9 === 'v9') {
		echo $v9;
	}
	if (in_array($v9, ['v9', 'v10'], true)) {
		echo $v9;
	}
	$v10 = produce();
	if ($v10 === 'v10') {
		echo $v10;
	}
	if (in_array($v10, ['v10', 'v11'], true)) {
		echo $v10;
	}
	$v11 = produce();
	if ($v11 === 'v11') {
		echo $v11;
	}
	if (in_array($v11, ['v11', 'v12'], true)) {
		echo $v11;
	}
	$v12 = produce();
	if ($v12 === 'v12') {
		echo $v12;
	}
	if (in_array($v12, ['v12', 'v13'], true)) {
		echo $v12;
	}
	$v13 = produce();
	if ($v13 === 'v13') {
		echo $v13;
	}
	if (in_array($v13, ['v13', 'v14'], true)) {
		echo $v13;
	}
	$v14 = produce();
	if ($v14 === 'v14') {
		echo $v14;
	}
	if (in_array($v14, ['v14', 'v15'], true)) {
		echo $v14;
	}
	$v15 = produce();
	if ($v15 === 'v15') {
		echo $v15;
	}
	if (in_array($v15, ['v15', 'v16'], true)) {
		echo $v15;
	}
	$v16 = produce();
	if ($v16 === 'v16') {
		echo $v16;
	}
	if (in_array($v16, ['v16', 'v17'], true)) {
		echo $v16;
	}
	$v17 = produce();
	if ($v17 === 'v17') {
		echo $v17;
	}
	if (in_array($v17, ['v17', 'v18'], true)) {
		echo $v17;
	}
	$v18 = produce();
	if ($v18 === 'v18') {
		echo $v18;
	}
	if (in_array($v18, ['v18', 'v19'], true)) {
		echo $v18;
	}
	$v19 = produce();
	if ($v19 === 'v19') {
		echo $v19;
	}
	if (in_array($v19, ['v19', 'v20'], true)) {
		echo $v19;
	}
}
