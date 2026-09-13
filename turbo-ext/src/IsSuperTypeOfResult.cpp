/*
 * PHPStanTurbo\IsSuperTypeOfResult — native implementation of
 * PHPStan\Type\IsSuperTypeOfResult.
 *
 * Declared as PHPStan\Type\IsSuperTypeOfResult itself at activation (final,
 * like the twin); every instance — the three singletons included — is of
 * that class. State lives in the declared public
 * readonly property slots ($result, $reasons, $lazyReasons), which PHP code
 * reads directly; the std object handlers do GC and freeing.
 *
 * The reasons merging, the ->result trinary folds and the readonly-slot
 * helpers are the pt_result_* family shared with AcceptsResult.cpp, which
 * hosts them.
 *
 * decorateReasons() wraps each lazy reason in a closure
 * (`static fn (): string => $cb($lazyReason())` in the twin): natively that
 * is a real Closure over the __invoke() of the small PHPStanTurbo\
 * DecoratedLazyReason holder registered here, so $lazyReasons keeps holding
 * Closure instances.
 */

#include "support.h"
#include "generated/IsSuperTypeOfResult.h"

namespace slots = ptdecl::IsSuperTypeOfResult::slot;
#include "zv.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_closures.h" /* zend_ce_closure, zend_create_closure */
#pragma GCC diagnostic pop

/* {{{ provided by AcceptsResult.cpp (declarations to be hosted in support.h) */

#define PT_RESULT_PROP_RESULT 0
#define PT_RESULT_PROP_REASONS 1

extern zend_class_entry *pt_ce_accepts_result;

void pt_readonly_slot_init(zend_object *object, uint32_t slot, zval *owned);
bool pt_readonly_construct_guard(zend_object *object, const char *propertyName);
zend_long pt_result_value(zend_object *object);
zval *pt_result_array_slot(zend_object *object, uint32_t slot, const char *propertyName);
zend_long pt_result_and(zend_long self, zval *operands, uint32_t count);
zend_long pt_result_or(zend_long self, zval *operands, uint32_t count);
zend_long pt_result_extreme_identity(zval *operands, uint32_t count);
zend_long pt_result_max_min(zval *operands, uint32_t count);
zend_long pt_trinary_negate_value(zend_long value);
const char *pt_trinary_describe_value(zend_long value);
bool pt_result_object_create(zval *out, zend_class_entry *ce, zval *trinary, zval *reasons, zval *lazyReasons);
bool pt_reasons_merge(zval *result, zval *const *arrays, uint32_t count, bool mergeKeys, bool unique);
bool pt_reasons_merge_operands(zval *result, zend_object *self, zval *operands, uint32_t count, uint32_t slot, bool mergeKeys, bool unique);
bool pt_call_fci(zend_fcall_info *fci, zend_fcall_info_cache *fcc, uint32_t argc, zval *argv, zval *retval);
bool pt_accepts_result_create(zval *out, zval *trinary, zval *reasons);
bool pt_reasons_decorate(zval *out, zval *reasons, zend_fcall_info *fci, zend_fcall_info_cache *fcc);

/* }}} */

zend_class_entry *pt_ce_is_super_type_of_result = nullptr;

/* the decorateReasons() lazy-reason holder and its __invoke(), the function
 * the wrapping closures are created over */
static zend_class_entry *pt_ce_decorated_lazy_reason = nullptr;
static zend_function *pt_decorated_lazy_reason_invoke = nullptr;

/* Calls a stored callable value (a lazy reason closure, a decorateReasons()
 * callback); false = pending exception (*retval is then released). */
[[nodiscard]] static bool pt_call_stored_callable(zval *callable, uint32_t argc, zval *argv, zval *retval)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	char *error = NULL;
	if (UNEXPECTED(zend_fcall_info_init(callable, 0, &fci, &fcc, NULL, &error) != SUCCESS)) {
		zend_throw_error(NULL, "Value of type %s is not callable", zend_zval_value_name(callable));
		if (error != NULL) {
			efree(error);
		}
		return false;
	}
	if (error != NULL) {
		efree(error);
	}
	return pt_call_fci(&fci, &fcc, argc, argv, retval);
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\IsSuperTypeOfResult. State lives in the PHP object's
 * $result/$reasons/$lazyReasons slots. */
