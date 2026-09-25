/*
 * PHPStanTurbo\DynamicReturnTypeStoragePrimer — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\DynamicReturnTypeStoragePrimer.
 *
 * A DI service (#[AutowiredService], no constructor). pushPrimedStorage()
 * pushes a storage carrying a call's argument results onto the scope's
 * storage stack and returns the matching pop as a Closure, as the twin does;
 * native callers use pt_dynamic_return_type_storage_primer_push() / _pop()
 * instead, which record whether a storage was pushed and create no closure
 * (the twin's closures are only ever called once, in the caller's finally).
 * The scope, storage and results are asked through their direct entries.
 */

#include "support.h"
#include "generated/DynamicReturnTypeStoragePrimer.h"

namespace sigs = ptdecl::DynamicReturnTypeStoragePrimer::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_dynamic_return_type_storage_primer = nullptr;

namespace {

pt_method_site pt_drtsp_push_primed_storage_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\DynamicReturnTypeStoragePrimer. */
class DynamicReturnTypeStoragePrimer
{
public:
	/* pushPrimedStorage() up to the returned closure: pushed tells whether
	 * the pop has to run; $argsResult NULL or IS_NULL for null; false =
	 * pending exception */
	[[nodiscard]] static bool push(zend_object *scope, zval *argsResult, bool &pushed)
	{
		pushed = false;
		if (argsResult == NULL || Z_TYPE_P(argsResult) == IS_NULL) return true;

		zv::Val current = pt_mutating_scope_get_current_expression_result_storage(scope);
		if (UNEXPECTED(current.isUndef())) return false;
		zv::Val primed = current.isNull() ? pt_expression_result_storage_new() : pt_expression_result_storage_duplicate(current.raw());
		if (UNEXPECTED(primed.isUndef())) return false;
		bool primedAny = false;

		zv::Val argResultsHold;
		zval *argResults = pt_args_result_arg_results(argsResult, argResultsHold);
		if (UNEXPECTED(argResults == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(argResults) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(argResults));
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
			if (UNEXPECTED(closureCe == NULL)) return false;
			zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
			if (UNEXPECTED(arrowFunctionCe == NULL)) return false;
			/* a copy of the table as the twin's foreach iterates its own
			 * value (the storage writes cannot reach it anyway) */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(argResults));
			for (auto entry : zv::TableRef(Z_ARRVAL_P(iterated.raw()))) {
				zval *argResult = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(argResult) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getExpr() on %s", zend_zval_value_name(argResult));
					return false;
				}
				zv::Val exprHold;
				zval *argExpr = pt_expression_result_expr(argResult, exprHold);
				if (UNEXPECTED(argExpr == NULL)) return false;
				if (instanceof_function(Z_OBJCE_P(argExpr), closureCe) || instanceof_function(Z_OBJCE_P(argExpr), arrowFunctionCe)) continue;
				pt_expression_result_storage_store(primed.raw(), argExpr, argResult);
				if (UNEXPECTED(EG(exception) != NULL)) return false;
				primedAny = true;
			}
		}

		if (!primedAny) return true;

		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(scope, primed.raw()))) return false;
		pushed = true;
		return true;
	}

	/* Mirrors pushPrimedStorage(): the pop (or the no-op) as a Closure. */
	static zv::Val pushPrimedStorage(zval *scope, zval *argsResult)
	{
		zv::Val noop = pt_native_closure(&noopBody);
		bool pushed = false;
		if (UNEXPECTED(!push(Z_OBJ_P(scope), argsResult, pushed))) return zv::Val();
		if (!pushed) return pt_native_closure_to_closure(noop.raw());
		zv::Val pop = pt_native_closure(&popBody, scope);
		return pt_native_closure_to_closure(pop.raw());
	}

private:
	/* static function (): void {} */
	static void noopBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		(void) return_value;
	}

	/* static function () use ($scope): void { $scope->popExpressionResultStorage(); }
	 * — captures: $scope */
	static void popBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		(void) return_value;
		(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ(captures[0]));
	}
};

} // namespace phpstanturbo

using phpstanturbo::DynamicReturnTypeStoragePrimer;

/* {{{ exported helpers */

bool pt_dynamic_return_type_storage_primer_push(zval *primer, zval *scope, zval *argsResult, pt_primed_storage &out)
{
	out.scope = Z_OBJ_P(scope);
	out.pushed = false;
	ZVAL_UNDEF(&out.pop);
	if (EXPECTED(Z_OBJCE_P(primer) == pt_ce_dynamic_return_type_storage_primer)) return DynamicReturnTypeStoragePrimer::push(Z_OBJ_P(scope), argsResult, out.pushed);

	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, argsResult != NULL ? argsResult : &null};
	zv::Val pop = pt_call_method_cached(pt_drtsp_push_primed_storage_site, Z_OBJ_P(primer), PT_LC("pushprimedstorage"), 2, argv);
	if (UNEXPECTED(pop.isUndef())) return false;
	out.pop = pop.take();
	return true;
}

bool pt_dynamic_return_type_storage_primer_pop(pt_primed_storage &primed)
{
	if (Z_TYPE(primed.pop) != IS_UNDEF) {
		bool ok = !pt_type_call_callable(&primed.pop, 0, NULL).isUndef();
		zval_ptr_dtor(&primed.pop);
		ZVAL_UNDEF(&primed.pop);
		return ok;
	}
	if (!primed.pushed) return true;
	primed.pushed = false;
	return pt_mutating_scope_pop_expression_result_storage(primed.scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_dynamic_return_type_storage_primer)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\DynamicReturnTypeStoragePrimer");
	ptdecl::DynamicReturnTypeStoragePrimer::declareClass(cls);
	ptdecl::DynamicReturnTypeStoragePrimer::declareProperties(cls);

	cls.method(sigs::pushPrimedStorage, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *argsResult;
		if (!zp::parse<zp::Obj, zp::ObjOrNull>(execute_data, scope, argsResult)) RETURN_THROWS();
		PT_RETURN_VAL(DynamicReturnTypeStoragePrimer::pushPrimedStorage(scope, argsResult));
	});

	cls.shadow(&pt_ce_dynamic_return_type_storage_primer);
}

/* }}} */
