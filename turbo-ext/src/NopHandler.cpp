/*
 * PHPStanTurbo\NopHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\NopHandler.
 *
 * A DI service (#[AutowiredService]) without a constructor. processStmt()
 * is registered as the class's statement-handler entry (Engine.h); the
 * result is built through InternalStatementResult's direct entry.
 */

#include "support.h"
#include "generated/NopHandler.h"

namespace sigs = ptdecl::NopHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_nop_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\NopHandler; UNDEF = pending
 * exception. */
class NopHandler
{
public:
	explicit NopHandler(zend_object *self) : self(self) {}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		zend_class_entry *nopCe = pt_class(PT_CLASS_NOP_STMT);
		if (UNEXPECTED(nopCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(stmt), nopCe);
		return true;
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
		return NopHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::NopHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_nop_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\NopHandler");
	ptdecl::NopHandler::declareClass(cls);
	ptdecl::NopHandler::declareProperties(cls);

	cls.method<&NopHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(NopHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_nop_handler);
	pt_stmt_handler_entry_register(&pt_ce_nop_handler, &NopHandler::processStmtEntry);
}

/* }}} */
