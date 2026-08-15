/*
 * PHPStanTurbo\ExpressionResultStorage — native implementation of
 * PHPStan\Analyser\ExpressionResultStorage.
 *
 * Not final: a PHP stub subclass extends this class. duplicate() creates
 * instances of the object's own class (the stub), so userland type hints
 * keep working without a configured Impl entry.
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

#include "support.h"
#include "zv.h"

#define PT_ERS_PROP_EXPRS 0
#define PT_ERS_PROP_RESULTS 1
#define PT_ERS_PROP_FALLBACK 2

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
		if (UNEXPECTED(object_init_ex(&newObj, Z_OBJCE_P(self)) != SUCCESS)) {
			return zv::Val();
		}
		zv::ObjRef(&newObj).propAtWrite(PT_ERS_PROP_FALLBACK, zv::Val::copyOf(zv::Ref(self)));
		return zv::Val::adopt(newObj);
	}

	void mergeResults(zval *other)
	{
		zv::ObjRef src(other);
		zv::ObjRef dst(self);
		zv::ArrRef dstExprs(dst.propAt(PT_ERS_PROP_EXPRS).raw());
		zv::ArrRef dstResults(dst.propAt(PT_ERS_PROP_RESULTS).raw());
		for (auto entry : zv::ArrRef(src.propAt(PT_ERS_PROP_EXPRS).raw())) {
			dstExprs.setIndex(entry.indexKey(), entry.value());
		}
		for (auto entry : zv::ArrRef(src.propAt(PT_ERS_PROP_RESULTS).raw())) {
			dstResults.setIndex(entry.indexKey(), entry.value());
		}
	}

	void storeExpressionResult(zval *expr, zval *expressionResult)
	{
		zend_ulong id = Z_OBJ_HANDLE_P(expr);
		zv::ObjRef obj(self);
		zv::ArrRef(obj.propAt(PT_ERS_PROP_EXPRS).raw()).setIndex(id, zv::Ref(expr));
		zv::ArrRef(obj.propAt(PT_ERS_PROP_RESULTS).raw()).setIndex(id, zv::Ref(expressionResult));
	}

	zv::Val findExpressionResult(zval *expr) const
	{
		zend_ulong id = Z_OBJ_HANDLE_P(expr);
		zval *cur = self;
		for (;;) {
			zv::ObjRef obj(cur);
			zv::Ref found = zv::ArrRef(obj.propAt(PT_ERS_PROP_RESULTS).raw()).findIndex(id);
			if (found.raw() != NULL) {
				return zv::Val::copyOf(found);
			}
			/* the twin recurses into ?self $fallback; iterate the chain */
			zval *fallback = obj.propAt(PT_ERS_PROP_FALLBACK).raw();
			if (Z_TYPE_P(fallback) != IS_OBJECT) {
				return zv::Val::null();
			}
			cur = fallback;
		}
	}

private:
	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionResultStorage;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_expression_result_storage()
{
	reg::Class cls("PHPStanTurbo\\ExpressionResultStorage");
	/* not final: a PHP stub subclass extends this class; exprsById/resultsById/
	 * fallback must stay in this order (OBJ_PROP_NUM slots) */
	cls.privateArrayProperty("exprsById");
	cls.privateArrayProperty("resultsById");
	cls.privateNullProperty("fallback");

	/* the twin's constructor only initialized its SplObjectStorage; the
	 * native property defaults already cover that */
	cls.method("__construct", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method("duplicate", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = ExpressionResultStorage(ZEND_THIS).duplicate();
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("mergeResults", reg::Public, 1, { reg::any("other") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(other)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).mergeResults(other);
	});

	cls.method("storeExpressionResult", reg::Public, 2, { reg::any("expr"), reg::any("expressionResult") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *expressionResult;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(expressionResult)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).storeExpressionResult(expr, expressionResult);
	});

	cls.method("findExpressionResult", reg::Public, 1, { reg::any("expr") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).findExpressionResult(expr).intoReturnValue(return_value);
	});

	cls.register_();
}

/* }}} */
