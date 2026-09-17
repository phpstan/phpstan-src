/*
 * PHPStanTurbo\CalledOnTypeUnresolvedMethodPrototypeReflection — native
 * implementation of PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection.
 *
 * Declared under the twin's real name at activation (final, like the
 * twin): the lazy method prototype ObjectType::getMethod() builds, whose
 * getTransformedMethod() rewrites `static` in the method's signature to
 * the called-on type. The logic lives in the handle class below,
 * mirroring src/Reflection/Type/CalledOnTypeUnresolvedMethodPrototypeReflection.php
 * method for method; transformMethodWithStaticType() is the shared
 * pt_prototype_resolved_method() (TypeTraits.cpp), and the
 * transformStaticType() closure runs as a native TypeTraverser callback,
 * so a transformation never leaves C++ except for the reflection getters
 * it reads. State lives in the six declared property slots — the twin's,
 * in its order; the standard object handlers do GC/free/clone.
 */

#include "support.h"
#include "generated/CalledOnTypeUnresolvedMethodPrototypeReflection.h"

namespace slots = ptdecl::CalledOnTypeUnresolvedMethodPrototypeReflection::slot;
namespace sigs = ptdecl::CalledOnTypeUnresolvedMethodPrototypeReflection::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_called_on_type_unresolved_method_prototype_reflection = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Type\CalledOnTypeUnresolvedMethodPrototypeReflection.
 * State lives in the PHP object's $transformedMethod,
 * $cachedDoNotResolveTemplateTypeMapToBounds and the promoted
 * $methodReflection, $resolvedDeclaringClass, $resolveTemplateTypeMapToBounds,
 * $calledOnType. */
class CalledOnTypeUnresolvedMethodPrototypeReflection
{
public:
	explicit CalledOnTypeUnresolvedMethodPrototypeReflection(zend_object *self) : self(self) {}

