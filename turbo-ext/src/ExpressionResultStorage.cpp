/*
 * PHPStanTurbo\ExpressionResultStorage — native implementation of
 * PHPStan\Analyser\ExpressionResultStorage.
 *
 * Not final: a PHP stub subclass extends this class. duplicate() creates
 * instances of the object's own class (the stub), so userland type hints
 * keep working without a configured Impl entry.
 *
 * The before-scope table is two id-keyed arrays in private property slots,
 * exactly like the PHP twin: exprsById pins each stored Expr so its object
 * handle cannot be reused while scopesById still maps it. duplicate() copies
 * the two array zvals by refcount (copy-on-write) — the eager per-entry copy
 * of the twin's former SplObjectStorage is what made this worth porting.
 * pendingFibers/parkedFibers are ordinary public properties read and written
 * by FiberNodeScopeResolver in PHP; the native code never touches them.
 */

#include "support.h"
#include "zv.h"

#define PT_ERS_PROP_EXPRS 0
#define PT_ERS_PROP_SCOPES 1

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExpressionResultStorage. State lives in the PHP
 * object's exprsById/scopesById properties. */
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
		zv::ObjRef src(self);
		zv::ObjRef dst(&newObj);
		dst.propAtWrite(PT_ERS_PROP_EXPRS, zv::Val::copyOf(src.propAt(PT_ERS_PROP_EXPRS)));
		dst.propAtWrite(PT_ERS_PROP_SCOPES, zv::Val::copyOf(src.propAt(PT_ERS_PROP_SCOPES)));
		return zv::Val::adopt(newObj);
	}

	void storeBeforeScope(zval *expr, zval *scope)
	{
		zend_ulong id = Z_OBJ_HANDLE_P(expr);
		zv::ObjRef obj(self);
		zv::ArrRef(obj.propAt(PT_ERS_PROP_EXPRS).raw()).setIndex(id, zv::Ref(expr));
		zv::ArrRef(obj.propAt(PT_ERS_PROP_SCOPES).raw()).setIndex(id, zv::Ref(scope));
	}

	zv::Val findBeforeScope(zval *expr) const
	{
		zv::Ref found = zv::ArrRef(zv::ObjRef(self).propAt(PT_ERS_PROP_SCOPES).raw()).findIndex(Z_OBJ_HANDLE_P(expr));
		if (found.raw() == NULL) {
			return zv::Val::null();
		}
		return zv::Val::copyOf(found);
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
	/* not final: a PHP stub subclass extends this class; exprsById/scopesById
	 * must stay in this order (OBJ_PROP_NUM slots) */
	cls.privateArrayProperty("exprsById");
	cls.privateArrayProperty("scopesById");
	cls.publicArrayProperty("pendingFibers");
	cls.publicArrayProperty("parkedFibers");

	cls.method("duplicate", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = ExpressionResultStorage(ZEND_THIS).duplicate();
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("storeBeforeScope", reg::Public, 2, { reg::any("expr"), reg::any("scope") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *scope;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).storeBeforeScope(expr, scope);
	});

	cls.method("findBeforeScope", reg::Public, 1, { reg::any("expr") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionResultStorage(ZEND_THIS).findBeforeScope(expr).intoReturnValue(return_value);
	});

	cls.register_();
}

/* }}} */
