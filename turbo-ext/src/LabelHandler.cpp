/*
 * PHPStanTurbo\LabelHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\LabelHandler.
 *
 * A DI service (#[AutowiredService]) without a constructor. processStmt()
 * is registered as the class's statement-handler entry (Engine.h); the
 * result is built through InternalStatementResult's direct entry.
 */

#include "support.h"
#include "generated/LabelHandler.h"

namespace sigs = ptdecl::LabelHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_label_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\LabelHandler; UNDEF = pending
 * exception. */
class LabelHandler
{
public:
	explicit LabelHandler(zend_object *self) : self(self) {}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_LABEL_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) self;
		(void) nodeScopeResolver;
		(void) stmt;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return LabelHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::LabelHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_label_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\LabelHandler");
	ptdecl::LabelHandler::declareClass(cls);
	ptdecl::LabelHandler::declareProperties(cls);

	cls.method<&LabelHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(LabelHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_label_handler);
	pt_stmt_handler_entry_register(&pt_ce_label_handler, &LabelHandler::processStmtEntry);
}

/* }}} */
