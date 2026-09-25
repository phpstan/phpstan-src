/*
 * PHPStanTurbo\ChangedTypePropertyReflection — native implementation of
 * PHPStan\Reflection\Dummy\ChangedTypePropertyReflection.
 *
 * The property a member prototype transformed (CalledOnType / Callback
 * UnresolvedPropertyPrototypeReflection): the declaring class and the four
 * types are its own promoted slots, everything else is delegated to the
 * wrapped reflection through pt_extended_property_reflection_call(). Almost
 * every instance is wrapped by a ResolvedPropertyReflection, which reaches
 * these bodies without a frame. Native creators (the prototypes in
 * TypeTraits.cpp) use pt_changed_type_property_reflection_new().
 */

#include "support.h"
#include "generated/ChangedTypePropertyReflection.h"

namespace slots = ptdecl::ChangedTypePropertyReflection::slot;
namespace sigs = ptdecl::ChangedTypePropertyReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

zend_class_entry *pt_ce_changed_type_property_reflection = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Dummy\ChangedTypePropertyReflection; UNDEF =
 * pending exception. */
class ChangedTypePropertyReflection
{
public:
	explicit ChangedTypePropertyReflection(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *declaringClass, zval *reflection, zval *readableType, zval *writableType, zval *phpDocType, zval *nativeType) const
	{
		pt_write_slot(self, slots::declaringClass, declaringClass);
		pt_write_slot(self, slots::reflection, reflection);
		pt_write_slot(self, slots::readableType, readableType);
		pt_write_slot(self, slots::writableType, writableType);
		pt_write_slot(self, slots::phpDocType, phpDocType);
		pt_write_slot(self, slots::nativeType, nativeType);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *declaringClass, zval *reflection, zval *readableType, zval *writableType, zval *phpDocType, zval *nativeType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_changed_type_property_reflection) != SUCCESS)) return zv::Val();
		ChangedTypePropertyReflection(Z_OBJ(object)).construct(declaringClass, reflection, readableType, writableType, phpDocType, nativeType);
		return zv::Val::adopt(object);
	}

	zv::Val getName() const { return delegate(PT_PROP_GET_NAME); }
	zv::Val getDeclaringClass() const { return copy(slot(slots::declaringClass, "declaringClass")); }
	zv::Val isStatic() const { return delegate(PT_PROP_IS_STATIC); }
	zv::Val isPrivate() const { return delegate(PT_PROP_IS_PRIVATE); }
	zv::Val isPublic() const { return delegate(PT_PROP_IS_PUBLIC); }
	zv::Val getDocComment() const { return delegate(PT_PROP_GET_DOC_COMMENT); }
	zv::Val hasPhpDocType() const { return delegate(PT_PROP_HAS_PHP_DOC_TYPE); }
	zv::Val getPhpDocType() const { return copy(slot(slots::phpDocType, "phpDocType")); }
	zv::Val hasNativeType() const { return delegate(PT_PROP_HAS_NATIVE_TYPE); }
	zv::Val getNativeType() const { return copy(slot(slots::nativeType, "nativeType")); }
	zv::Val getReadableType() const { return copy(slot(slots::readableType, "readableType")); }
	zv::Val getWritableType() const { return copy(slot(slots::writableType, "writableType")); }
	zv::Val canChangeTypeAfterAssignment() const { return delegate(PT_PROP_CAN_CHANGE_TYPE_AFTER_ASSIGNMENT); }
	zv::Val isReadable() const { return delegate(PT_PROP_IS_READABLE); }
	zv::Val isWritable() const { return delegate(PT_PROP_IS_WRITABLE); }
	zv::Val isDeprecated() const { return delegate(PT_PROP_IS_DEPRECATED); }
	zv::Val getDeprecatedDescription() const { return delegate(PT_PROP_GET_DEPRECATED_DESCRIPTION); }
	zv::Val isInternal() const { return delegate(PT_PROP_IS_INTERNAL); }
	zv::Val getOriginalReflection() const { return copy(slot(slots::reflection, "reflection")); }
	zv::Val isAbstract() const { return delegate(PT_PROP_IS_ABSTRACT); }
	zv::Val isFinalByKeyword() const { return delegate(PT_PROP_IS_FINAL_BY_KEYWORD); }
	zv::Val isFinal() const { return delegate(PT_PROP_IS_FINAL); }
	zv::Val isVirtual() const { return delegate(PT_PROP_IS_VIRTUAL); }

	bool hasHook(zend_string *hookType, bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		return EXPECTED(reflection != NULL) && pt_extended_property_reflection_has_hook(reflection, hookType, out);
	}

	zv::Val getHook(zend_string *hookType) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return pt_extended_property_reflection_get_hook(reflection, hookType);
	}

	zv::Val isProtectedSet() const { return delegate(PT_PROP_IS_PROTECTED_SET); }
	zv::Val isPrivateSet() const { return delegate(PT_PROP_IS_PRIVATE_SET); }
	zv::Val getAttributes() const { return delegate(PT_PROP_GET_ATTRIBUTES); }
	zv::Val isDummy() const { return delegate(PT_PROP_IS_DUMMY); }

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

	/* $this->reflection->method() */
	zv::Val delegate(pt_property_reflection_member member) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return pt_extended_property_reflection_call(reflection, member);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ChangedTypePropertyReflection;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_changed_type_property_reflection_new(zval *declaringClass, zval *reflection, zval *readableType, zval *writableType, zval *phpDocType, zval *nativeType)
{
	return ChangedTypePropertyReflection::create(declaringClass, reflection, readableType, writableType, phpDocType, nativeType);
}

