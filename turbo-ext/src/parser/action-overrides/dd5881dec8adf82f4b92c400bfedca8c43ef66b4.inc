		/* foreach ($1 as $s): backtick parseEscapeSequences() on InterpolatedStringParts; $$ = $1 */
		for (auto entry : zv::ArrRef(PN_SEM(1, 1).raw())) {
			if (isInstanceOf(entry.value(), "Node\\InterpolatedStringPart")) {
				parseEscapeSequencesInPart(entry.value(), "`");
			}
		}
		semValue = PN_SEM(1, 1);
