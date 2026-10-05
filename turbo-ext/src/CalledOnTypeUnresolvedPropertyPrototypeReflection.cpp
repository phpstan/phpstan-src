/*
 * PHPStanTurbo\CalledOnTypeUnresolvedPropertyPrototypeReflection — native
 * implementation of PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection.
 *
 * Declared under the twin's real name at activation (final, like the
 * twin): the lazy property prototype ObjectType builds, whose
 * getTransformedProperty() rewrites `static` in the property's types to
 * the fetched-on type. The logic lives in the handle class below,
 * mirroring src/Reflection/Type/CalledOnTypeUnresolvedPropertyPrototypeReflection.php
 * method for method; transformPropertyWithStaticType() is the shared
 * pt_prototype_resolved_property() (TypeTraits.cpp), and the
 * transformStaticType() closure runs as a native TypeTraverser callback.
 * State lives in the six declared property slots — the twin's, in its
 * order; the standard object handlers do GC/free/clone.
 */

#include "support.h"
#include "generated/CalledOnTypeUnresolvedPropertyPrototypeReflection.h"

namespace slots = ptdecl::CalledOnTypeUnresolvedPropertyPrototypeReflection::slot;
namespace sigs = ptdecl::CalledOnTypeUnresolvedPropertyPrototypeReflection::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_called_on_type_unresolved_property_prototype_reflection = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Type\CalledOnTypeUnresolvedPropertyPrototypeReflection.
 * State lives in the PHP object's $transformedProperty,
 * $cachedDoNotResolveTemplateTypeMapToBounds and the promoted
 * $propertyReflection, $resolvedDeclaringClass, $resolveTemplateTypeMapToBounds,
 * $fetchedOnType. */
class CalledOnTypeUnresolvedPropertyPrototypeReflection
{
public:
	explicit CalledOnTypeUnresolvedPropertyPrototypeReflection(zend_object *self) : self(self) {}

