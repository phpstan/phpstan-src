/*
 * PHPStanTurbo\ScopeContext — native implementation of
 * PHPStan\Analyser\ScopeContext.
 *
 * The PHP twin's constructor is private; instances come only from
 * create()/beginFile()/enterClass()/enterTrait().
 *
 * State lives in the three declared private property slots, so the standard
 * object handlers do GC/free/clone — no custom object struct.
 *
 * The file also carries pt_scope_is_in_class() / pt_scope_get_class_reflection(),
 * the native readers of MutatingScope::isInClass()/getClassReflection(): both
 * answer out of a native ScopeContext's $classReflection slot, so they live
 * next to the slot readers above (see the block at the end of the file).
 */

#include "support.h"
#include "generated/ScopeContext.h"

namespace slots = ptdecl::ScopeContext::slot;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_scope_context = nullptr;

zval *pt_scope_context_class_reflection(zend_object *context)
{
	return OBJ_PROP_NUM(context, slots::classReflection);
}

zval *pt_scope_context_file(zend_object *context)
{
	return OBJ_PROP_NUM(context, slots::file);
}

zval *pt_scope_context_trait_reflection(zend_object *context)
{
	return OBJ_PROP_NUM(context, slots::traitReflection);
}

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ScopeContext. State lives in the PHP object's
 * file/classReflection/traitReflection properties. */
class ScopeContext
{
public:
	explicit ScopeContext(zval *self) : self(self) {}

	/* the private constructor's body: $this->file/... = the arguments */
	void construct(zv::Ref file, zv::Ref classReflection, zv::Ref traitReflection)
	{
		zv::ObjRef obj(self);
		obj.propAtWrite(slots::file, zv::Val::copyOf(file));
		obj.propAtWrite(slots::classReflection, zv::Val::copyOf(classReflection));
		obj.propAtWrite(slots::traitReflection, zv::Val::copyOf(traitReflection));
	}

	/* UNDEF = pending exception */
	static zv::Val create(zv::Ref file)
	{
		zval null;
		ZVAL_NULL(&null);
		return newSelf(file, zv::Ref(&null), zv::Ref(&null));
	}

	/* UNDEF = pending exception */
	zv::Val beginFile() const
	{
		zval null;
		ZVAL_NULL(&null);
		return newSelf(file(), zv::Ref(&null), zv::Ref(&null));
	}

	/* UNDEF = pending exception (ShouldNotHappenException on a non-anonymous
	 * class inside a class, or on a trait) */
	zv::Val enterClass(zv::Ref classReflection) const
	{
		/* the isAnonymous() call happens only when a class is already
		 * entered — the PHP twin's && short-circuits the same way */
		if (!this->classReflection().isNull()) {
			bool isAnonymous;
			if (UNEXPECTED(!callBool(classReflection, "isanonymous", sizeof("isanonymous") - 1, isAnonymous))) return zv::Val();
			if (!isAnonymous) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
		}
		bool isTrait;
		if (UNEXPECTED(!callBool(classReflection, "istrait", sizeof("istrait") - 1, isTrait))) return zv::Val();
		if (isTrait) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval null;
		ZVAL_NULL(&null);
		return newSelf(file(), classReflection, zv::Ref(&null));
	}

	/* UNDEF = pending exception (ShouldNotHappenException outside a class,
	 * or on a non-trait) */
	zv::Val enterTrait(zv::Ref traitReflection) const
	{
		if (this->classReflection().isNull()) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		bool isTrait;
		if (UNEXPECTED(!callBool(traitReflection, "istrait", sizeof("istrait") - 1, isTrait))) return zv::Val();
		if (!isTrait) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		return newSelf(file(), this->classReflection(), traitReflection);
	}

	/* false = pending exception (from a getName() call). Reads the slots the
	 * twin reaches through getClassReflection()/getTraitReflection() */
	[[nodiscard]] bool equals(const ScopeContext &otherContext, bool &out) const
	{
		if (!zend_string_equals(file().asString(), otherContext.file().asString())) {
			out = false;
			return true;
		}

		if (classReflection().isNull()) {
			out = otherContext.classReflection().isNull();
			return true;
		} else if (otherContext.classReflection().isNull()) {
			out = false;
			return true;
		}

		bool isSameClass;
		if (UNEXPECTED(!sameName(classReflection(), otherContext.classReflection(), isSameClass))) return false;

		if (traitReflection().isNull()) {
			out = otherContext.traitReflection().isNull() && isSameClass;
			return true;
		} else if (otherContext.traitReflection().isNull()) {
			out = false;
			return true;
		}

		bool isSameTrait;
		if (UNEXPECTED(!sameName(traitReflection(), otherContext.traitReflection(), isSameTrait))) return false;

		out = isSameClass && isSameTrait;
		return true;
	}

