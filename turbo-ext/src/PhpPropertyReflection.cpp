/*
 * PHPStanTurbo\PhpPropertyReflection — native implementation of
 * PHPStan\Reflection\Php\PhpPropertyReflection.
 *
 * The property reflection PhpClassReflectionExtension creates for every
 * declared property: promoted slots, the readable/writable type memos, and
 * the BetterReflection adapter ($reflection), whose getters are read through
 * the adapter readers of BetterReflectionAccess.cpp. TypehintHelper,
 * TrinaryLogic and the Type kernel are reached natively; the set hook's
 * variant and parameters through pt_extended_method_reflection_call() /
 * pt_parameters_acceptor_parameters() and the parameter's getType() by name.
 * Native callers use pt_php_property_reflection_new() and reach the bodies
 * through pt_extended_property_reflection_call() without a frame.
 */

#include "support.h"
#include "generated/PhpPropertyReflection.h"

namespace slots = ptdecl::PhpPropertyReflection::slot;
namespace sigs = ptdecl::PhpPropertyReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

zend_class_entry *pt_ce_php_property_reflection = nullptr;

namespace {

pt_method_site pt_ppr_parameter_get_type_site;

/*
 * `$value instanceof <ShadowedTypeClass>` — the native class entry first,
 * and under the prefixed activation of the differential tests also the PHP
 * twin that still carries the real name (the container's Type objects are
 * the twins there). In a production run the native class carries the real
 * name and the second lookup never runs.
 */
bool instanceOfShadowed(zval *value, zend_class_entry *ce, const char *realName, size_t len)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	if (EXPECTED(instanceof_function(Z_OBJCE_P(value), ce))) return true;
	if (EXPECTED(zend_string_equals_cstr(ce->name, realName, len))) return false;
	zend_string *name = zend_string_init(realName, len, 0);
	zend_class_entry *twin = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
	zend_string_release(name);
	return twin != NULL && instanceof_function(Z_OBJCE_P(value), twin);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Php\PhpPropertyReflection; UNDEF / false =
 * pending exception. */
class PhpPropertyReflection
{
public:
	explicit PhpPropertyReflection(zend_object *self) : self(self) {}

	/* the constructor body: the twenty promoted properties, in parameter order */
	void construct(zval *argv) const
	{
		static const uint32_t promoted[20] = {
			slots::declaringClass, slots::declaringTrait, slots::nativeType, slots::readablePhpDocType, slots::writablePhpDocType,
			slots::reflection, slots::getHook, slots::setHook, slots::resolvedPhpDocBlock, slots::deprecatedDescription,
			slots::isDeprecated, slots::isInternal, slots::isReadOnlyByPhpDoc, slots::isAllowedPrivateMutation, slots::attributes,
			slots::isFinal, slots::readable, slots::writable, slots::private_, slots::public_,
		};
		for (uint32_t i = 0; i < 20; i++) {
			if (i >= 10 && i != 14 && Z_TYPE(argv[i]) != IS_TRUE && Z_TYPE(argv[i]) != IS_FALSE) {
				/* the bool parameters, coerced as the typed parameters coerce */
				zval coerced = {};
				ZVAL_BOOL(&coerced, zend_is_true(&argv[i]));
				pt_write_slot(self, promoted[i], &coerced);
				continue;
			}
			pt_write_slot(self, promoted[i], &argv[i]);
		}
	}

