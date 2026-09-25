/*
 * PHPStanTurbo\CircularTypeAliasErrorType — native implementation of
 * PHPStan\Type\CircularTypeAliasErrorType.
 *
 * Declared as PHPStan\Type\CircularTypeAliasErrorType itself at activation:
 * not final, extending the native ErrorType, with no body of its own — the
 * twin is a marker class, everything (the constructor and its $reason slot
 * included) is inherited.
 */

#include "TypeTraits.h"
#include "generated/CircularTypeAliasErrorType.h"

zend_class_entry *pt_ce_circular_type_alias_error_type = nullptr;

bool pt_circular_type_alias_error_type_new(zval *out, zend_string *reason)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_circular_type_alias_error_type) != SUCCESS)) return false;
	/* the inherited ErrorType::__construct($reason) */
	pt_error_type_construct(Z_OBJ_P(out), reason);
	return true;
}

/* {{{ registration */

PT_MINIT_REGISTRATION(pt_register_circular_type_alias_error_type)
{
	reg::Class cls("PHPStan\\Type\\CircularTypeAliasErrorType");
	ptdecl::CircularTypeAliasErrorType::declareClass(cls);
	ptdecl::CircularTypeAliasErrorType::declareProperties(cls);

	cls.shadow(&pt_ce_circular_type_alias_error_type);
}

/* }}} */