class IsSuperTypeOfResult
{
public:
	explicit IsSuperTypeOfResult(zend_object *self) : self(self) {}

	/* lazyReasons NULL = the default []; false = pending exception */
	[[nodiscard]] bool construct(zval *result, zval *reasons, zval *lazyReasons)
	{
		if (UNEXPECTED(!pt_readonly_construct_guard(self, "result"))) return false;
		zval copy;
		ZVAL_COPY(&copy, result);
		pt_readonly_slot_init(self, PT_RESULT_PROP_RESULT, &copy);
		ZVAL_COPY(&copy, reasons);
		pt_readonly_slot_init(self, PT_RESULT_PROP_REASONS, &copy);
		if (lazyReasons != NULL) {
			ZVAL_COPY(&copy, lazyReasons);
		} else {
			ZVAL_EMPTY_ARRAY(&copy);
		}
		pt_readonly_slot_init(self, slots::lazyReasons, &copy);
		return true;
	}

	/* -1 = pending exception */
	[[nodiscard]] zend_long resultValue() const { return pt_result_value(self); }

	/* All reasons with the lazy ones materialized: array_values(array_unique(
	 * array_merge($reasons, array_map(fn (Closure $cb): string => $cb(),
	 * $lazyReasons)))); no caching, the twin has none. UNDEF = pending
	 * exception. */
	zv::Val getReasons() const
	{
		zval *reasons = pt_result_array_slot(self, PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(reasons == NULL)) return zv::Val();
		zval *lazyReasons = pt_result_array_slot(self, slots::lazyReasons, "lazyReasons");
		if (UNEXPECTED(lazyReasons == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(lazyReasons)) == 0) return zv::Val::copyOf(zv::Ref(reasons));
		zv::Val materialized = materializeLazyReasons(zv::ArrRef(lazyReasons));
		if (UNEXPECTED(materialized.isUndef())) return zv::Val();
		zval *inputs[2] = { reasons, materialized.raw() };
		zval merged;
		if (UNEXPECTED(!pt_reasons_merge(&merged, inputs, 2, true, true))) return zv::Val();
		return zv::Val::adopt(merged);
	}

	static zv::Val createYes() { return singleton(PT_TRI_YES); }

	/* reasons/lazyReasons NULL = the default []; a no-reasons result is the
	 * singleton */
	static zv::Val createNo(zval *reasons, zval *lazyReasons)
	{
		bool noReasons = reasons == NULL || zend_hash_num_elements(Z_ARRVAL_P(reasons)) == 0;
		bool noLazyReasons = lazyReasons == NULL || zend_hash_num_elements(Z_ARRVAL_P(lazyReasons)) == 0;
		if (noReasons && noLazyReasons) return singleton(PT_TRI_NO);
		return create(pt_trinary_singleton(PT_TRI_NO), copyOrEmpty(reasons), copyOrEmpty(lazyReasons));
	}

	static zv::Val createMaybe() { return singleton(PT_TRI_MAYBE); }

	static zv::Val createFromBoolean(bool value) { return value ? createYes() : createNo(NULL, NULL); }