	zv::Val getFile() const { return zv::Val::copyOf(file()); }
	zv::Val getClassReflection() const { return zv::Val::copyOf(classReflection()); }
	zv::Val getTraitReflection() const { return zv::Val::copyOf(traitReflection()); }

private:
	zval *self;

	zv::Ref file() const { return zv::ObjRef(self).propAt(slots::file); }
	zv::Ref classReflection() const { return zv::ObjRef(self).propAt(slots::classReflection); }
	zv::Ref traitReflection() const { return zv::ObjRef(self).propAt(slots::traitReflection); }

	/* new self(...): instantiates the class and fills the slots directly, as
	 * the private PHP constructor would; UNDEF = pending exception */
	static zv::Val newSelf(zv::Ref file, zv::Ref classReflection, zv::Ref traitReflection)
	{
		zend_class_entry *impl = pt_ce_scope_context;
		if (UNEXPECTED(impl == NULL)) return zv::Val();
		zval raw;
		if (UNEXPECTED(object_init_ex(&raw, impl) != SUCCESS)) return zv::Val();
		zv::Val context = zv::Val::adopt(raw);
		ScopeContext(context.raw()).construct(file, classReflection, traitReflection);
		return context;
	}

	/* $reflection->method() coerced to bool, resolved through the object's
	 * own class entry (a userland PHPStan\Reflection\ClassReflection);
	 * pt_call_scope_bool is that generic helper despite its parameter name.
	 * false = pending exception */
	[[nodiscard]] static bool callBool(zv::Ref reflection, const char *lcname, size_t len, bool &out)
	{
		return pt_call_scope_bool(reflection.raw(), lcname, len, 0, NULL, &out);
	}

	/* $reflection->getName() — the native ClassReflection's body called
	 * directly (ClassReflection.cpp); UNDEF = pending exception */
	static zv::Val callGetName(zv::Ref reflection)
	{
		return pt_class_reflection_get_name(reflection.asObject());
	}

