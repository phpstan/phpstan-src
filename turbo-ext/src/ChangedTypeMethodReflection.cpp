/*
 * PHPStanTurbo\ChangedTypeMethodReflection — native implementation of
 * PHPStan\Reflection\Dummy\ChangedTypeMethodReflection.
 *
 * The method a member prototype transformed (CalledOnType / Callback
 * UnresolvedMethodPrototypeReflection): the declaring class, the variants,
 * the self-out and throw types and the asserts are its own promoted slots,
 * everything else is delegated to the wrapped naked reflection through
 * pt_extended_method_reflection_call() (one cached method site per member).
 * Almost every instance is wrapped by a ResolvedMethodReflection, which
 * reaches these bodies through pt_changed_type_method_reflection_call()
 * without a frame. Native creators (the prototypes in TypeTraits.cpp) use
 * pt_changed_type_method_reflection_new().
 */

#include "support.h"
#include "generated/ChangedTypeMethodReflection.h"

namespace slots = ptdecl::ChangedTypeMethodReflection::slot;
namespace sigs = ptdecl::ChangedTypeMethodReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

zend_class_entry *pt_ce_changed_type_method_reflection = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Dummy\ChangedTypeMethodReflection; UNDEF =
 * pending exception. */
class ChangedTypeMethodReflection
{
public:
	explicit ChangedTypeMethodReflection(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties ($namedArgumentsVariants /
	 * $selfOutType / $throwType NULL or IS_NULL for null) */
	void construct(zval *declaringClass, zval *reflection, zval *variants, zval *namedArgumentsVariants, zval *selfOutType, zval *throwType, zval *assertions) const
	{
		zval null;
		ZVAL_NULL(&null);
		pt_write_slot(self, slots::declaringClass, declaringClass);
		pt_write_slot(self, slots::reflection, reflection);
		pt_write_slot(self, slots::variants, variants);
		pt_write_slot(self, slots::namedArgumentsVariants, namedArgumentsVariants != NULL ? namedArgumentsVariants : &null);
		pt_write_slot(self, slots::selfOutType, selfOutType != NULL ? selfOutType : &null);
		pt_write_slot(self, slots::throwType, throwType != NULL ? throwType : &null);
		pt_write_slot(self, slots::assertions, assertions);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *declaringClass, zval *reflection, zval *variants, zval *namedArgumentsVariants, zval *selfOutType, zval *throwType, zval *assertions)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_changed_type_method_reflection) != SUCCESS)) return zv::Val();
		ChangedTypeMethodReflection(Z_OBJ(object)).construct(declaringClass, reflection, variants, namedArgumentsVariants, selfOutType, throwType, assertions);
		return zv::Val::adopt(object);
	}

	/* the getters of the own slots (borrowed; NULL with the uninitialized-read
	 * Error pending) */
	zval *declaringClassSlot() const { return pt_typed_slot(self, slots::declaringClass, self->ce, "declaringClass"); }
	zval *variantsSlot() const { return pt_typed_slot(self, slots::variants, self->ce, "variants"); }
	zval *namedArgumentsVariantsSlot() const { return pt_typed_slot(self, slots::namedArgumentsVariants, self->ce, "namedArgumentsVariants"); }
	zval *selfOutTypeSlot() const { return pt_typed_slot(self, slots::selfOutType, self->ce, "selfOutType"); }
	zval *throwTypeSlot() const { return pt_typed_slot(self, slots::throwType, self->ce, "throwType"); }
	zval *assertionsSlot() const { return pt_typed_slot(self, slots::assertions, self->ce, "assertions"); }

	zv::Val getDeclaringClass() const { return copy(declaringClassSlot()); }
	zv::Val isStatic() const { return delegate(PT_MR_IS_STATIC); }
	zv::Val isPrivate() const { return delegate(PT_MR_IS_PRIVATE); }
	zv::Val isPublic() const { return delegate(PT_MR_IS_PUBLIC); }
	zv::Val getDocComment() const { return delegate(PT_MR_GET_DOC_COMMENT); }
	zv::Val getName() const { return delegate(PT_MR_GET_NAME); }
	zv::Val getPrototype() const { return delegate(PT_MR_GET_PROTOTYPE); }
	zv::Val getVariants() const { return copy(variantsSlot()); }

	/* Mirrors getOnlyVariant(). */
	zv::Val getOnlyVariant() const
	{
		zval *variants = variantsSlot();
		if (UNEXPECTED(variants == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(variants)) != 1) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval *variant = zend_hash_index_find(Z_ARRVAL_P(variants), 0);
		if (UNEXPECTED(variant == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return zv::Val();
			zend_type_error("%s::getOnlyVariant(): Return value must be of type PHPStan\\Reflection\\ExtendedParametersAcceptor, null returned", ZSTR_VAL(self->ce->name));
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(variant));
	}

	zv::Val getNamedArgumentsVariants() const { return copy(namedArgumentsVariantsSlot()); }
	zv::Val isDeprecated() const { return delegate(PT_MR_IS_DEPRECATED); }
	zv::Val getDeprecatedDescription() const { return delegate(PT_MR_GET_DEPRECATED_DESCRIPTION); }
	zv::Val isFinal() const { return delegate(PT_MR_IS_FINAL); }
	zv::Val isFinalByKeyword() const { return delegate(PT_MR_IS_FINAL_BY_KEYWORD); }
	zv::Val isInternal() const { return delegate(PT_MR_IS_INTERNAL); }

	/* Mirrors isBuiltin(): a bool answer as the TrinaryLogic singleton. */
	zv::Val isBuiltin() const
	{
		return trinaryFromBool(delegate(PT_MR_IS_BUILTIN));
	}

	zv::Val getThrowType() const { return copy(throwTypeSlot()); }
	zv::Val hasSideEffects() const { return delegate(PT_MR_HAS_SIDE_EFFECTS); }
	zv::Val getAsserts() const { return copy(assertionsSlot()); }
	zv::Val acceptsNamedArguments() const { return delegate(PT_MR_ACCEPTS_NAMED_ARGUMENTS); }
	zv::Val getSelfOutType() const { return copy(selfOutTypeSlot()); }
	zv::Val returnsByReference() const { return delegate(PT_MR_RETURNS_BY_REFERENCE); }

	/* Mirrors isAbstract(): a bool answer as the TrinaryLogic singleton. */
	zv::Val isAbstract() const
	{
		return trinaryFromBool(delegate(PT_MR_IS_ABSTRACT));
	}

	zv::Val isPure() const { return delegate(PT_MR_IS_PURE); }
	zv::Val getPureUnlessCallableIsImpureParameters() const { return delegate(PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS); }
	zv::Val getAttributes() const { return delegate(PT_MR_GET_ATTRIBUTES); }
	zv::Val mustUseReturnValue() const { return delegate(PT_MR_MUST_USE_RETURN_VALUE); }
	zv::Val getResolvedPhpDoc() const { return delegate(PT_MR_GET_RESOLVED_PHP_DOC); }

private:
	zend_object *self;

	static zv::Val copy(zval *slot)
	{
		return slot != NULL ? zv::Val::copyOf(zv::Ref(slot)) : zv::Val();
	}

	/* $this->reflection->method() */
	zv::Val delegate(pt_method_reflection_member member) const
	{
		zval *reflection = pt_typed_slot(self, slots::reflection, self->ce, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return pt_extended_method_reflection_call(reflection, member);
	}

	/* is_bool($x) ? TrinaryLogic::createFromBoolean($x) : $x */
	static zv::Val trinaryFromBool(zv::Val value)
	{
		if (value.isUndef()) return value;
		if (Z_TYPE_P(value.raw()) == IS_TRUE) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
		if (Z_TYPE_P(value.raw()) == IS_FALSE) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_NO)));
		return value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ChangedTypeMethodReflection;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_changed_type_method_reflection_new(zval *declaringClass, zval *reflection, zval *variants, zval *namedArgumentsVariants, zval *selfOutType, zval *throwType, zval *assertions)
{
	return ChangedTypeMethodReflection::create(declaringClass, reflection, variants, namedArgumentsVariants, selfOutType, throwType, assertions);
}