	/* new AcceptsResult($this->result, $this->getReasons()) — always a fresh
	 * instance; UNDEF = pending exception */
	zv::Val toAcceptsResult() const
	{
		if (UNEXPECTED(resultValue() < 0)) return zv::Val();
		zv::Val reasons = getReasons();
		if (UNEXPECTED(reasons.isUndef())) return zv::Val();
		zval out;
		zval reasonsRaw = reasons.take();
		if (UNEXPECTED(!pt_accepts_result_create(&out, OBJ_PROP_NUM(self, PT_RESULT_PROP_RESULT), &reasonsRaw))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* and() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val and_(zval *others, uint32_t count) const { return combine(others, count, true); }

	/* or() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val or_(zval *others, uint32_t count) const { return combine(others, count, false); }

	/* $cb($reason) for every reason, and every lazy reason wrapped in
	 * `static fn (): string => $cb($lazyReason())`; UNDEF = pending exception */
	zv::Val decorateReasons(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *reasons = pt_result_array_slot(self, PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(reasons == NULL)) return zv::Val();
		zval decoratedRaw;
		if (UNEXPECTED(!pt_reasons_decorate(&decoratedRaw, reasons, fci, fcc))) return zv::Val();
		zv::Val decorated = zv::Val::adopt(decoratedRaw);

		zval *lazyReasons = pt_result_array_slot(self, slots::lazyReasons, "lazyReasons");
		if (UNEXPECTED(lazyReasons == NULL)) return zv::Val();
		zv::Val decoratedLazy = wrapLazyReasons(zv::ArrRef(lazyReasons), zv::Ref(&fci->function_name));
		if (UNEXPECTED(decoratedLazy.isUndef())) return zv::Val();

		if (UNEXPECTED(resultValue() < 0)) return zv::Val();
		return create(OBJ_PROP_NUM(self, PT_RESULT_PROP_RESULT), std::move(decorated), std::move(decoratedLazy));
	}

	/* count >= 1 (the glue throws for none); UNDEF = pending exception */
	static zv::Val extremeIdentity(zval *operands, uint32_t count)
	{
		return fromOperands(pt_result_extreme_identity(operands, count), operands, count);
	}

	/* count >= 1 (the glue throws for none); UNDEF = pending exception */
	static zv::Val maxMin(zval *operands, uint32_t count)
	{
		return fromOperands(pt_result_max_min(operands, count), operands, count);
	}

	/* UNDEF = pending exception */
	static zv::Val lazyMaxMin(zv::ArrRef objects, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		zv::Arr collected;
		bool hasNo = false;
		for (auto entry : objects) {
			zval arg, callbackResult;
			ZVAL_COPY_VALUE(&arg, entry.value().deref().raw());
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &callbackResult))) return zv::Val();
			if (UNEXPECTED(Z_TYPE(callbackResult) != IS_OBJECT || !instanceof_function(Z_OBJCE(callbackResult), pt_ce_is_super_type_of_result))) {
				zval_ptr_dtor(&callbackResult);
				zend_type_error("Return value of the callback must be of type %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
				return zv::Val();
			}
			zv::Val isSuperTypeOf = zv::Val::adopt(callbackResult);
			zend_long value = pt_result_value(zv::Ref(isSuperTypeOf.raw()).asObject());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) return isSuperTypeOf;
			if (value == PT_TRI_NO) {
				hasNo = true;
			}
			if (collected.isUndef()) {
				collected = zv::Arr::create(objects.size());
			}
			collected.push(std::move(isSuperTypeOf));
		}

