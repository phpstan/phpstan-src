/*
 * PHPStanTurbo\ArgsResult — native implementation of
 * PHPStan\Analyser\ArgsResult.
 *
 * What ArgumentsHandler::processArgs() hands every call handler: the wrapped
 * ExpressionResult of the arguments' walk, the resolved acceptor, and the
 * per-argument results and by-reference markers keyed by the argument
 * value's object id. State lives in the twin's four promoted property slots,
 * in its order; the delegating getters read the wrapped result through
 * ExpressionResult's direct entries (its slots when it is the native class).
 * VariableFlowBuilder asks findArgResult() / isPassedByReference() through
 * pt_args_result_find_arg_result() / pt_args_result_is_passed_by_reference().
 */

#include "support.h"
#include "generated/ArgsResult.h"

namespace slots = ptdecl::ArgsResult::slot;
namespace sigs = ptdecl::ArgsResult::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_args_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ArgsResult. */
class ArgsResult
{
public:
	explicit ArgsResult(zend_object *self) : self(self) {}

	/* __construct(private ExpressionResult $expressionResult, private
	 * ?ParametersAcceptor $resolvedParametersAcceptor, private array
	 * $argResults, private array $byRefArguments = []); the acceptor NULL
	 * for null, $byRefArguments NULL for [] */
	void construct(zval *expressionResult, zval *resolvedParametersAcceptor, zval *argResults, zval *byRefArguments) const
	{
		pt_write_slot(self, slots::expressionResult, expressionResult);
		zval value = {};
		if (resolvedParametersAcceptor != NULL) {
			pt_write_slot(self, slots::resolvedParametersAcceptor, resolvedParametersAcceptor);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(self, slots::resolvedParametersAcceptor, &value);
		}
		pt_write_slot(self, slots::argResults, argResults);
		if (byRefArguments != NULL) {
			pt_write_slot(self, slots::byRefArguments, byRefArguments);
		} else {
			ZVAL_EMPTY_ARRAY(&value);
			pt_write_slot(self, slots::byRefArguments, &value);
		}
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *expressionResult, zval *resolvedParametersAcceptor, zval *argResults, zval *byRefArguments)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_args_result) != SUCCESS)) return zv::Val();
		ArgsResult(Z_OBJ(object)).construct(expressionResult, resolvedParametersAcceptor, argResults, byRefArguments);
		return zv::Val::adopt(object);
	}

	/* Mirrors findArgResult(): $this->argResults[spl_object_id($argValue)] ?? null
	 * (the `??` read of a never initialized property is null, not an Error) */
	zv::Val findArgResult(zval *argValue) const
	{
		zval *argResults = OBJ_PROP_NUM(self, slots::argResults);
		if (UNEXPECTED(Z_TYPE_P(argResults) != IS_ARRAY)) return zv::Val::null();
		zval *found = zend_hash_index_find(Z_ARRVAL_P(argResults), Z_OBJ_HANDLE_P(argValue));
		if (found == NULL) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(found));
	}

	zv::Val getArgResults() const { return read(slots::argResults, "argResults"); }

	/* Mirrors requireArgResult(). */
	zv::Val requireArgResult(zval *argValue) const
	{
		zv::Val result = findArgResult(argValue);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (EXPECTED(Z_TYPE_P(result.raw()) != IS_NULL)) return result;

		zv::Val startLine = pt_type_call(Z_OBJ_P(argValue), PT_LC("getstartline"), 0, NULL);
		if (UNEXPECTED(startLine.isUndef())) return zv::Val();
		zend_class_entry *exceptionCe = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
		if (UNEXPECTED(exceptionCe == NULL)) return zv::Val();
		zend_throw_exception_ex(exceptionCe, 0, "No stored ExpressionResult for a %s argument on line " ZEND_LONG_FMT ".", ZSTR_VAL(Z_OBJCE_P(argValue)->name), zval_get_long(startLine.raw()));
		return zv::Val();
	}

	/* Mirrors isPassedByReference(): isset($this->byRefArguments[spl_object_id($arg)])
	 * (isset() of a never initialized property is false, not an Error) */
	[[nodiscard]] bool isPassedByReference(zval *arg, bool &out) const
	{
		zval *byRefArguments = OBJ_PROP_NUM(self, slots::byRefArguments);
		if (UNEXPECTED(Z_TYPE_P(byRefArguments) != IS_ARRAY)) {
			out = false;
			return true;
		}
		zval *found = zend_hash_index_find(Z_ARRVAL_P(byRefArguments), Z_OBJ_HANDLE_P(arg));
		out = found != NULL && Z_TYPE_P(found) != IS_NULL;
		return true;
	}

	zv::Val getScope() const
	{
		zval *expressionResult = pt_typed_slot(self, slots::expressionResult, self->ce, "expressionResult");
		if (UNEXPECTED(expressionResult == NULL)) return zv::Val();
		zv::Val hold;
		return ptav::own(pt_expression_result_scope(expressionResult, hold));
	}

	/* false = pending exception */
	[[nodiscard]] bool hasYield(bool &out) const
	{
		zval *expressionResult = pt_typed_slot(self, slots::expressionResult, self->ce, "expressionResult");
		return expressionResult != NULL && pt_expression_result_has_yield(expressionResult, out);
	}

	/* false = pending exception */
	[[nodiscard]] bool isAlwaysTerminating(bool &out) const
	{
		zval *expressionResult = pt_typed_slot(self, slots::expressionResult, self->ce, "expressionResult");
		return expressionResult != NULL && pt_expression_result_is_always_terminating(expressionResult, out);
	}

	zv::Val getThrowPoints() const
	{
		zval *expressionResult = pt_typed_slot(self, slots::expressionResult, self->ce, "expressionResult");
		if (UNEXPECTED(expressionResult == NULL)) return zv::Val();
		zv::Val hold;
		return ptav::own(pt_expression_result_throw_points(expressionResult, hold));
	}

	zv::Val getImpurePoints() const
	{
		zval *expressionResult = pt_typed_slot(self, slots::expressionResult, self->ce, "expressionResult");
		if (UNEXPECTED(expressionResult == NULL)) return zv::Val();
		zv::Val hold;
		return ptav::own(pt_expression_result_impure_points(expressionResult, hold));
	}

	/* Mirrors withResolvedParametersAcceptor(): clone, then the slot;
	 * $resolvedParametersAcceptor NULL for null */
	zv::Val withResolvedParametersAcceptor(zval *resolvedParametersAcceptor) const
	{
		zend_object *clone = self->handlers->clone_obj(self);
		if (UNEXPECTED(EG(exception))) {
			if (clone != NULL) {
				OBJ_RELEASE(clone);
			}
			return zv::Val();
		}
		zval cloneZv;
		ZVAL_OBJ(&cloneZv, clone);
		zv::Val result = zv::Val::adopt(cloneZv);
		zval value = {};
		if (resolvedParametersAcceptor != NULL) {
			ZVAL_COPY_VALUE(&value, resolvedParametersAcceptor);
		} else {
			ZVAL_NULL(&value);
		}
		pt_write_slot(clone, slots::resolvedParametersAcceptor, &value);

		return result;
	}

	zv::Val getResolvedParametersAcceptor() const { return read(slots::resolvedParametersAcceptor, "resolvedParametersAcceptor"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArgsResult;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_args_result_new(zval *expressionResult, zval *resolvedParametersAcceptor, zval *argResults, zval *byRefArguments)
{
	return ArgsResult::create(expressionResult, resolvedParametersAcceptor != NULL && Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL ? NULL : resolvedParametersAcceptor, argResults, byRefArguments);
}

zv::Val pt_args_result_with_resolved_parameters_acceptor(zval *argsResult, zval *resolvedParametersAcceptor)
{
	if (resolvedParametersAcceptor != NULL && Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL) resolvedParametersAcceptor = NULL;
	if (EXPECTED(Z_OBJCE_P(argsResult) == pt_ce_args_result)) return ArgsResult(Z_OBJ_P(argsResult)).withResolvedParametersAcceptor(resolvedParametersAcceptor);
	zval null;
	ZVAL_NULL(&null);
	return pt_type_call(Z_OBJ_P(argsResult), PT_LC("withresolvedparametersacceptor"), 1, resolvedParametersAcceptor != NULL ? resolvedParametersAcceptor : &null);
}

zv::Val pt_args_result_require_arg_result(zval *argsResult, zval *argValue)
{
	if (EXPECTED(Z_OBJCE_P(argsResult) == pt_ce_args_result)) return ArgsResult(Z_OBJ_P(argsResult)).requireArgResult(argValue);
	return pt_type_call(Z_OBJ_P(argsResult), PT_LC("requireargresult"), 1, argValue);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_args_result)
{
	reg::Class cls("PHPStan\\Analyser\\ArgsResult");
	ptdecl::ArgsResult::declareClass(cls);
	ptdecl::ArgsResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResult, *resolvedParametersAcceptor, *argResults, *byRefArguments = NULL;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::Arr, zp::Opt<zp::Arr>>(execute_data, expressionResult, resolvedParametersAcceptor, argResults, byRefArguments)) RETURN_THROWS();
		ArgsResult(Z_OBJ_P(ZEND_THIS)).construct(expressionResult, resolvedParametersAcceptor, argResults, byRefArguments);
	});

	cls.method<&ArgsResult::findArgResult, zp::Obj>(sigs::findArgResult);

	cls.method<&ArgsResult::getArgResults>(sigs::getArgResults);

	cls.method<&ArgsResult::requireArgResult, zp::Obj>(sigs::requireArgResult);

	cls.method<&ArgsResult::isPassedByReference, zp::Obj>(sigs::isPassedByReference);

	cls.method<&ArgsResult::getScope>(sigs::getScope);

	cls.method<&ArgsResult::hasYield>(sigs::hasYield);

	cls.method<&ArgsResult::isAlwaysTerminating>(sigs::isAlwaysTerminating);

	cls.method<&ArgsResult::getThrowPoints>(sigs::getThrowPoints);

	cls.method<&ArgsResult::getImpurePoints>(sigs::getImpurePoints);

	cls.method<&ArgsResult::withResolvedParametersAcceptor, zp::ObjOrNull>(sigs::withResolvedParametersAcceptor);

	cls.method<&ArgsResult::getResolvedParametersAcceptor>(sigs::getResolvedParametersAcceptor);

	cls.shadow(&pt_ce_args_result);
}

/* }}} */
