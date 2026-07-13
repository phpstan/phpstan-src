		/* $$ = array($1); foreach ($$ as $s): backtick parseEscapeSequences() on InterpolatedStringParts */
		zv::Arr arr = arrayOf(PN_SEM(1, 1));
		for (auto entry : arr.arrRef()) {
			if (isInstanceOf(entry.value(), "Node\\InterpolatedStringPart")) {
				parseEscapeSequencesInPart(entry.value(), "`");
			}
		}
		semValue = std::move(arr);