		zval reasons, lazyReasons;
		if (collected.isUndef()) {
			ZVAL_EMPTY_ARRAY(&reasons);
			ZVAL_EMPTY_ARRAY(&lazyReasons);
		} else {
			/* built by pushes alone, so the slots are contiguous */
			HashTable *ht = collected.table();
			ZEND_ASSERT(HT_IS_PACKED(ht));
			uint32_t count = zend_hash_num_elements(ht);
			if (UNEXPECTED(!pt_reasons_merge_operands(&reasons, NULL, ht->arPacked, count, PT_RESULT_PROP_REASONS, false, true))) return zv::Val();
			if (UNEXPECTED(!pt_reasons_merge_operands(&lazyReasons, NULL, ht->arPacked, count, slots::lazyReasons, false, false))) {
				zval_ptr_dtor(&reasons);
				return zv::Val();
			}
		}
		/* new self(...) — a fresh instance, not the singleton, like the twin */
		return create(pt_trinary_singleton(hasNo ? PT_TRI_NO : PT_TRI_MAYBE), zv::Val::adopt(reasons), zv::Val::adopt(lazyReasons));
	}

	/* UNDEF = pending exception */
	zv::Val negate() const
	{
		zend_long value = resultValue();
		if (UNEXPECTED(value < 0)) return zv::Val();
		zval *reasons = pt_result_array_slot(self, PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(reasons == NULL)) return zv::Val();
		zval *lazyReasons = pt_result_array_slot(self, slots::lazyReasons, "lazyReasons");
		if (UNEXPECTED(lazyReasons == NULL)) return zv::Val();
		return create(pt_trinary_singleton(pt_trinary_negate_value(value)), zv::Val::copyOf(zv::Ref(reasons)), zv::Val::copyOf(zv::Ref(lazyReasons)));
	}

	/* NULL = pending exception */
	const char *describe() const
	{
		zend_long value = resultValue();
		if (UNEXPECTED(value < 0)) return NULL;
		return pt_trinary_describe_value(value);
	}

	static void rinit()
	{
		ZVAL_UNDEF(&singletons[0]);
		ZVAL_UNDEF(&singletons[1]);
		ZVAL_UNDEF(&singletons[2]);
	}

	static void rshutdown()
	{
		for (zval &singleton : singletons) {
			if (!Z_ISUNDEF(singleton)) {
				zval_ptr_dtor(&singleton);
				ZVAL_UNDEF(&singleton);
			}
		}
	}

	/* PHPStanTurbo\DecoratedLazyReason::__invoke(): $cb($lazyReason()),
	 * checked to return a string like the twin's `: string` arrow function
	 * (strict_types, no coercion) */
	static void ZEND_FASTCALL invokeDecoratedLazyReason(INTERNAL_FUNCTION_PARAMETERS)
	{
		ZEND_PARSE_PARAMETERS_NONE();
		if (UNEXPECTED(Z_TYPE_P(ZEND_THIS) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: decorated lazy reason called without its holder");
			RETURN_THROWS();
		}
		zend_object *holder = Z_OBJ_P(ZEND_THIS);
		zval inner;
		if (UNEXPECTED(!pt_call_stored_callable(OBJ_PROP_NUM(holder, slots::reasons), 0, NULL, &inner))) RETURN_THROWS();
		zval outer;
		bool ok = pt_call_stored_callable(OBJ_PROP_NUM(holder, slots::result), 1, &inner, &outer);
		zval_ptr_dtor(&inner);
		if (UNEXPECTED(!ok)) RETURN_THROWS();
		if (UNEXPECTED(Z_TYPE(outer) != IS_STRING)) {
			zend_type_error("%s::{closure}(): Return value must be of type string, %s returned", ZSTR_VAL(pt_ce_is_super_type_of_result->name), zend_zval_value_name(&outer));
			zval_ptr_dtor(&outer);
			RETURN_THROWS();
		}
		RETURN_COPY_VALUE(&outer);
	}

private:
	zend_object *self;

	static zval singletons[3]; /* yes, maybe, no */

	static zval *singletonSlot(zend_long value)
	{
		if (value == PT_TRI_YES) return &singletons[0];
		if (value == PT_TRI_MAYBE) return &singletons[1];
		return &singletons[2];
	}

	/* the per-request $YES/$MAYBE/$NO singletons, created on first use */
	static zv::Val singleton(zend_long value)
	{
		zval *slot = singletonSlot(value);
		if (UNEXPECTED(Z_ISUNDEF_P(slot))) {
			zv::Val created = create(pt_trinary_singleton(value), zv::Val(zv::Arr::empty()), zv::Val(zv::Arr::empty()));
			if (UNEXPECTED(created.isUndef())) return zv::Val();
			*slot = created.take();
		}
		return zv::Val::copyOf(zv::Ref(slot));
	}

	/* new self($trinary, $reasons, $lazyReasons); the arrays are consumed;
	 * UNDEF = pending exception */
	static zv::Val create(zval *trinary, zv::Val reasons, zv::Val lazyReasons)
	{
		zend_class_entry *ce = pt_ce_is_super_type_of_result;
		if (UNEXPECTED(ce == NULL)) return zv::Val();
		zval out;
		zval reasonsRaw = reasons.take();
		zval lazyReasonsRaw = lazyReasons.take();
		if (UNEXPECTED(!pt_result_object_create(&out, ce, trinary, &reasonsRaw, &lazyReasonsRaw))) return zv::Val();
		return zv::Val::adopt(out);
	}

	static zv::Val copyOrEmpty(zval *array)
	{
		if (array == NULL) return zv::Val(zv::Arr::empty());
		return zv::Val::copyOf(zv::Ref(array));
	}

	/* and()/or(): the trinary fold, the merged and deduplicated reasons, the
	 * merged lazy reasons */
	zv::Val combine(zval *others, uint32_t count, bool isAnd) const
	{
		zend_long value = resultValue();
		if (UNEXPECTED(value < 0)) return zv::Val();
		zend_long folded = isAnd ? pt_result_and(value, others, count) : pt_result_or(value, others, count);
		if (UNEXPECTED(folded < 0)) return zv::Val();
		zval reasons;
		if (UNEXPECTED(!pt_reasons_merge_operands(&reasons, self, others, count, PT_RESULT_PROP_REASONS, true, true))) return zv::Val();
		zval lazyReasons;
		if (UNEXPECTED(!pt_reasons_merge_operands(&lazyReasons, self, others, count, slots::lazyReasons, true, false))) {
			zval_ptr_dtor(&reasons);
			return zv::Val();
		}
		return create(pt_trinary_singleton(folded), zv::Val::adopt(reasons), zv::Val::adopt(lazyReasons));
	}

	/* extremeIdentity()/maxMin(): a folded value plus the operands' reasons
	 * collected and deduplicated, and their lazy reasons collected */
	static zv::Val fromOperands(zend_long folded, zval *operands, uint32_t count)
	{
		if (UNEXPECTED(folded < 0)) return zv::Val();
		zval reasons;
		if (UNEXPECTED(!pt_reasons_merge_operands(&reasons, NULL, operands, count, PT_RESULT_PROP_REASONS, false, true))) return zv::Val();
		zval lazyReasons;
		if (UNEXPECTED(!pt_reasons_merge_operands(&lazyReasons, NULL, operands, count, slots::lazyReasons, false, false))) {
			zval_ptr_dtor(&reasons);
			return zv::Val();
		}
		return create(pt_trinary_singleton(folded), zv::Val::adopt(reasons), zv::Val::adopt(lazyReasons));
	}

	/* array_map(static fn (Closure $cb): string => $cb(), $lazyReasons):
	 * every closure invoked in order, keys preserved; the parameter and
	 * return types are checked like the twin's (strict_types, no coercion).
	 * UNDEF = pending exception. */
	static zv::Val materializeLazyReasons(zv::ArrRef lazyReasons)
	{
		zv::Arr mapped = zv::Arr::create(lazyReasons.size());
		for (auto entry : lazyReasons) {
			zv::Ref cb = entry.value().deref();
			if (UNEXPECTED(!cb.instanceOf(zend_ce_closure))) {
				zend_type_error("%s::{closure}(): Argument #1 ($cb) must be of type Closure, %s given", ZSTR_VAL(pt_ce_is_super_type_of_result->name), zend_zval_value_name(cb.raw()));
				return zv::Val();
			}
			zval reason;
			if (UNEXPECTED(!pt_call_stored_callable(cb.raw(), 0, NULL, &reason))) return zv::Val();
			if (UNEXPECTED(Z_TYPE(reason) != IS_STRING)) {
				zend_type_error("%s::{closure}(): Return value must be of type string, %s returned", ZSTR_VAL(pt_ce_is_super_type_of_result->name), zend_zval_value_name(&reason));
				zval_ptr_dtor(&reason);
				return zv::Val();
			}
			/* key-preserving insert into the fresh table — raw form, the
			 * zv::Arr setters spell symtable/next-index semantics */
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				zend_hash_add_new(mapped.table(), key, &reason);
			} else {
				zend_hash_index_add_new(mapped.table(), entry.indexKey(), &reason);
			}
		}
		return zv::Val(std::move(mapped));
	}

	/* `static fn (): string => $cb($lazyReason())` per lazy reason: a Closure
	 * over DecoratedLazyReason::__invoke() bound to a holder carrying the two
	 * callables; UNDEF = pending exception */
	static zv::Val wrapLazyReasons(zv::ArrRef lazyReasons, zv::Ref cb)
	{
		if (lazyReasons.size() == 0) return zv::Val(zv::Arr::empty());
		zv::Arr wrapped = zv::Arr::create(lazyReasons.size());
		for (auto entry : lazyReasons) {
			zval holder;
			object_init_ex(&holder, pt_ce_decorated_lazy_reason);
			zv::ObjRef holderRef(&holder);
			holderRef.propAtWrite(slots::result, zv::Val::copyOf(cb));
			holderRef.propAtWrite(slots::reasons, zv::Val::copyOf(entry.value().deref()));
			zval closure;
#if PHP_VERSION_ID >= 80600
			/* php-src fbb2e1f23d6: $this is passed as zend_object* from 8.6 on */
			zend_create_closure(&closure, pt_decorated_lazy_reason_invoke, pt_ce_decorated_lazy_reason, pt_ce_decorated_lazy_reason, Z_OBJ(holder));
#else
			zend_create_closure(&closure, pt_decorated_lazy_reason_invoke, pt_ce_decorated_lazy_reason, pt_ce_decorated_lazy_reason, &holder);
#endif
			zval_ptr_dtor(&holder); /* the closure holds its own reference */
			wrapped.push(zv::Val::adopt(closure));
		}
		return zv::Val(std::move(wrapped));
	}
};

