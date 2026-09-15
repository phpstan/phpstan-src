/*
 * PHPStanTurbo\ExpressionResultStorage — native implementation of
 * PHPStan\Analyser\ExpressionResultStorage.
 *
 * Declared as PHPStan\Analyser\ExpressionResultStorage itself at activation
 * (final, like the twin); duplicate() creates instances of the object's own
 * class.
 *
 * The result table is two id-keyed arrays in private property slots:
 * exprsById pins each stored Expr so its object handle cannot be reused
 * while resultsById still maps it — the PHP twin's SplObjectStorage pins its
 * keys the same way. duplicate() copies nothing: the new storage carries the
 * source as its read-only fallback (writes never reach it), mirroring the
 * twin's O(1) duplicate(); findExpressionResult() walks the fallback chain
 * on a miss. mergeResults() unions the other storage's own entries (not its
 * fallback chain) into this one, like the twin's SplObjectStorage::addAll().
 */

#include "TypeTraits.h"
#include "generated/ExpressionResultStorage.h"

namespace slots = ptdecl::ExpressionResultStorage::slot;
namespace sigs = ptdecl::ExpressionResultStorage::sig;
#include "support.h"
#include "zv.h"

#define PT_ERS_PROP_FALLBACK 2

zend_class_entry *pt_ce_expression_result_storage = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExpressionResultStorage. State lives in the PHP
 * object's exprsById/resultsById/fallback properties. */
class ExpressionResultStorage
{
public:
	explicit ExpressionResultStorage(zval *self) : self(self) {}

	zv::Val duplicate() const
	{
		zval newObj;
		if (UNEXPECTED(object_init_ex(&newObj, Z_OBJCE_P(self)) != SUCCESS)) return zv::Val();
		zv::ObjRef(&newObj).propAtWrite(PT_ERS_PROP_FALLBACK, zv::Val::copyOf(zv::Ref(self)));
		return zv::Val::adopt(newObj);
	}

	void mergeResults(zval *other)
	{
		zv::ObjRef src(other);
		zv::ObjRef dst(self);
		zv::ArrRef dstExprs(dst.propAt(slots::exprResults).raw());
		zv::ArrRef dstResults(dst.propAt(slots::fallback).raw());
		for (auto entry : zv::ArrRef(src.propAt(slots::exprResults).raw())) {
			dstExprs.setIndex(entry.indexKey(), entry.value());
		}
		for (auto entry : zv::ArrRef(src.propAt(slots::fallback).raw())) {
			dstResults.setIndex(entry.indexKey(), entry.value());
		}
	}

	void storeExpressionResult(zval *expr, zval *expressionResult)
	{
		zend_ulong id = Z_OBJ_HANDLE_P(expr);
		zv::ObjRef obj(self);
		zv::ArrRef(obj.propAt(slots::exprResults).raw()).setIndex(id, zv::Ref(expr));
		zv::ArrRef(obj.propAt(slots::fallback).raw()).setIndex(id, zv::Ref(expressionResult));
	}

	zv::Val findExpressionResult(zval *expr) const
	{
		zend_ulong id = Z_OBJ_HANDLE_P(expr);
		zval *cur = self;
		for (;;) {
			zv::ObjRef obj(cur);
			zv::Ref found = zv::ArrRef(obj.propAt(slots::fallback).raw()).findIndex(id);
			if (found.raw() != NULL) return zv::Val::copyOf(found);
			/* the twin recurses into ?self $fallback; iterate the chain */
			zval *fallback = obj.propAt(PT_ERS_PROP_FALLBACK).raw();
			if (Z_TYPE_P(fallback) != IS_OBJECT) return zv::Val::null();
			cur = fallback;
		}
	}

private:
	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionResultStorage;

#include "TypeTraits.h"

zv::Val pt_expression_result_storage_find(zval *storage, zval *expr)
{
	/* the twin is final: an instance of the native class entry takes the
	 * native path, anything else (the PHP twin declared next to the native
	 * class in the differential tests) the method */
	if (EXPECTED(Z_OBJCE_P(storage) == pt_ce_expression_result_storage)) return ExpressionResultStorage(storage).findExpressionResult(expr);
	return pt_type_call(Z_OBJ_P(storage), "findexpressionresult", sizeof("findexpressionresult") - 1, 1, expr);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_expression_result_storage()
{
	reg::Class cls("PHPStan\\Analyser\\ExpressionResultStorage");
	ptdecl::ExpressionResultStorage::declareClass(cls);
	/* exprsById/resultsById/fallback must stay in this order (OBJ_PROP_NUM
	 * slots) */
	cls.privateArrayProperty("exprsById");
	cls.privateArrayProperty("resultsById");
	cls.privateNullProperty("fallback");

	/* the twin's constructor only initialized its SplObjectStorage; the
	 * native property defaults already cover that */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method("duplicate", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = ExpressionResultStorage(ZEND_THIS).duplicate();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("mergeResults", reg::Public, 1, { reg::any("other") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		if (!zp::parse<zp::Obj>(execute_data, other)) RETURN_THROWS();
		ExpressionResultStorage(ZEND_THIS).mergeResults(other);
	});

	cls.method("storeExpressionResult", reg::Public, 2, { reg::any("expr"), reg::any("expressionResult") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *expressionResult;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, expressionResult)) RETURN_THROWS();
		ExpressionResultStorage(ZEND_THIS).storeExpressionResult(expr, expressionResult);
	});

	cls.method("findExpressionResult", reg::Public, 1, { reg::any("expr") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		ExpressionResultStorage(ZEND_THIS).findExpressionResult(expr).intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_expression_result_storage);
}

/* }}} */

/* {{{ direct entries: the native bodies for a native storage, the methods
 * of anything else (the PHP twin under the prefixed differential
 * activation) */

zv::Val pt_expression_result_storage_new()
{
	return pt_type_new_ce(pt_ce_expression_result_storage, 0, NULL);
}

zv::Val pt_expression_result_storage_duplicate(zval *storage)
{
	if (EXPECTED(Z_OBJCE_P(storage) == pt_ce_expression_result_storage)) return ExpressionResultStorage(storage).duplicate();
	return pt_type_call(Z_OBJ_P(storage), "duplicate", sizeof("duplicate") - 1, 0, NULL);
}

/* }}} */
