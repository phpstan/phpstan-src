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
	 * slots): the two id-keyed arrays deliberately replace the twin's
	 * SplObjectStorage $exprResults; $fallback is the twin's `?self` */
	cls.privateArrayProperty("exprsById");
	cls.privateArrayProperty("resultsById");
	cls.property("fallback", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, "self");

	/* the twin's constructor only initialized its SplObjectStorage; the
	 * native property defaults already cover that */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::duplicate, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = ExpressionResultStorage(ZEND_THIS).duplicate();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	/* self $other: the native class exactly (its slots are read directly) */
	cls.method(sigs::mergeResults, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expression_result_storage)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).mergeResults(other);
	});

	cls.method(sigs::storeExpressionResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *expressionResult;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
			Z_PARAM_OBJECT(expressionResult)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!pt_shadow_instanceof(Z_OBJCE_P(expressionResult), pt_ce_expression_result, ZEND_STRL("PHPStan\\Analyser\\ExpressionResult")))) {
			zend_wrong_parameter_class_error(2, "PHPStan\\Analyser\\ExpressionResult", expressionResult);
			RETURN_THROWS();
		}
		ExpressionResultStorage(ZEND_THIS).storeExpressionResult(expr, expressionResult);
	});

	cls.method(sigs::findExpressionResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(expr, exprCe)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).findExpressionResult(expr).intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_expression_result_storage);
}

/* }}} */

/* {{{ direct entries: the native bodies for a native storage, the methods
 * of anything else (the PHP twin under the prefixed differential
 * activation) */

void pt_expression_result_storage_store(zval *storage, zval *expr, zval *expressionResult)
{
	if (EXPECTED(Z_OBJCE_P(storage) == pt_ce_expression_result_storage)) {
		ExpressionResultStorage(storage).storeExpressionResult(expr, expressionResult);
		return;
	}
	zv::Args argv{expr, expressionResult};
	(void) pt_type_call(Z_OBJ_P(storage), "storeexpressionresult", sizeof("storeexpressionresult") - 1, 2, argv);
}

zv::Val pt_expression_result_storage_new()
{
	return pt_type_new_ce(pt_ce_expression_result_storage, 0, NULL);
}

zv::Val pt_expression_result_storage_duplicate(zval *storage)
{
	if (EXPECTED(Z_OBJCE_P(storage) == pt_ce_expression_result_storage)) return ExpressionResultStorage(storage).duplicate();
	return pt_type_call(Z_OBJ_P(storage), "duplicate", sizeof("duplicate") - 1, 0, NULL);
}

bool pt_expression_result_storage_merge_results(zval *storage, zval *other)
{
	if (EXPECTED(Z_OBJCE_P(storage) == pt_ce_expression_result_storage && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_expression_result_storage)) {
		ExpressionResultStorage(storage).mergeResults(other);
		return true;
	}
	return !pt_type_call(Z_OBJ_P(storage), "mergeresults", sizeof("mergeresults") - 1, 1, other).isUndef();
}

/* }}} */