	/* __construct(private ExtendedPropertyReflection $propertyReflection,
	 * private ClassReflection $resolvedDeclaringClass, private bool
	 * $resolveTemplateTypeMapToBounds, private Type $fetchedOnType) */
	void construct(zval *propertyReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *fetchedOnType) const
	{
		write(slots::propertyReflection, zv::Val::copyOf(zv::Ref(propertyReflection)));
		write(slots::resolvedDeclaringClass, zv::Val::copyOf(zv::Ref(resolvedDeclaringClass)));
		write(slots::resolveTemplateTypeMapToBounds, zv::Val::boolean(resolveTemplateTypeMapToBounds));
		write(slots::fetchedOnType, zv::Val::copyOf(zv::Ref(fetchedOnType)));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *propertyReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *fetchedOnType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_called_on_type_unresolved_property_prototype_reflection) != SUCCESS)) return zv::Val();
		CalledOnTypeUnresolvedPropertyPrototypeReflection(Z_OBJ(object)).construct(propertyReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, fetchedOnType);
		return zv::Val::adopt(object);
	}

	/* doNotResolveTemplateTypeMapToBounds(): the memoized copy with
	 * $resolveTemplateTypeMapToBounds = false; UNDEF = pending exception */
	zv::Val doNotResolveTemplateTypeMapToBounds() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedDoNotResolveTemplateTypeMapToBounds);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		zval *propertyReflection = slot(slots::propertyReflection, "propertyReflection");
		zval *resolvedDeclaringClass = propertyReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		zval *fetchedOnType = resolvedDeclaringClass != NULL ? slot(slots::fetchedOnType, "fetchedOnType") : NULL;
		if (UNEXPECTED(fetchedOnType == NULL)) return zv::Val();
		zv::Val created = create(propertyReflection, resolvedDeclaringClass, false, fetchedOnType);
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		write(slots::cachedDoNotResolveTemplateTypeMapToBounds, zv::Val::copyOf(zv::Ref(created.raw())));
		return created;
	}

	/* getNakedProperty(): $this->propertyReflection; UNDEF = pending exception */
	zv::Val getNakedProperty() const
	{
		zval *propertyReflection = slot(slots::propertyReflection, "propertyReflection");
		return propertyReflection != NULL ? zv::Val::copyOf(zv::Ref(propertyReflection)) : zv::Val();
	}

	/* getTransformedProperty(): the memo, computed once through
	 * pt_prototype_resolved_property(); UNDEF = pending exception */
	zv::Val getTransformedProperty() const
	{
		zval *transformedProperty = OBJ_PROP_NUM(self, slots::transformedProperty);
		if (EXPECTED(Z_TYPE_P(transformedProperty) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(transformedProperty));
		zval *resolvedDeclaringClass = slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass");
		zval *propertyReflection = resolvedDeclaringClass != NULL ? slot(slots::propertyReflection, "propertyReflection") : NULL;
		zval *resolveToBounds = propertyReflection != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		PrototypeTransformer transformer = { transformStaticType, self };
		zv::Val result = pt_prototype_resolved_property(PT_PROTOTYPE_CALLED_ON_TYPE, transformer, Z_OBJ_P(resolvedDeclaringClass), Z_OBJ_P(propertyReflection), zend_is_true(resolveToBounds));
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		write(slots::transformedProperty, zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* withFechedOnType(Type $type): a copy over $type; UNDEF = pending exception */
	zv::Val withFechedOnType(zval *type) const
	{
		zval *propertyReflection = slot(slots::propertyReflection, "propertyReflection");
		zval *resolvedDeclaringClass = propertyReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		zval *resolveToBounds = resolvedDeclaringClass != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		return create(propertyReflection, resolvedDeclaringClass, zend_is_true(resolveToBounds), type);
	}

private:
	zend_object *self;

	void write(uint32_t index, zv::Val value) const
	{
		zval *slot = OBJ_PROP_NUM(self, index);
		zval previous;
		ZVAL_COPY_VALUE(&previous, slot);
		zval v = value.take();
		ZVAL_COPY_VALUE(slot, &v);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	zval *slot(uint32_t index, const char *propertyName) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_called_on_type_unresolved_property_prototype_reflection->name), propertyName);
			return NULL;
		}
		return value;
	}

	/* transformStaticType($type): TypeTraverser::map($type, the closure
	 * below over $this); UNDEF = pending exception */
	static zv::Val transformStaticType(void *context, zval *type)
	{
		zval thisZv;
		ZVAL_OBJ(&thisZv, (zend_object *) context);
		zv::Val callback = pt_type_native_callback(traverse, &thisZv, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* the closure of transformStaticType(): `function (Type $type, callable
	 * $traverse): Type` — a StaticType becomes the fetched-on type,
	 * everything else traverses */
	static void traverse(zval *thisZv, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc < 2)) {
			zend_argument_count_error("Too few arguments to function %s::{closure}(), %u passed and exactly 2 expected", ZSTR_VAL(pt_ce_called_on_type_unresolved_property_prototype_reflection->name), argc);
			return;
		}
		if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_type_error("%s::{closure}(): Argument #1 ($type) must be of type %s, %s given", ZSTR_VAL(pt_ce_called_on_type_unresolved_property_prototype_reflection->name), ptcls::type, zend_zval_value_name(&argv[0]));
			return;
		}
		zval *type = &argv[0];
		bool isStatic;
		pt_type_instanceof_ce(type, pt_ce_static_type, isStatic);
		if (isStatic) {
			zval *fetchedOnType = CalledOnTypeUnresolvedPropertyPrototypeReflection(Z_OBJ_P(thisZv)).slot(slots::fetchedOnType, "fetchedOnType");
			if (UNEXPECTED(fetchedOnType == NULL)) return;
			ZVAL_COPY(return_value, fetchedOnType);
			return;
		}
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CalledOnTypeUnresolvedPropertyPrototypeReflection;