	/* __construct(private ExtendedMethodReflection $methodReflection,
	 * private ClassReflection $resolvedDeclaringClass, private bool
	 * $resolveTemplateTypeMapToBounds, private Type $calledOnType) — the
	 * promoted slots (the objects borrowed) */
	void construct(zval *methodReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *calledOnType) const
	{
		write(slots::methodReflection, zv::Val::copyOf(zv::Ref(methodReflection)));
		write(slots::resolvedDeclaringClass, zv::Val::copyOf(zv::Ref(resolvedDeclaringClass)));
		write(slots::resolveTemplateTypeMapToBounds, zv::Val::boolean(resolveTemplateTypeMapToBounds));
		write(slots::calledOnType, zv::Val::copyOf(zv::Ref(calledOnType)));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *methodReflection, zval *resolvedDeclaringClass, bool resolveTemplateTypeMapToBounds, zval *calledOnType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_called_on_type_unresolved_method_prototype_reflection) != SUCCESS)) return zv::Val();
		CalledOnTypeUnresolvedMethodPrototypeReflection(Z_OBJ(object)).construct(methodReflection, resolvedDeclaringClass, resolveTemplateTypeMapToBounds, calledOnType);
		return zv::Val::adopt(object);
	}

	/* doNotResolveTemplateTypeMapToBounds(): the memoized copy with
	 * $resolveTemplateTypeMapToBounds = false; UNDEF = pending exception */
	zv::Val doNotResolveTemplateTypeMapToBounds() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedDoNotResolveTemplateTypeMapToBounds);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		zval *methodReflection = slot(slots::methodReflection, "methodReflection");
		zval *resolvedDeclaringClass = methodReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		zval *calledOnType = resolvedDeclaringClass != NULL ? slot(slots::calledOnType, "calledOnType") : NULL;
		if (UNEXPECTED(calledOnType == NULL)) return zv::Val();
		zv::Val created = create(methodReflection, resolvedDeclaringClass, false, calledOnType);
		if (UNEXPECTED(created.isUndef())) return zv::Val();
		write(slots::cachedDoNotResolveTemplateTypeMapToBounds, zv::Val::copyOf(zv::Ref(created.raw())));
		return created;
	}

	/* getNakedMethod(): $this->methodReflection; UNDEF = pending exception */
	zv::Val getNakedMethod() const
	{
		zval *methodReflection = slot(slots::methodReflection, "methodReflection");
		return methodReflection != NULL ? zv::Val::copyOf(zv::Ref(methodReflection)) : zv::Val();
	}

	/* getTransformedMethod(): the memo, computed once through
	 * pt_prototype_resolved_method(); UNDEF = pending exception */
	zv::Val getTransformedMethod() const
	{
		zval *transformedMethod = OBJ_PROP_NUM(self, slots::transformedMethod);
		if (EXPECTED(Z_TYPE_P(transformedMethod) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(transformedMethod));
		zval *resolvedDeclaringClass = slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass");
		zval *methodReflection = resolvedDeclaringClass != NULL ? slot(slots::methodReflection, "methodReflection") : NULL;
		zval *resolveToBounds = methodReflection != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		/* fn (Type $type): Type => $this->transformStaticType($type) — for Assertions::mapTypes() */
		zval thisZv;
		ZVAL_OBJ(&thisZv, self);
		zv::Val assertsCallback = pt_type_native_callback(transformStaticTypeCallback, &thisZv, NULL);
		if (UNEXPECTED(assertsCallback.isUndef())) return zv::Val();
		PrototypeTransformer transformer = { transformStaticType, self };
		zv::Val result = pt_prototype_resolved_method(PT_PROTOTYPE_CALLED_ON_TYPE, transformer, Z_OBJ_P(resolvedDeclaringClass), Z_OBJ_P(methodReflection), zend_is_true(resolveToBounds), assertsCallback.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		write(slots::transformedMethod, zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* withCalledOnType(Type $type): a copy over $type; UNDEF = pending exception */
	zv::Val withCalledOnType(zval *type) const
	{
		zval *methodReflection = slot(slots::methodReflection, "methodReflection");
		zval *resolvedDeclaringClass = methodReflection != NULL ? slot(slots::resolvedDeclaringClass, "resolvedDeclaringClass") : NULL;
		zval *resolveToBounds = resolvedDeclaringClass != NULL ? slot(slots::resolveTemplateTypeMapToBounds, "resolveTemplateTypeMapToBounds") : NULL;
		if (UNEXPECTED(resolveToBounds == NULL)) return zv::Val();
		return create(methodReflection, resolvedDeclaringClass, zend_is_true(resolveToBounds), type);
	}

private:
	zend_object *self;

	/* a slot written by the constructor or a memo (owned value moved in,
	 * a previous value released) */
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

	/* a promoted slot (borrowed); NULL with the engine's Error pending for
	 * a read before the constructor initialized it */
	zval *slot(uint32_t index, const char *propertyName) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_called_on_type_unresolved_method_prototype_reflection->name), propertyName);
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

	/* fn (Type $type): Type => $this->transformStaticType($type) */
	static void transformStaticTypeCallback(zval *thisZv, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc < 1 || Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_argument_count_error("Too few arguments to function %s::{closure}(), %u passed and exactly 1 expected", ZSTR_VAL(pt_ce_called_on_type_unresolved_method_prototype_reflection->name), argc);
			return;
		}
		zv::Val result = transformStaticType(Z_OBJ_P(thisZv), &argv[0]);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* the closure of transformStaticType(): `function (Type $type, callable
	 * $traverse): Type` — a GenericStaticType becomes the static object
	 * type of its base class changed to the single called-on class
	 * (traversed on), or the called-on type itself; any other StaticType
	 * the called-on type; everything else traverses */
	static void traverse(zval *thisZv, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state1;
		if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_argument_count_error("Too few arguments to function %s::{closure}(), %u passed and exactly 2 expected", ZSTR_VAL(pt_ce_called_on_type_unresolved_method_prototype_reflection->name), argc);
			return;
		}
		CalledOnTypeUnresolvedMethodPrototypeReflection self(Z_OBJ_P(thisZv));
		zval *type = &argv[0];
		bool isGenericStatic;
		pt_type_instanceof_ce(type, pt_ce_generic_static_type, isGenericStatic);
		if (isGenericStatic) {
			zval *calledOnType = self.slot(slots::calledOnType, "calledOnType");
			if (UNEXPECTED(calledOnType == NULL)) return;
			/* $calledOnTypeReflections = $this->calledOnType->getObjectClassReflections(); */
			zv::Val reflections = pt_type_call(Z_OBJ_P(calledOnType), PT_LC("getobjectclassreflections"), 0, NULL);
			if (UNEXPECTED(reflections.isUndef())) return;
			if (UNEXPECTED(!zv::Ref(reflections.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getObjectClassReflections() must return an array");
				return;
			}
			if (zend_hash_num_elements(Z_ARRVAL_P(reflections.raw())) == 1) {
				/* return $traverse($type->changeBaseClass($calledOnTypeReflections[0])->getStaticObjectType()); */
				zval *first = zend_hash_index_find(Z_ARRVAL_P(reflections.raw()), 0);
				if (UNEXPECTED(first == NULL)) {
					zend_throw_error(NULL, "phpstan_turbo: getObjectClassReflections() must return a list");
					return;
				}
				ZVAL_DEREF(first);
				zv::Val changed = pt_type_call(Z_OBJ_P(type), PT_LC("changebaseclass"), 1, first);
				if (UNEXPECTED(changed.isUndef())) return;
				if (UNEXPECTED(!zv::Ref(changed.raw()).isObject())) {
					zend_type_error("phpstan_turbo: changeBaseClass() must return an object");
					return;
				}
				zv::Val staticObjectType = pt_type_call(Z_OBJ_P(changed.raw()), PT_LC("getstaticobjecttype"), 0, NULL);
				if (UNEXPECTED(staticObjectType.isUndef())) return;
				zv::Val traversed = pt_type_call_callable(&argv[1], 1, staticObjectType.raw());
				if (UNEXPECTED(traversed.isUndef())) return;
				traversed.intoReturnValue(return_value);
				return;
			}
			ZVAL_COPY(return_value, calledOnType);
			return;
		}
		bool isStatic;
		pt_type_instanceof_ce(type, pt_ce_static_type, isStatic);
		if (isStatic) {
			zval *calledOnType = self.slot(slots::calledOnType, "calledOnType");
			if (UNEXPECTED(calledOnType == NULL)) return;
			ZVAL_COPY(return_value, calledOnType);
			return;
		}
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CalledOnTypeUnresolvedMethodPrototypeReflection;

/* {{{ exported helpers: the shadowing class for native callers */

/* new CalledOnTypeUnresolvedMethodPrototypeReflection(...$argv) over values
 * as PHP code hands them (borrowed): directly when they already have the
 * parameter types, through the constructor's parameter parsing otherwise;
 * UNDEF = pending exception */
zv::Val pt_called_on_type_unresolved_method_prototype_reflection_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc == 4 && Z_TYPE(argv[0]) == IS_OBJECT && Z_TYPE(argv[1]) == IS_OBJECT && (Z_TYPE(argv[2]) == IS_TRUE || Z_TYPE(argv[2]) == IS_FALSE) && Z_TYPE(argv[3]) == IS_OBJECT)) {
		return CalledOnTypeUnresolvedMethodPrototypeReflection::create(&argv[0], &argv[1], Z_TYPE(argv[2]) == IS_TRUE, &argv[3]);
	}
	return pt_type_new_ce(pt_ce_called_on_type_unresolved_method_prototype_reflection, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_THIS CalledOnTypeUnresolvedMethodPrototypeReflection(Z_OBJ_P(ZEND_THIS))

void pt_register_called_on_type_unresolved_method_prototype_reflection()
{
	static const char *selfClass = "PHPStan\\Reflection\\Type\\CalledOnTypeUnresolvedMethodPrototypeReflection";
	static const char *extendedMethodReflectionClass = "PHPStan\\Reflection\\ExtendedMethodReflection";

	reg::Class cls("PHPStan\\Reflection\\Type\\CalledOnTypeUnresolvedMethodPrototypeReflection");
	ptdecl::CalledOnTypeUnresolvedMethodPrototypeReflection::declareClass(cls);
	/* the twin's slots in its order (OBJ_PROP_NUM): the two memos defaulting
	 * to null, then the promoted parameters, typed, uninitialized until the
	 * constructor runs */
	cls.privateTypedClassPropertyDefaultNull("transformedMethod", extendedMethodReflectionClass);
	cls.privateTypedClassPropertyDefaultNull("cachedDoNotResolveTemplateTypeMapToBounds", selfClass);
	cls.privateTypedClassProperty("methodReflection", extendedMethodReflectionClass, false);
	cls.privateTypedClassProperty("resolvedDeclaringClass", ptcls::classReflection, false);
	cls.privateTypedProperty("resolveTemplateTypeMapToBounds", MAY_BE_BOOL);
	cls.privateTypedClassProperty("calledOnType", ptcls::type, false);

	cls.method<&CalledOnTypeUnresolvedMethodPrototypeReflection::construct, zp::Obj, zp::Obj, zp::Bool, zp::Obj>(sigs::__construct);

	cls.method<&CalledOnTypeUnresolvedMethodPrototypeReflection::doNotResolveTemplateTypeMapToBounds>(sigs::doNotResolveTemplateTypeMapToBounds);

	cls.method<&CalledOnTypeUnresolvedMethodPrototypeReflection::getNakedMethod>(sigs::getNakedMethod);
	cls.op<PT_OP_GET_NAKED_METHOD, &CalledOnTypeUnresolvedMethodPrototypeReflection::getNakedMethod>();

	cls.method<&CalledOnTypeUnresolvedMethodPrototypeReflection::getTransformedMethod>(sigs::getTransformedMethod);
	cls.op<PT_OP_GET_TRANSFORMED_METHOD, &CalledOnTypeUnresolvedMethodPrototypeReflection::getTransformedMethod>();

	cls.method<&CalledOnTypeUnresolvedMethodPrototypeReflection::withCalledOnType, zp::Obj>(sigs::withCalledOnType);

	cls.shadow(&pt_ce_called_on_type_unresolved_method_prototype_reflection);
}

/* }}} */
