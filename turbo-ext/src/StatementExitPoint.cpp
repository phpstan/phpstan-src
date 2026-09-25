/*
 * PHPStanTurbo\StatementExitPoint — native implementation of
 * PHPStan\Analyser\StatementExitPoint.
 *
 * The public (@api) exit point a StatementResult hands to the rules:
 * InternalStatementExitPoint::toPublic() creates one per exit point. State
 * lives in the twin's two promoted property slots, in its order.
 */

#include "support.h"
#include "generated/StatementExitPoint.h"

namespace slots = ptdecl::StatementExitPoint::slot;
namespace sigs = ptdecl::StatementExitPoint::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_statement_exit_point = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StatementExitPoint. */
class StatementExitPoint
{
public:
	explicit StatementExitPoint(zend_object *self) : self(self) {}

	/* __construct(private Stmt $statement, private Scope $scope) */
	void construct(zval *statement, zval *scope) const
	{
		pt_write_slot(self, slots::statement, statement);
		pt_write_slot(self, slots::scope, scope);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *statement, zval *scope)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_statement_exit_point) != SUCCESS)) return zv::Val();
		StatementExitPoint(Z_OBJ(object)).construct(statement, scope);
		return zv::Val::adopt(object);
	}

	zv::Val getStatement() const { return read(slots::statement, "statement"); }
	zv::Val getScope() const { return read(slots::scope, "scope"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::StatementExitPoint;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_statement_exit_point_new(zval *statement, zval *scope)
{
	return StatementExitPoint::create(statement, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_statement_exit_point)
{
	reg::Class cls("PHPStan\\Analyser\\StatementExitPoint");
	ptdecl::StatementExitPoint::declareClass(cls);
	ptdecl::StatementExitPoint::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statement, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, statement, scope)) RETURN_THROWS();
		StatementExitPoint(Z_OBJ_P(ZEND_THIS)).construct(statement, scope);
	});

	cls.method<&StatementExitPoint::getStatement>(sigs::getStatement);

	cls.method<&StatementExitPoint::getScope>(sigs::getScope);

	cls.shadow(&pt_ce_statement_exit_point);
}

/* }}} */