/* {{{ exported helpers: the shadowing class for native callers */

/* new CalledOnTypeUnresolvedPropertyPrototypeReflection(...$argv) over
 * values as PHP code hands them (borrowed): directly when they already
 * have the parameter types, through the constructor's parameter parsing
 * otherwise; UNDEF = pending exception */
zv::Val pt_called_on_type_unresolved_property_prototype_reflection_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc == 4 && Z_TYPE(argv[0]) == IS_OBJECT && Z_TYPE(argv[1]) == IS_OBJECT && (Z_TYPE(argv[2]) == IS_TRUE || Z_TYPE(argv[2]) == IS_FALSE) && Z_TYPE(argv[3]) == IS_OBJECT)) {
		return CalledOnTypeUnresolvedPropertyPrototypeReflection::create(&argv[0], &argv[1], Z_TYPE(argv[2]) == IS_TRUE, &argv[3]);
	}
	return pt_type_new_ce(pt_ce_called_on_type_unresolved_property_prototype_reflection, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_THIS CalledOnTypeUnresolvedPropertyPrototypeReflection(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_called_on_type_unresolved_property_prototype_reflection)
{
	static const char *selfClass = "PHPStan\\Reflection\\Type\\CalledOnTypeUnresolvedPropertyPrototypeReflection";
	static const char *extendedPropertyReflectionClass = "PHPStan\\Reflection\\ExtendedPropertyReflection";

	reg::Class cls("PHPStan\\Reflection\\Type\\CalledOnTypeUnresolvedPropertyPrototypeReflection");
	ptdecl::CalledOnTypeUnresolvedPropertyPrototypeReflection::declareClass(cls);
	cls.privateTypedClassPropertyDefaultNull("transformedProperty", extendedPropertyReflectionClass);
	cls.privateTypedClassPropertyDefaultNull("cachedDoNotResolveTemplateTypeMapToBounds", selfClass);
	cls.privateTypedClassProperty("propertyReflection", extendedPropertyReflectionClass, false);
	cls.privateTypedClassProperty("resolvedDeclaringClass", ptcls::classReflection, false);
	cls.privateTypedProperty("resolveTemplateTypeMapToBounds", MAY_BE_BOOL);
	cls.privateTypedClassProperty("fetchedOnType", ptcls::type, false);

	cls.method<&CalledOnTypeUnresolvedPropertyPrototypeReflection::construct, zp::Obj, zp::Obj, zp::Bool, zp::Obj>(sigs::__construct);

	cls.method<&CalledOnTypeUnresolvedPropertyPrototypeReflection::doNotResolveTemplateTypeMapToBounds>(sigs::doNotResolveTemplateTypeMapToBounds);
	cls.op<PT_OP_DO_NOT_RESOLVE_TEMPLATE_TYPE_MAP_TO_BOUNDS, &CalledOnTypeUnresolvedPropertyPrototypeReflection::doNotResolveTemplateTypeMapToBounds>();

	cls.method<&CalledOnTypeUnresolvedPropertyPrototypeReflection::getNakedProperty>(sigs::getNakedProperty);
	cls.op<PT_OP_GET_NAKED_PROPERTY, &CalledOnTypeUnresolvedPropertyPrototypeReflection::getNakedProperty>();

	cls.method<&CalledOnTypeUnresolvedPropertyPrototypeReflection::getTransformedProperty>(sigs::getTransformedProperty);
	cls.op<PT_OP_GET_TRANSFORMED_PROPERTY, &CalledOnTypeUnresolvedPropertyPrototypeReflection::getTransformedProperty>();

	cls.method<&CalledOnTypeUnresolvedPropertyPrototypeReflection::withFechedOnType, zp::Obj>(sigs::withFechedOnType);

	cls.shadow(&pt_ce_called_on_type_unresolved_property_prototype_reflection);
}

/* }}} */