	/* new self(...$argv); UNDEF = pending exception */
	static zv::Val create(zval *argv)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_php_property_reflection) != SUCCESS)) return zv::Val();
		PhpPropertyReflection(Z_OBJ(object)).construct(argv);
		return zv::Val::adopt(object);
	}

	zv::Val getName() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return pt_property_adapter_get_name(reflection);
	}

	zv::Val getDeclaringClass() const { return copy(slot(slots::declaringClass, "declaringClass")); }
	zv::Val getDeclaringTrait() const { return copy(slot(slots::declaringTrait, "declaringTrait")); }

	/* Mirrors getDocComment(): the adapter's false as null. */
	zv::Val getDocComment() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zv::Val docComment = pt_property_adapter_get_doc_comment(reflection);
		if (UNEXPECTED(docComment.isUndef())) return zv::Val();
		if (Z_TYPE_P(docComment.raw()) == IS_FALSE) return zv::Val::null();
		return docComment;
	}

	bool isStatic(bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_property_adapter_is_static(reflection, out);
	}

	bool isPrivate(bool &out) const { return boolSlot(slots::private_, "private", out); }
	bool isPublic(bool &out) const { return boolSlot(slots::public_, "public", out); }

	bool isReadOnly(bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_property_adapter_is_read_only(reflection, out);
	}

	bool isReadOnlyByPhpDoc(bool &out) const { return boolSlot(slots::isReadOnlyByPhpDoc, "isReadOnlyByPhpDoc", out); }

	/* Mirrors getReadableType(): $this->readableType ??= TypehintHelper::decideType($this->nativeType, $this->readablePhpDocType) */
	zv::Val getReadableType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::readableType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *nativeType = slot(slots::nativeType, "nativeType");
		if (UNEXPECTED(nativeType == NULL)) return zv::Val();
		zval *readablePhpDocType = slot(slots::readablePhpDocType, "readablePhpDocType");
		if (UNEXPECTED(readablePhpDocType == NULL)) return zv::Val();
		zv::Val type = pt_typehint_helper_decide_type(nativeType, readablePhpDocType);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(self, slots::readableType, type.raw());
		return type;
	}

	/* Mirrors getWritableType(). */
	zv::Val getWritableType() const
	{
		/* if ($this->hasHook('set')) { $parameters = $this->getHook('set')->getOnlyVariant()->getParameters();
		 * if (isset($parameters[0])) return $parameters[0]->getType(); } */
		zval *setHook = slot(slots::setHook, "setHook");
		if (UNEXPECTED(setHook == NULL)) return zv::Val();
		if (Z_TYPE_P(setHook) != IS_NULL) {
			zv::Val variant = pt_extended_method_reflection_call(setHook, PT_MR_GET_ONLY_VARIANT);
			if (UNEXPECTED(variant.isUndef())) return zv::Val();
			zv::Val parametersHold;
			zval *parameters = pt_parameters_acceptor_parameters(variant.raw(), parametersHold);
			if (UNEXPECTED(parameters == NULL)) return zv::Val();
			zval *first = Z_TYPE_P(parameters) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(parameters), 0) : NULL;
			if (first != NULL) ZVAL_DEREF(first);
			if (first != NULL && Z_TYPE_P(first) != IS_NULL) {
				if (UNEXPECTED(Z_TYPE_P(first) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(first));
					return zv::Val();
				}
				return pt_call_method_cached(pt_ppr_parameter_get_type_site, Z_OBJ_P(first), PT_LC("gettype"), 0, NULL);
			}
		}

		zval *memo = OBJ_PROP_NUM(self, slots::writableType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));

		zval *nativeType = slot(slots::nativeType, "nativeType");
		if (UNEXPECTED(nativeType == NULL)) return zv::Val();
		zval *readablePhpDocType = slot(slots::readablePhpDocType, "readablePhpDocType");
		if (UNEXPECTED(readablePhpDocType == NULL)) return zv::Val();
		zval *writablePhpDocType = slot(slots::writablePhpDocType, "writablePhpDocType");
		if (UNEXPECTED(writablePhpDocType == NULL)) return zv::Val();

		zval *decideAgainst;
		if (Z_TYPE_P(writablePhpDocType) == IS_NULL || instanceOfShadowed(writablePhpDocType, pt_ce_never_type, PT_LC("PHPStan\\Type\\NeverType"))) {
			decideAgainst = readablePhpDocType;
		} else {
			if (Z_TYPE_P(readablePhpDocType) != IS_NULL) {
				/* !$this->readablePhpDocType->equals($this->writablePhpDocType) */
				zv::Val equals = pt_type_op(Z_OBJ_P(readablePhpDocType), PT_OP_EQUALS, 1, writablePhpDocType);
				if (UNEXPECTED(equals.isUndef())) return zv::Val();
				if (Z_TYPE_P(equals.raw()) != IS_TRUE) {
					pt_write_slot(self, slots::writableType, writablePhpDocType);
					return zv::Val::copyOf(zv::Ref(writablePhpDocType));
				}
			}
			decideAgainst = writablePhpDocType;
		}
		zv::Val type = pt_typehint_helper_decide_type(nativeType, decideAgainst);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(self, slots::writableType, type.raw());
		return type;
	}

	/* Mirrors canChangeTypeAfterAssignment(). */
	bool canChangeTypeAfterAssignment(bool &out) const
	{
		bool isStatic_;
		if (UNEXPECTED(!isStatic(isStatic_))) return false;
		if (!isStatic_) {
			zend_long virtual_ = isVirtualValue();
			if (UNEXPECTED(virtual_ < 0)) return false;
			if (virtual_ == 1) {
				out = false;
				return true;
			}
			bool hooked;
			if (UNEXPECTED(!hasHookSlot(true, hooked))) return false;
			if (!hooked && UNEXPECTED(!hasHookSlot(false, hooked))) return false;
			if (hooked) {
				out = false;
				return true;
			}
		}
		/* $this->getReadableType()->equals($this->getWritableType()) */
		zv::Val readableType = getReadableType();
		if (UNEXPECTED(readableType.isUndef())) return false;
		zv::Val writableType = getWritableType();
		if (UNEXPECTED(writableType.isUndef())) return false;
		zv::Val equals = pt_type_op(Z_OBJ_P(readableType.raw()), PT_OP_EQUALS, 1, writableType.raw());
		if (UNEXPECTED(equals.isUndef())) return false;
		out = Z_TYPE_P(equals.raw()) == IS_TRUE;
		return true;
	}

	bool isPromoted(bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_property_adapter_is_promoted(reflection, out);
	}

	bool hasPhpDocType(bool &out) const
	{
		zval *readablePhpDocType = slot(slots::readablePhpDocType, "readablePhpDocType");
		if (UNEXPECTED(readablePhpDocType == NULL)) return false;
		out = Z_TYPE_P(readablePhpDocType) != IS_NULL;
		return true;
	}

	/* Mirrors getPhpDocType(): the PHPDoc type or a new MixedType. */
	zv::Val getPhpDocType() const
	{
		zval *readablePhpDocType = slot(slots::readablePhpDocType, "readablePhpDocType");
		if (UNEXPECTED(readablePhpDocType == NULL)) return zv::Val();
		if (Z_TYPE_P(readablePhpDocType) != IS_NULL) return zv::Val::copyOf(zv::Ref(readablePhpDocType));
		return pt_type_new_mixed_type();
	}

	/* Mirrors hasNativeType(): !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed() */
	bool hasNativeType(bool &out) const
	{
		zval *nativeType = slot(slots::nativeType, "nativeType");
		if (UNEXPECTED(nativeType == NULL)) return false;
		if (!instanceOfShadowed(nativeType, pt_ce_mixed_type, PT_LC("PHPStan\\Type\\MixedType"))) {
			out = true;
			return true;
		}
		zv::Val explicitMixed = pt_type_call(Z_OBJ_P(nativeType), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(explicitMixed.isUndef())) return false;
		out = zend_is_true(explicitMixed.raw());
		return true;
	}

	zv::Val getNativeType() const { return copy(slot(slots::nativeType, "nativeType")); }

	/* Mirrors isReadable(). */
	bool isReadable(bool &out) const { return accessible(slots::readable, "readable", true, out); }

	/* Mirrors isWritable(). */
	bool isWritable(bool &out) const { return accessible(slots::writable, "writable", false, out); }

	/* Mirrors getDeprecatedDescription(). */
	zv::Val getDeprecatedDescription() const
	{
		bool isDeprecated_;
		if (UNEXPECTED(!boolSlot(slots::isDeprecated, "isDeprecated", isDeprecated_))) return zv::Val();
		if (isDeprecated_) return copy(slot(slots::deprecatedDescription, "deprecatedDescription"));
		return zv::Val::null();
	}

	zv::Val isDeprecated() const { return trinarySlot(slots::isDeprecated, "isDeprecated"); }
	zv::Val isInternal() const { return trinarySlot(slots::isInternal, "isInternal"); }

	bool isAllowedPrivateMutation(bool &out) const { return boolSlot(slots::isAllowedPrivateMutation, "isAllowedPrivateMutation", out); }

	zv::Val getNativeReflection() const { return copy(slot(slots::reflection, "reflection")); }

	/* Mirrors isAbstract(): TrinaryLogic::createFromBoolean($this->reflection->isAbstract()) */
	zv::Val isAbstract() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		bool answer;
		if (UNEXPECTED(reflection == NULL) || UNEXPECTED(!pt_property_adapter_is_abstract(reflection, answer))) return zv::Val();
		return trinary(answer);
	}

	/* Mirrors isFinalByKeyword(): TrinaryLogic::createFromBoolean($this->reflection->isFinal()) */
	zv::Val isFinalByKeyword() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		bool answer;
		if (UNEXPECTED(reflection == NULL) || UNEXPECTED(!pt_property_adapter_is_final(reflection, answer))) return zv::Val();
		return trinary(answer);
	}

	zv::Val isFinal() const { return trinarySlot(slots::isFinal, "isFinal"); }

	/* Mirrors isVirtual(): TrinaryLogic::createFromBoolean($this->reflection->isVirtual()) */
	zv::Val isVirtual() const
	{
		zend_long answer = isVirtualValue();
		if (UNEXPECTED(answer < 0)) return zv::Val();
		return trinary(answer == 1);
	}

	/* Mirrors hasHook(): $hookType === 'get' ? $this->getHook !== null : $this->setHook !== null */
	bool hasHook(zend_string *hookType, bool &out) const
	{
		return hasHookSlot(zend_string_equals_literal(hookType, "get"), out);
	}

	/* Mirrors isHooked(). */
	bool isHooked(bool &out) const
	{
		if (UNEXPECTED(!hasHookSlot(true, out))) return false;
		if (out) return true;
		return hasHookSlot(false, out);
	}

	/* Mirrors getHook(): the hook, or MissingMethodFromReflectionException. */
	zv::Val getHook(zend_string *hookType) const
	{
		bool get = zend_string_equals_literal(hookType, "get");
		zval *hook = get ? slot(slots::getHook, "getHook") : slot(slots::setHook, "setHook");
		if (UNEXPECTED(hook == NULL)) return zv::Val();
		if (EXPECTED(Z_TYPE_P(hook) != IS_NULL)) return zv::Val::copyOf(zv::Ref(hook));

		/* throw new MissingMethodFromReflectionException($this->declaringClass->getName(), sprintf('$%s::get', $this->reflection->getName())) */
		zval *declaringClass = slot(slots::declaringClass, "declaringClass");
		if (UNEXPECTED(declaringClass == NULL)) return zv::Val();
		zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(declaringClass));
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val propertyName = getName();
		if (UNEXPECTED(propertyName.isUndef())) return zv::Val();
		zend_string *propertyNameStr = zval_get_string(propertyName.raw());
		zend_string *methodName = zend_strpprintf(0, "$%s::%s", ZSTR_VAL(propertyNameStr), get ? "get" : "set");
		zend_string_release(propertyNameStr);
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], className.raw());
		ZVAL_STR(&args[1], methodName);
		zv::Val exception = pt_type_new(PT_CLASS_MISSING_METHOD_FROM_REFLECTION_EXCEPTION, 2, args);
		zend_string_release(methodName);
		if (UNEXPECTED(exception.isUndef())) return zv::Val();
		zval thrown = exception.take();
		zend_throw_exception_object(&thrown);
		return zv::Val();
	}

	bool isProtectedSet(bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_property_adapter_is_protected_set(reflection, out);
	}

	bool isPrivateSet(bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_property_adapter_is_private_set(reflection, out);
	}

	zv::Val getAttributes() const { return copy(slot(slots::attributes, "attributes")); }

	/* Mirrors isDummy(): TrinaryLogic::createNo() */
	zv::Val isDummy() const { return trinary(false); }

	zv::Val getResolvedPhpDoc() const { return copy(slot(slots::resolvedPhpDocBlock, "resolvedPhpDocBlock")); }

