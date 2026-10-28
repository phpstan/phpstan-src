<?php declare(strict_types = 1);

namespace ReadableAcceptsReasons;

/** @param array{id: int, ...<string, array{x: int}>} $a */
function unsealedExtraOffsets(array $a): void {}

/** @param object{p0: int, p1: int, p2: int, p3: int, p4: int, p5: int} $o */
function missingProperties(object $o): void {}

/** @param non-empty-list<lowercase-string> $x */
function nonEmptyList(array $x): void {}

/** @param array{a: list<int>, b: list<int>} $a */
function listInShape(array $a): void {}

/** @param \Iterator<int, array{id: int}> $it */
function iterateRows(\Iterator $it): void {}

function doFoo(bool $c, int $i): void
{
	unsealedExtraOffsets(['id' => 1, 'k0' => ['x' => 'a0'], 'k1' => ['x' => 'a1'], 'k2' => ['x' => 'a2'], 'k3' => ['x' => 'a3'], 'k4' => ['x' => 'a4'], 'k5' => ['x' => 'a5']]);
	missingProperties((object) ['p0' => 0, 'p1' => 1, 'p2' => 2]);
	nonEmptyList($c ? [] : ['v0', 'v1', 'v2', 'v3', 'v4', 'v5', 'v6', 'v7', 'v8', 'v9', 'v10', 'v11', 'v12', 'v13', 'v14', 'v15', 'v16', 'v17', 'v18', 'v19', 'v20', 'v21', 'v22', 'v23', 'v24', 'v25', 'v26', 'v27', 'v28', 'v29', 'v30', 'v31', 'v32', 'v33', 'v34', 'v35', 'v36', 'v37', 'v38', 'v39', 'v40', 'v41', 'v42', 'v43', 'v44', 'v45', 'v46', 'v47', 'v48', 'v49', 'v50', 'v51', 'v52', 'v53', 'v54', 'v55', 'v56', 'v57', 'v58', 'v59', 'v60', 'v61', 'v62', 'v63', 'v64', 'v65', 'v66', 'v67', 'v68', 'v69', 'v70', 'v71', 'v72', 'v73', 'v74', 'v75', 'v76', 'v77', 'v78', 'v79', 'v80', 'v81', 'v82', 'v83', 'v84', 'v85', 'v86', 'v87', 'v88', 'v89', 'v90', 'v91', 'v92', 'v93', 'v94', 'v95', 'v96', 'v97', 'v98', 'v99', 'v100', 'v101', 'v102', 'v103', 'v104', 'v105', 'v106', 'v107', 'v108', 'v109', 'v110', 'v111', 'v112', 'v113', 'v114', 'v115', 'v116', 'v117', 'v118', 'v119', 'v120', 'v121', 'v122', 'v123', 'v124', 'v125', 'v126', 'v127', 'v128', 'v129', 'v130', 'v131', 'v132', 'v133', 'v134', 'v135', 'v136', 'v137', 'v138', 'v139', 'v140', 'v141', 'v142', 'v143', 'v144', 'v145', 'v146', 'v147', 'v148', 'v149']);
	listInShape(['a' => ['x' => 1], 'b' => [1]]);
	$rows = [['id' => 0, 'name' => 'a0'], ['id' => 1, 'name' => 'a1'], ['id' => 2, 'name' => 'a2'], ['id' => 3, 'name' => 'a3']];
	iterateRows(new \ArrayIterator([$rows[$i]]));
	$otherRows = [['id' => 0, 'name0' => 'a0'], ['id' => 1, 'name1' => 'a1'], ['id' => 2, 'name2' => 'a2'], ['id' => 3, 'name3' => 'a3'], ['id' => 4, 'name4' => 'a4'], ['id' => 5, 'name5' => 'a5'], ['id' => 6, 'name6' => 'a6'], ['id' => 7, 'name7' => 'a7']];
	iterateRows(new \ArrayIterator([$otherRows[$i]]));
}