zv::Val pt_changed_type_property_reflection_call(zend_object *property, pt_property_reflection_member member)
{
	ChangedTypePropertyReflection reflection(property);
	switch (member) {
		case PT_PROP_GET_NAME: return reflection.getName();
		case PT_PROP_GET_DECLARING_CLASS: return reflection.getDeclaringClass();
		case PT_PROP_IS_STATIC: return reflection.isStatic();
		case PT_PROP_IS_PRIVATE: return reflection.isPrivate();
		case PT_PROP_IS_PUBLIC: return reflection.isPublic();
		case PT_PROP_GET_DOC_COMMENT: return reflection.getDocComment();
		case PT_PROP_GET_READABLE_TYPE: return reflection.getReadableType();
		case PT_PROP_GET_WRITABLE_TYPE: return reflection.getWritableType();
		case PT_PROP_CAN_CHANGE_TYPE_AFTER_ASSIGNMENT: return reflection.canChangeTypeAfterAssignment();
		case PT_PROP_IS_READABLE: return reflection.isReadable();
		case PT_PROP_IS_WRITABLE: return reflection.isWritable();
		case PT_PROP_IS_DEPRECATED: return reflection.isDeprecated();
		case PT_PROP_GET_DEPRECATED_DESCRIPTION: return reflection.getDeprecatedDescription();
		case PT_PROP_IS_INTERNAL: return reflection.isInternal();
		case PT_PROP_HAS_PHP_DOC_TYPE: return reflection.hasPhpDocType();
		case PT_PROP_GET_PHP_DOC_TYPE: return reflection.getPhpDocType();
		case PT_PROP_HAS_NATIVE_TYPE: return reflection.hasNativeType();
		case PT_PROP_GET_NATIVE_TYPE: return reflection.getNativeType();
		case PT_PROP_IS_ABSTRACT: return reflection.isAbstract();
		case PT_PROP_IS_FINAL_BY_KEYWORD: return reflection.isFinalByKeyword();
		case PT_PROP_IS_FINAL: return reflection.isFinal();
		case PT_PROP_IS_VIRTUAL: return reflection.isVirtual();
		case PT_PROP_IS_PROTECTED_SET: return reflection.isProtectedSet();
		case PT_PROP_IS_PRIVATE_SET: return reflection.isPrivateSet();
		case PT_PROP_GET_ATTRIBUTES: return reflection.getAttributes();
		case PT_PROP_IS_DUMMY: return reflection.isDummy();
		case PT_PROP_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

bool pt_changed_type_property_reflection_has_hook(zend_object *property, zend_string *hookType, bool &out)
{
	return ChangedTypePropertyReflection(property).hasHook(hookType, out);
}

zv::Val pt_changed_type_property_reflection_get_hook(zend_object *property, zend_string *hookType)
{
	return ChangedTypePropertyReflection(property).getHook(hookType);
}

zv::Val pt_changed_type_property_reflection_get_original_reflection(zend_object *property)
{
	return ChangedTypePropertyReflection(property).getOriginalReflection();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_changed_type_property_reflection)
{
	reg::Class cls("PHPStan\\Reflection\\Dummy\\ChangedTypePropertyReflection");
	ptdecl::ChangedTypePropertyReflection::declareClass(cls);
	ptdecl::ChangedTypePropertyReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *declaringClass, *reflection, *readableType, *writableType, *phpDocType, *nativeType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, declaringClass, reflection, readableType, writableType, phpDocType, nativeType)) RETURN_THROWS();
		ChangedTypePropertyReflection(Z_OBJ_P(ZEND_THIS)).construct(declaringClass, reflection, readableType, writableType, phpDocType, nativeType);
	});

	cls.method<&ChangedTypePropertyReflection::getName>(sigs::getName);
	cls.op<PT_OP_GET_NAME, &ChangedTypePropertyReflection::getName>();
	cls.method<&ChangedTypePropertyReflection::getDeclaringClass>(sigs::getDeclaringClass);
	cls.method<&ChangedTypePropertyReflection::isStatic>(sigs::isStatic);
	cls.method<&ChangedTypePropertyReflection::isPrivate>(sigs::isPrivate);
	cls.method<&ChangedTypePropertyReflection::isPublic>(sigs::isPublic);
	cls.op<PT_OP_IS_PUBLIC, &ChangedTypePropertyReflection::isPublic>();
	cls.method<&ChangedTypePropertyReflection::getDocComment>(sigs::getDocComment);
	cls.method<&ChangedTypePropertyReflection::hasPhpDocType>(sigs::hasPhpDocType);
	cls.method<&ChangedTypePropertyReflection::getPhpDocType>(sigs::getPhpDocType);
	cls.method<&ChangedTypePropertyReflection::hasNativeType>(sigs::hasNativeType);
	cls.method<&ChangedTypePropertyReflection::getNativeType>(sigs::getNativeType);
	cls.method<&ChangedTypePropertyReflection::getReadableType>(sigs::getReadableType);
	cls.method<&ChangedTypePropertyReflection::getWritableType>(sigs::getWritableType);
	cls.method<&ChangedTypePropertyReflection::canChangeTypeAfterAssignment>(sigs::canChangeTypeAfterAssignment);
	cls.method<&ChangedTypePropertyReflection::isReadable>(sigs::isReadable);
	cls.method<&ChangedTypePropertyReflection::isWritable>(sigs::isWritable);
	cls.method<&ChangedTypePropertyReflection::isDeprecated>(sigs::isDeprecated);
	cls.method<&ChangedTypePropertyReflection::getDeprecatedDescription>(sigs::getDeprecatedDescription);
	cls.method<&ChangedTypePropertyReflection::isInternal>(sigs::isInternal);
	cls.method<&ChangedTypePropertyReflection::getOriginalReflection>(sigs::getOriginalReflection);
	cls.method<&ChangedTypePropertyReflection::isAbstract>(sigs::isAbstract);
	cls.method<&ChangedTypePropertyReflection::isFinalByKeyword>(sigs::isFinalByKeyword);
	cls.op<PT_OP_IS_FINAL_BY_KEYWORD, &ChangedTypePropertyReflection::isFinalByKeyword>();
	cls.method<&ChangedTypePropertyReflection::isFinal>(sigs::isFinal);
	cls.op<PT_OP_IS_FINAL, &ChangedTypePropertyReflection::isFinal>();
	cls.method<&ChangedTypePropertyReflection::isVirtual>(sigs::isVirtual);
	cls.method<&ChangedTypePropertyReflection::hasHook, zp::Str>(sigs::hasHook);
	cls.method<&ChangedTypePropertyReflection::getHook, zp::Str>(sigs::getHook);
	cls.method<&ChangedTypePropertyReflection::isProtectedSet>(sigs::isProtectedSet);
	cls.method<&ChangedTypePropertyReflection::isPrivateSet>(sigs::isPrivateSet);
	cls.method<&ChangedTypePropertyReflection::getAttributes>(sigs::getAttributes);
	cls.method<&ChangedTypePropertyReflection::isDummy>(sigs::isDummy);

	cls.shadow(&pt_ce_changed_type_property_reflection);
}

/* }}} */