private:
	zend_object *self;

	zval *slot(uint32_t index, const char *name) const
	{
		return pt_typed_slot(self, index, self->ce, name);
	}

	static zv::Val copy(zval *slot)
	{
		return slot != NULL ? zv::Val::copyOf(zv::Ref(slot)) : zv::Val();
	}

	bool boolSlot(uint32_t index, const char *name, bool &out) const
	{
		zval *value = slot(index, name);
		if (UNEXPECTED(value == NULL)) return false;
		out = Z_TYPE_P(value) == IS_TRUE;
		return true;
	}

	static zv::Val trinary(bool value)
	{
		return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value ? PT_TRI_YES : PT_TRI_NO)));
	}

	/* TrinaryLogic::createFromBoolean($this->x) */
	zv::Val trinarySlot(uint32_t index, const char *name) const
	{
		bool value;
		if (UNEXPECTED(!boolSlot(index, name, value))) return zv::Val();
		return trinary(value);
	}

	/* $this->reflection->isVirtual() as 1 / 0, -1 = pending exception */
	zend_long isVirtualValue() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		bool answer;
		if (UNEXPECTED(reflection == NULL) || UNEXPECTED(!pt_property_adapter_is_virtual(reflection, answer))) return -1;
		return answer ? 1 : 0;
	}

	/* $this->getHook !== null / $this->setHook !== null */
	bool hasHookSlot(bool get, bool &out) const
	{
		zval *hook = get ? slot(slots::getHook, "getHook") : slot(slots::setHook, "setHook");
		if (UNEXPECTED(hook == NULL)) return false;
		out = Z_TYPE_P(hook) != IS_NULL;
		return true;
	}

	/* isReadable() / isWritable(): if (!$this->readable) return false; if
	 * ($this->isStatic()) return true; if (!$this->isVirtual()->yes()) return
	 * true; return $this->hasHook('get'); */
	bool accessible(uint32_t index, const char *name, bool get, bool &out) const
	{
		bool flag;
		if (UNEXPECTED(!boolSlot(index, name, flag))) return false;
		if (!flag) {
			out = false;
			return true;
		}
		bool isStatic_;
		if (UNEXPECTED(!isStatic(isStatic_))) return false;
		if (isStatic_) {
			out = true;
			return true;
		}
		zend_long virtual_ = isVirtualValue();
		if (UNEXPECTED(virtual_ < 0)) return false;
		if (virtual_ != 1) {
			out = true;
			return true;
		}
		return hasHookSlot(get, out);
	}
};

} // namespace phpstanturbo