	/* $a->getName() === $b->getName(); both calls always happen, like the
	 * twin's. false = pending exception */
	[[nodiscard]] static bool sameName(zv::Ref a, zv::Ref b, bool &out)
	{
		zv::Val aName = callGetName(a);
		if (UNEXPECTED(aName.isUndef())) return false;
		zv::Val bName = callGetName(b);
		if (UNEXPECTED(bName.isUndef())) return false;
		out = zend_is_identical(aName.raw(), bName.raw());
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ScopeContext;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define SCOPE_CONTEXT_CLASS "PHPStanTurbo\\ScopeContext"

void pt_register_scope_context();

void pt_register_scope_context()
{
	reg::Class cls("PHPStan\\Analyser\\ScopeContext");
	ptdecl::ScopeContext::declareClass(cls);
	/* file/classReflection/traitReflection must stay in this order
	 * (OBJ_PROP_NUM slots) */
	cls.privateNullProperty("file");
	cls.privateNullProperty("classReflection");
	cls.privateNullProperty("traitReflection");

	/* private like the twin's: `new ScopeContext(...)` from userland fails
	 * the same way; the native factories fill the slots without it */
	cls.method("__construct", reg::Private, 3, { reg::stringArg("file"), reg::objectArg("classReflection", true), reg::objectArg("traitReflection", true) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *file;
		zval *classReflection, *traitReflection;
		if (!zp::parse<zp::Str, zp::ObjOrNull, zp::ObjOrNull>(execute_data, file, classReflection, traitReflection)) RETURN_THROWS();
		zval fileZv, null;
		ZVAL_STR(&fileZv, file);
		ZVAL_NULL(&null);
		ScopeContext(ZEND_THIS).construct(zv::Ref(&fileZv), zv::Ref(classReflection != NULL ? classReflection : &null), zv::Ref(traitReflection != NULL ? traitReflection : &null));
	});

	cls.method("create", reg::PublicStatic, 1, { reg::stringArg("file") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *file;
		if (!zp::parse<zp::Str>(execute_data, file)) RETURN_THROWS();
		zval fileZv;
		ZVAL_STR(&fileZv, file);
		zv::Val result = ScopeContext::create(zv::Ref(&fileZv));
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("beginFile", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = ScopeContext(ZEND_THIS).beginFile();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("enterClass", reg::Public, 1, { reg::objectArg("classReflection") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		if (!zp::parse<zp::Obj>(execute_data, classReflection)) RETURN_THROWS();
		zv::Val result = ScopeContext(ZEND_THIS).enterClass(zv::Ref(classReflection));
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("enterTrait", reg::Public, 1, { reg::objectArg("traitReflection") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *traitReflection;
		if (!zp::parse<zp::Obj>(execute_data, traitReflection)) RETURN_THROWS();
		zv::Val result = ScopeContext(ZEND_THIS).enterTrait(zv::Ref(traitReflection));
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("equals", reg::Public, 1, { reg::obj("otherContext", SCOPE_CONTEXT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherContext;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(otherContext, pt_ce_scope_context)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ScopeContext(ZEND_THIS).equals(ScopeContext(otherContext), out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method("getFile", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ScopeContext(ZEND_THIS).getFile().intoReturnValue(return_value);
	});

	cls.method("getClassReflection", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ScopeContext(ZEND_THIS).getClassReflection().intoReturnValue(return_value);
	});

	cls.method("getTraitReflection", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ScopeContext(ZEND_THIS).getTraitReflection().intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_scope_context);
}

/* }}} */

/* {{{ MutatingScope::isInClass() / getClassReflection() for native callers
 *
 * Both read $this->context->getClassReflection(): when the scope is exactly
 * a MutatingScope (a PHP subclass may override them) holding a native
 * ScopeContext, the class reflection comes straight out of the context's
 * slot; anything else goes through the PHP method as before. The class
 * entry is looked up without autoloading — an object of a class that is not
 * declared cannot exist, so an undeclared MutatingScope simply means "not
 * this class". */

namespace {

/* the property slot offset of a class entry, resolved once; a user class's
 * entry is per request without opcache, so rinit forgets it */
struct ScopeSlots
{
	zend_class_entry *ce;
	uint32_t context;
};

ScopeSlots pt_ms_slots = { NULL, 0 };

/* the subclass of MutatingScope last approved by the inheritance check */
zend_class_entry *pt_ms_inherited_ce = NULL;

/* whether a subclass inherits isInClass() and getClassReflection() from
 * MutatingScope itself (declared there, not re-declared below it) */
bool inheritsScopeGetters(zend_class_entry *ce, zend_class_entry *mutatingScope)
{
	static const struct { const char *name; size_t len; } methods[] = { { "isinclass", sizeof("isinclass") - 1 }, { "getclassreflection", sizeof("getclassreflection") - 1 } };
	for (const auto &method : methods) {
		zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, method.name, method.len);
		if (fn == NULL || fn->common.scope != mutatingScope) return false;
	}
	return true;
}

/* the $classReflection slot of the scope's context when the fast path
 * applies (the scope exactly a MutatingScope, its context a native
 * ScopeContext); NULL otherwise, with `error` set when the class map
 * failed */
zval *scopeClassReflectionSlot(zend_object *scope, bool &error)
{
	error = false;
	if (scope->ce != pt_ms_slots.ce && scope->ce != pt_ms_inherited_ce) {
		zend_class_entry *ce = pt_class_loaded(PT_CLASS_MUTATING_SCOPE);
		if (ce == NULL) {
			error = EG(exception) != NULL;
			return NULL;
		}
		if (scope->ce != ce) {
			/* a subclass (NodeCallbackScope) qualifies when it inherits both
			 * methods from MutatingScope unchanged: the bodies are then the
			 * twin's, reading the same inherited $context slot; the last
			 * such class is remembered (one subclass exists in practice) */
			if (!instanceof_function(scope->ce, ce) || !inheritsScopeGetters(scope->ce, ce)) return NULL;
		}
		int32_t context = pt_instance_prop_offset(ce, "context", sizeof("context") - 1);
		if (UNEXPECTED(context < 0)) return NULL;
		if (scope->ce == ce) {
			pt_ms_slots = { ce, (uint32_t) context };
		} else {
			pt_ms_slots.context = (uint32_t) context;
			pt_ms_inherited_ce = scope->ce;
		}
	}
	zval *context = OBJ_PROP(scope, pt_ms_slots.context);
	if (Z_TYPE_P(context) != IS_OBJECT || Z_OBJCE_P(context) != pt_ce_scope_context) return NULL;
	return pt_scope_context_class_reflection(Z_OBJ_P(context));
}

} // namespace

void pt_scope_access_rinit()
{
	pt_ms_slots.ce = NULL;
	pt_ms_inherited_ce = NULL;
}

/* return $this->context->getClassReflection() !== null; */
bool pt_scope_is_in_class(zend_object *scope, bool &out)
{
	bool error;
	zval *classReflection = scopeClassReflectionSlot(scope, error);
	if (classReflection != NULL) {
		if (Z_TYPE_P(classReflection) == IS_NULL) {
			out = false;
			return true;
		}
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT)) {
			out = true;
			return true;
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	zv::Val result = pt_type_call(scope, "isinclass", sizeof("isinclass") - 1, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* return $this->context->getClassReflection(); */
zv::Val pt_scope_get_class_reflection(zend_object *scope)
{
	bool error;
	zval *classReflection = scopeClassReflectionSlot(scope, error);
	if (classReflection != NULL) {
		if (Z_TYPE_P(classReflection) == IS_NULL) return zv::Val::null();
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(classReflection));
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	return pt_type_call(scope, "getclassreflection", sizeof("getclassreflection") - 1, 0, NULL);
}

/* }}} */
