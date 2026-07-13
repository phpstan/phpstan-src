		/* $nop = $self->maybeCreateZeroLengthNop($self->tokenPos);
		 * if ($nop !== null) { $self->semStack[$1][] = $nop; } $$ = $self->semStack[$1]; */
		zv::Val nop = maybeCreateZeroLengthNop(tokenPos);
		if (!nop.isNull()) {
			pushOnto(PN_SEM(1, 1), std::move(nop));
		}
		semValue = PN_SEM(1, 1);