using phpstanturbo::PhpPropertyReflection;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_php_property_reflection_new(zval *argv)
{
	return PhpPropertyReflection::create(argv);
}

namespace {

/* `out` by reference: the argument order is unspecified, and a copy taken
 * before the getter that fills it runs (x86-64 GCC evaluates right to
 * left) would answer the stale value */
inline zv::Val boolAnswer(bool ok, const bool &out)
{
	return ok ? zv::Val::boolean(out) : zv::Val();
}

} // namespace

zv::Val pt_php_property_reflection_call(zend_object *property, pt_property_reflection_member member)
{
	PhpPropertyReflection reflection(property);
	bool out = false;
	switch (member) {
		case PT_PROP_GET_NAME: return reflection.getName();
		case PT_PROP_GET_DECLARING_CLASS: return reflection.getDeclaringClass();
		case PT_PROP_IS_STATIC: return boolAnswer(reflection.isStatic(out), out);
		case PT_PROP_IS_PRIVATE: return boolAnswer(reflection.isPrivate(out), out);
		case PT_PROP_IS_PUBLIC: return boolAnswer(reflection.isPublic(out), out);
		case PT_PROP_GET_DOC_COMMENT: return reflection.getDocComment();
		case PT_PROP_GET_READABLE_TYPE: return reflection.getReadableType();
		case PT_PROP_GET_WRITABLE_TYPE: return reflection.getWritableType();
		case PT_PROP_CAN_CHANGE_TYPE_AFTER_ASSIGNMENT: return boolAnswer(reflection.canChangeTypeAfterAssignment(out), out);
		case PT_PROP_IS_READABLE: return boolAnswer(reflection.isReadable(out), out);
		case PT_PROP_IS_WRITABLE: return boolAnswer(reflection.isWritable(out), out);
		case PT_PROP_IS_DEPRECATED: return reflection.isDeprecated();
		case PT_PROP_GET_DEPRECATED_DESCRIPTION: return reflection.getDeprecatedDescription();
		case PT_PROP_IS_INTERNAL: return reflection.isInternal();
		case PT_PROP_HAS_PHP_DOC_TYPE: return boolAnswer(reflection.hasPhpDocType(out), out);
		case PT_PROP_GET_PHP_DOC_TYPE: return reflection.getPhpDocType();
		case PT_PROP_HAS_NATIVE_TYPE: return boolAnswer(reflection.hasNativeType(out), out);
		case PT_PROP_GET_NATIVE_TYPE: return reflection.getNativeType();
		case PT_PROP_IS_ABSTRACT: return reflection.isAbstract();
		case PT_PROP_IS_FINAL_BY_KEYWORD: return reflection.isFinalByKeyword();
		case PT_PROP_IS_FINAL: return reflection.isFinal();
		case PT_PROP_IS_VIRTUAL: return reflection.isVirtual();
		case PT_PROP_IS_PROTECTED_SET: return boolAnswer(reflection.isProtectedSet(out), out);
		case PT_PROP_IS_PRIVATE_SET: return boolAnswer(reflection.isPrivateSet(out), out);
		case PT_PROP_GET_ATTRIBUTES: return reflection.getAttributes();
		case PT_PROP_IS_DUMMY: return reflection.isDummy();
		case PT_PROP_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

bool pt_php_property_reflection_has_hook(zend_object *property, zend_string *hookType, bool &out)
{
	return PhpPropertyReflection(property).hasHook(hookType, out);
}

zv::Val pt_php_property_reflection_get_hook(zend_object *property, zend_string *hookType)
{
	return PhpPropertyReflection(property).getHook(hookType);
}

bool pt_php_property_reflection_is_hooked(zend_object *property, bool &out)
{
	return PhpPropertyReflection(property).isHooked(out);
}

bool pt_php_property_reflection_is_promoted(zend_object *property, bool &out)
{
	return PhpPropertyReflection(property).isPromoted(out);
}

bool pt_php_property_reflection_is_read_only(zend_object *property, bool &out)
{
	return PhpPropertyReflection(property).isReadOnly(out);
}

zv::Val pt_php_property_reflection_get_native_reflection(zend_object *property)
{
	return PhpPropertyReflection(property).getNativeReflection();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_php_property_reflection)
{
	reg::Class cls("PHPStan\\Reflection\\Php\\PhpPropertyReflection");
	ptdecl::PhpPropertyReflection::declareClass(cls);
	ptdecl::PhpPropertyReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *argv;
		uint32_t argc;
		ZEND_PARSE_PARAMETERS_START(20, 20)
			Z_PARAM_VARIADIC('+', argv, argc)
		ZEND_PARSE_PARAMETERS_END();
		(void) argc;
		PhpPropertyReflection(Z_OBJ_P(ZEND_THIS)).construct(argv);
	});

	cls.method<&PhpPropertyReflection::getName>(sigs::getName);
	cls.op<PT_OP_GET_NAME, &PhpPropertyReflection::getName>();
	cls.method<&PhpPropertyReflection::getDeclaringClass>(sigs::getDeclaringClass);
	cls.method<&PhpPropertyReflection::getDeclaringTrait>(sigs::getDeclaringTrait);
	cls.method<&PhpPropertyReflection::getDocComment>(sigs::getDocComment);
	cls.method<&PhpPropertyReflection::isStatic>(sigs::isStatic);
	cls.method<&PhpPropertyReflection::isPrivate>(sigs::isPrivate);
	cls.method<&PhpPropertyReflection::isPublic>(sigs::isPublic);
	cls.op<PT_OP_IS_PUBLIC, &PhpPropertyReflection::isPublic>();
	cls.method<&PhpPropertyReflection::isReadOnly>(sigs::isReadOnly);
	cls.method<&PhpPropertyReflection::isReadOnlyByPhpDoc>(sigs::isReadOnlyByPhpDoc);
	cls.method<&PhpPropertyReflection::getReadableType>(sigs::getReadableType);
	cls.method<&PhpPropertyReflection::getWritableType>(sigs::getWritableType);
	cls.method<&PhpPropertyReflection::canChangeTypeAfterAssignment>(sigs::canChangeTypeAfterAssignment);
	cls.method<&PhpPropertyReflection::isPromoted>(sigs::isPromoted);
	cls.method<&PhpPropertyReflection::hasPhpDocType>(sigs::hasPhpDocType);
	cls.method<&PhpPropertyReflection::getPhpDocType>(sigs::getPhpDocType);
	cls.method<&PhpPropertyReflection::hasNativeType>(sigs::hasNativeType);
	cls.method<&PhpPropertyReflection::getNativeType>(sigs::getNativeType);
	cls.method<&PhpPropertyReflection::isReadable>(sigs::isReadable);
	cls.method<&PhpPropertyReflection::isWritable>(sigs::isWritable);
	cls.method<&PhpPropertyReflection::getDeprecatedDescription>(sigs::getDeprecatedDescription);
	cls.method<&PhpPropertyReflection::isDeprecated>(sigs::isDeprecated);
	cls.method<&PhpPropertyReflection::isInternal>(sigs::isInternal);
	cls.method<&PhpPropertyReflection::isAllowedPrivateMutation>(sigs::isAllowedPrivateMutation);
	cls.method<&PhpPropertyReflection::getNativeReflection>(sigs::getNativeReflection);
	cls.method<&PhpPropertyReflection::isAbstract>(sigs::isAbstract);
	cls.method<&PhpPropertyReflection::isFinalByKeyword>(sigs::isFinalByKeyword);
	cls.op<PT_OP_IS_FINAL_BY_KEYWORD, &PhpPropertyReflection::isFinalByKeyword>();
	cls.method<&PhpPropertyReflection::isFinal>(sigs::isFinal);
	cls.op<PT_OP_IS_FINAL, &PhpPropertyReflection::isFinal>();
	cls.method<&PhpPropertyReflection::isVirtual>(sigs::isVirtual);
	cls.method<&PhpPropertyReflection::hasHook, zp::Str>(sigs::hasHook);
	cls.method<&PhpPropertyReflection::isHooked>(sigs::isHooked);
	cls.method<&PhpPropertyReflection::getHook, zp::Str>(sigs::getHook);
	cls.method<&PhpPropertyReflection::isProtectedSet>(sigs::isProtectedSet);
	cls.method<&PhpPropertyReflection::isPrivateSet>(sigs::isPrivateSet);
	cls.method<&PhpPropertyReflection::getAttributes>(sigs::getAttributes);
	cls.method<&PhpPropertyReflection::isDummy>(sigs::isDummy);
	cls.method<&PhpPropertyReflection::getResolvedPhpDoc>(sigs::getResolvedPhpDoc);

	cls.shadow(&pt_ce_php_property_reflection);
}

/* }}} */
