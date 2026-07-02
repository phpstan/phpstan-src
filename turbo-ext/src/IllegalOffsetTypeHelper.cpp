/*
 * PHPStanTurbo\IllegalOffsetTypeHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\IllegalOffsetTypeHelper.
 *
 * mayOffsetThrowTypeError() is exported as
 * pt_illegal_offset_type_helper_may_offset_throw_type_error() for the native
 * ArrayDimFetchHandler and AssignHandler, which ask it for every offset they
 * read or write.
 */

#include "support.h"
#include "generated/IllegalOffsetTypeHelper.h"

namespace sigs = ptdecl::IllegalOffsetTypeHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

static zend_class_entry *pt_ce_illegal_offset_type_helper = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\IllegalOffsetTypeHelper. */
class IllegalOffsetTypeHelper
{
public:
	/* Mirrors mayOffsetThrowTypeError(); false = pending exception */
	[[nodiscard]] static bool mayOffsetThrowTypeError(zval *offsetType, bool &out)
	{
		// !$offsetType->isArray()->no() || !$offsetType->isObject()->no()
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(offsetType), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return false;
		if (isArray != PT_TRI_NO) {
			out = true;
			return true;
		}
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(offsetType), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return false;
		out = isObject != PT_TRI_NO;
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::IllegalOffsetTypeHelper;

bool pt_illegal_offset_type_helper_may_offset_throw_type_error(zval *offsetType, bool &out)
{
	return IllegalOffsetTypeHelper::mayOffsetThrowTypeError(offsetType, out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_illegal_offset_type_helper)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\IllegalOffsetTypeHelper");
	ptdecl::IllegalOffsetTypeHelper::declareClass(cls);
	ptdecl::IllegalOffsetTypeHelper::declareProperties(cls);

	cls.method<&IllegalOffsetTypeHelper::mayOffsetThrowTypeError, zp::Obj>(sigs::mayOffsetThrowTypeError);

	cls.shadow(&pt_ce_illegal_offset_type_helper);
}

/* }}} */