zv::Val pt_changed_type_method_reflection_call(zend_object *method, pt_method_reflection_member member)
{
	ChangedTypeMethodReflection reflection(method);
	switch (member) {
		case PT_MR_GET_NAME: return reflection.getName();
		case PT_MR_GET_PROTOTYPE: return reflection.getPrototype();
		case PT_MR_GET_VARIANTS: return reflection.getVariants();
		case PT_MR_GET_ONLY_VARIANT: return reflection.getOnlyVariant();
		case PT_MR_GET_NAMED_ARGUMENTS_VARIANTS: return reflection.getNamedArgumentsVariants();
		case PT_MR_GET_DECLARING_CLASS: return reflection.getDeclaringClass();
		case PT_MR_IS_STATIC: return reflection.isStatic();
		case PT_MR_IS_PRIVATE: return reflection.isPrivate();
		case PT_MR_IS_PUBLIC: return reflection.isPublic();
		case PT_MR_GET_DOC_COMMENT: return reflection.getDocComment();
		case PT_MR_IS_DEPRECATED: return reflection.isDeprecated();
		case PT_MR_GET_DEPRECATED_DESCRIPTION: return reflection.getDeprecatedDescription();
		case PT_MR_IS_FINAL: return reflection.isFinal();
		case PT_MR_IS_FINAL_BY_KEYWORD: return reflection.isFinalByKeyword();
		case PT_MR_IS_INTERNAL: return reflection.isInternal();
		case PT_MR_IS_BUILTIN: return reflection.isBuiltin();
		case PT_MR_GET_THROW_TYPE: return reflection.getThrowType();
		case PT_MR_HAS_SIDE_EFFECTS: return reflection.hasSideEffects();
		case PT_MR_IS_PURE: return reflection.isPure();
		case PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS: return reflection.getPureUnlessCallableIsImpureParameters();
		case PT_MR_GET_ASSERTS: return reflection.getAsserts();
		case PT_MR_ACCEPTS_NAMED_ARGUMENTS: return reflection.acceptsNamedArguments();
		case PT_MR_GET_SELF_OUT_TYPE: return reflection.getSelfOutType();
		case PT_MR_RETURNS_BY_REFERENCE: return reflection.returnsByReference();
		case PT_MR_IS_ABSTRACT: return reflection.isAbstract();
		case PT_MR_GET_ATTRIBUTES: return reflection.getAttributes();
		case PT_MR_MUST_USE_RETURN_VALUE: return reflection.mustUseReturnValue();
		case PT_MR_GET_RESOLVED_PHP_DOC: return reflection.getResolvedPhpDoc();
		case PT_MR_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_changed_type_method_reflection)
{
	reg::Class cls("PHPStan\\Reflection\\Dummy\\ChangedTypeMethodReflection");
	ptdecl::ChangedTypeMethodReflection::declareClass(cls);
	ptdecl::ChangedTypeMethodReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *declaringClass, *reflection, *variants, *namedArgumentsVariants, *selfOutType, *throwType, *assertions;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(declaringClass)
			Z_PARAM_OBJECT(reflection)
			Z_PARAM_ARRAY(variants)
			Z_PARAM_ARRAY_OR_NULL(namedArgumentsVariants)
			Z_PARAM_OBJECT_OR_NULL(selfOutType)
			Z_PARAM_OBJECT_OR_NULL(throwType)
			Z_PARAM_OBJECT(assertions)
		ZEND_PARSE_PARAMETERS_END();
		ChangedTypeMethodReflection(Z_OBJ_P(ZEND_THIS)).construct(declaringClass, reflection, variants, namedArgumentsVariants, selfOutType, throwType, assertions);
	});

	cls.method<&ChangedTypeMethodReflection::getDeclaringClass>(sigs::getDeclaringClass);
	cls.method<&ChangedTypeMethodReflection::isStatic>(sigs::isStatic);
	cls.method<&ChangedTypeMethodReflection::isPrivate>(sigs::isPrivate);
	cls.method<&ChangedTypeMethodReflection::isPublic>(sigs::isPublic);
	cls.method<&ChangedTypeMethodReflection::getDocComment>(sigs::getDocComment);
	cls.method<&ChangedTypeMethodReflection::getName>(sigs::getName);
	cls.method<&ChangedTypeMethodReflection::getPrototype>(sigs::getPrototype);
	cls.method<&ChangedTypeMethodReflection::getVariants>(sigs::getVariants);
	cls.method<&ChangedTypeMethodReflection::getOnlyVariant>(sigs::getOnlyVariant);
	cls.method<&ChangedTypeMethodReflection::getNamedArgumentsVariants>(sigs::getNamedArgumentsVariants);
	cls.method<&ChangedTypeMethodReflection::isDeprecated>(sigs::isDeprecated);
	cls.method<&ChangedTypeMethodReflection::getDeprecatedDescription>(sigs::getDeprecatedDescription);
	cls.method<&ChangedTypeMethodReflection::isFinal>(sigs::isFinal);
	cls.method<&ChangedTypeMethodReflection::isFinalByKeyword>(sigs::isFinalByKeyword);
	cls.method<&ChangedTypeMethodReflection::isInternal>(sigs::isInternal);
	cls.method<&ChangedTypeMethodReflection::isBuiltin>(sigs::isBuiltin);
	cls.method<&ChangedTypeMethodReflection::getThrowType>(sigs::getThrowType);
	cls.method<&ChangedTypeMethodReflection::hasSideEffects>(sigs::hasSideEffects);
	cls.method<&ChangedTypeMethodReflection::getAsserts>(sigs::getAsserts);
	cls.method<&ChangedTypeMethodReflection::acceptsNamedArguments>(sigs::acceptsNamedArguments);
	cls.method<&ChangedTypeMethodReflection::getSelfOutType>(sigs::getSelfOutType);
	cls.method<&ChangedTypeMethodReflection::returnsByReference>(sigs::returnsByReference);
	cls.method<&ChangedTypeMethodReflection::isAbstract>(sigs::isAbstract);
	cls.method<&ChangedTypeMethodReflection::isPure>(sigs::isPure);
	cls.method<&ChangedTypeMethodReflection::getPureUnlessCallableIsImpureParameters>(sigs::getPureUnlessCallableIsImpureParameters);
	cls.method<&ChangedTypeMethodReflection::getAttributes>(sigs::getAttributes);
	cls.method<&ChangedTypeMethodReflection::mustUseReturnValue>(sigs::mustUseReturnValue);
	cls.method<&ChangedTypeMethodReflection::getResolvedPhpDoc>(sigs::getResolvedPhpDoc);

	cls.shadow(&pt_ce_changed_type_method_reflection);
}

/* }}} */
