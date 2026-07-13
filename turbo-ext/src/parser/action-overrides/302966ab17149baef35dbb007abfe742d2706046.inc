		/* $attrs['kind'] = KIND_DOUBLE_QUOTED; double-quote parseEscapeSequences() on the
		 * InterpolatedStringParts of $2; $$ = new Scalar\InterpolatedString($2, $attrs) */
		zv::Arr attrs = PN_ATTRS(3, 1, 3);
		attrs.set("kind", zv::Val::integer(2 /* Scalar\String_::KIND_DOUBLE_QUOTED */));
		for (auto entry : zv::ArrRef(PN_SEM(3, 2).raw())) {
			if (isInstanceOf(entry.value(), "Node\\InterpolatedStringPart")) {
				parseEscapeSequencesInPart(entry.value(), "\"");
			}
		}
		semValue = newNode("Scalar\\InterpolatedString", std::move(attrs), PN_SEM(3, 2));