zval IsSuperTypeOfResult::singletons[3];

} // namespace phpstanturbo

using phpstanturbo::IsSuperTypeOfResult;

/* the per-request singleton for a PT_TRI_* value (createYes()/createMaybe()/
 * createNo()); owned copy in *out, false = pending exception */
[[nodiscard]] bool pt_is_super_type_of_result_singleton(zval *out, zend_long value)
{
	zv::Val result = value == PT_TRI_YES ? IsSuperTypeOfResult::createYes()
		: value == PT_TRI_MAYBE ? IsSuperTypeOfResult::createMaybe()
		: IsSuperTypeOfResult::createNo(NULL, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	*out = result.take();
	return true;
}

void pt_is_super_type_of_result_rinit()
{
	IsSuperTypeOfResult::rinit();
}

void pt_is_super_type_of_result_rshutdown()
{
	IsSuperTypeOfResult::rshutdown();
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define IS_SUPER_TYPE_OF_RESULT_CLASS "PHPStanTurbo\\IsSuperTypeOfResult"
#define TRINARY_CLASS "PHPStanTurbo\\TrinaryLogic"

static zend_result pt_verify_is_super_type_of_result_variadic(zval *args, uint32_t count, uint32_t offset)
{
	for (uint32_t i = 0; i < count; i++) {
		if (UNEXPECTED(!zv::Ref(&args[i]).deref().instanceOf(pt_ce_is_super_type_of_result))) {
			zend_argument_type_error(offset + i, "must be of type %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return FAILURE;
		}
	}
	return SUCCESS;
}

static void pt_is_super_type_of_result_and_or(INTERNAL_FUNCTION_PARAMETERS, bool isAnd)
{
	zval *others = NULL;
	uint32_t count = 0;

	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_VARIADIC('+', others, count)
	ZEND_PARSE_PARAMETERS_END();

	if (UNEXPECTED(pt_verify_is_super_type_of_result_variadic(others, count, 1) != SUCCESS)) RETURN_THROWS();

	IsSuperTypeOfResult self(Z_OBJ_P(ZEND_THIS));
	zv::Val result = isAnd ? self.and_(others, count) : self.or_(others, count);
	if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
	result.intoReturnValue(return_value);
}

static void pt_is_super_type_of_result_variadic_op(INTERNAL_FUNCTION_PARAMETERS, bool extremeIdentity)
{
	zval *operands = NULL;
	uint32_t count = 0;

	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_VARIADIC('+', operands, count)
	ZEND_PARSE_PARAMETERS_END();

	if (UNEXPECTED(count == 0)) {
		pt_throw_should_not_happen();
		RETURN_THROWS();
	}
	if (UNEXPECTED(pt_verify_is_super_type_of_result_variadic(operands, count, 1) != SUCCESS)) RETURN_THROWS();

	zv::Val result = extremeIdentity ? IsSuperTypeOfResult::extremeIdentity(operands, count) : IsSuperTypeOfResult::maxMin(operands, count);
	if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
	result.intoReturnValue(return_value);
}

static void pt_is_super_type_of_result_bool(INTERNAL_FUNCTION_PARAMETERS, zend_long expected)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_long value = IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).resultValue();
	if (UNEXPECTED(value < 0)) RETURN_THROWS();
	RETURN_BOOL(value == expected);
}

void pt_register_is_super_type_of_result()
{
	reg::Class cls("PHPStan\\Type\\IsSuperTypeOfResult");
	ptdecl::IsSuperTypeOfResult::declareClass(cls);

	cls.method("__construct", reg::Public, 2, { reg::obj("result", TRINARY_CLASS), reg::arrayArg("reasons"), reg::withDefault(reg::arrayArg("lazyReasons"), "[]") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *result, *reasons;
		zval *lazyReasons = NULL;
		ZEND_PARSE_PARAMETERS_START(2, 3)
			Z_PARAM_OBJECT_OF_CLASS(result, pt_ce_trinary)
			Z_PARAM_ARRAY(reasons)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(lazyReasons)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).construct(result, reasons, lazyReasons))) RETURN_THROWS();
	});

	cls.method("yes", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_bool(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_TRI_YES);
	});

	cls.method("maybe", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_bool(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_TRI_MAYBE);
	});

	cls.method("no", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_bool(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_TRI_NO);
	});

	cls.method("getReasons", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).getReasons();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createYes", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = IsSuperTypeOfResult::createYes();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createNo", reg::PublicStatic, 0, { reg::withDefault(reg::arrayArg("reasons"), "[]"), reg::withDefault(reg::arrayArg("lazyReasons"), "[]") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reasons = NULL, *lazyReasons = NULL;
		if (!zp::parse<zp::Opt<zp::Arr>, zp::Opt<zp::Arr>>(execute_data, reasons, lazyReasons)) RETURN_THROWS();
		zv::Val result = IsSuperTypeOfResult::createNo(reasons, lazyReasons);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createMaybe", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = IsSuperTypeOfResult::createMaybe();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createFromBoolean", reg::PublicStatic, 1, { reg::boolArg("value") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool value;
		if (!zp::parse<zp::Bool>(execute_data, value)) RETURN_THROWS();
		zv::Val result = IsSuperTypeOfResult::createFromBoolean(value);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("toAcceptsResult", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).toAcceptsResult();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("and", reg::Public, 0, { reg::variadicObj("others", IS_SUPER_TYPE_OF_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_and_or(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method("or", reg::Public, 0, { reg::variadicObj("others", IS_SUPER_TYPE_OF_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_and_or(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method("decorateReasons", reg::Public, 1, { reg::callableArg("cb") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).decorateReasons(&fci, &fcc);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("extremeIdentity", reg::PublicStatic, 0, { reg::variadicObj("operands", IS_SUPER_TYPE_OF_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_variadic_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method("maxMin", reg::PublicStatic, 0, { reg::variadicObj("operands", IS_SUPER_TYPE_OF_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_is_super_type_of_result_variadic_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method("lazyMaxMin", reg::PublicStatic, 2, { reg::arrayArg("objects"), reg::callableArg("callback") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objects;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY(objects)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = IsSuperTypeOfResult::lazyMaxMin(zv::ArrRef(objects), &fci, &fcc);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("negate", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).negate();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("describe", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		const char *described = IsSuperTypeOfResult(Z_OBJ_P(ZEND_THIS)).describe();
		if (UNEXPECTED(described == NULL)) RETURN_THROWS();
		RETURN_STRING(described);
	});

	cls.publicReadonlyProperty("result", MAY_BE_OBJECT);
	cls.publicReadonlyProperty("reasons", MAY_BE_ARRAY);
	cls.publicReadonlyProperty("lazyReasons", MAY_BE_ARRAY);
	cls.shadow(&pt_ce_is_super_type_of_result);

	/* the decorateReasons() lazy-reason holder: an internal detail with no PHP
	 * twin (the twin's arrow function closes over $cb and $lazyReason; here
	 * a Closure over __invoke() bound to a holder object does the same).
	 * Registered under a builder name other than `cls` on purpose: the
	 * side-by-side parity scan pairs `cls.method(...)` lines with the twin's
	 * public methods, and __invoke() has none. cb/lazyReason must stay in
	 * this order (PT_DLR_PROP_* slots). */
	reg::Class holder("PHPStanTurbo\\DecoratedLazyReason");
	holder.privateNullProperty("cb");
	holder.privateNullProperty("lazyReason");
	holder.method("__invoke", reg::Public, 0, {}, IsSuperTypeOfResult::invokeDecoratedLazyReason);
	pt_ce_decorated_lazy_reason = holder.register_();
	pt_ce_decorated_lazy_reason->ce_flags |= ZEND_ACC_FINAL;
	pt_decorated_lazy_reason_invoke = (zend_function *) zend_hash_str_find_ptr(&pt_ce_decorated_lazy_reason->function_table, "__invoke", sizeof("__invoke") - 1);
	ZEND_ASSERT(pt_decorated_lazy_reason_invoke != NULL);
}

/* }}} */
