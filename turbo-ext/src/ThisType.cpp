/*
 * PHPStanTurbo\ThisType — native implementation of PHPStan\Type\ThisType.
 *
 * Declared as PHPStan\Type\ThisType itself at activation: not final,
 * extending the native StaticType (declared first — Shadow.cpp materialises
 * a parent plan before its child). The twin declares no property and no
 * trait of its own; everything it does not override is inherited from
 * StaticType. Its `parent::` calls go to StaticType's native bodies
 * directly (pt_static_type_* in TypeTraits.h), run on the ThisType object
 * as PHP runs them, so the $this-calls inside answer as ThisType's own
 * methods do.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a PHP subclass may have overridden it — with the direct path when
 * the method is StaticType's own.
 */

#include "TypeTraits.h"
#include "generated/ThisType.h"

namespace sigs = ptdecl::ThisType::sig;

zend_class_entry *pt_ce_this_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ThisType. State lives in StaticType's slots. */
class ThisType
{
public:
	explicit ThisType(zend_object *self) : self(self) {}

	/* __construct(ClassReflection $classReflection, ?Type $subtractedType = null):
	 * parent::__construct($classReflection, $subtractedType) */
	void construct(zval *classReflection, zval *subtractedType)
	{
		pt_static_type_construct(self, classReflection, subtractedType);
	}

	/* new self($classReflection, $subtractedType) — exactly the class, as
	 * the twin's `new self` sites spell it; UNDEF = pending exception */
	static zv::Val create(zval *classReflection, zval *subtractedType = NULL)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_this_type) != SUCCESS)) return zv::Val();
		ThisType(Z_OBJ(object)).construct(classReflection, subtractedType);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* new self($classReflection, $this->getSubtractedType()); UNDEF =
	 * pending exception */
	zv::Val changeBaseClass(zval *classReflection) const
	{
		zv::Val subtracted = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
		return create(classReflection, subtracted.isNull() ? NULL : subtracted.raw());
	}

	/* sprintf('$this(%s)', $this->getStaticObjectType()->describe($level));
	 * UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zv::Val staticObject = pt_static_type_this_static_object_type(self);
		if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
		zv::Val inner = pt_type_op(Z_OBJ_P(staticObject.raw()), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(inner.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(inner.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		smart_str description = {NULL, 0};
		smart_str_appendl(&description, "$this(", sizeof("$this(") - 1);
		smart_str_append(&description, zv::Ref(inner.raw()).asString());
		smart_str_appendc(&description, ')');
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* the static object type's answer for another ThisType; the
	 * CompoundType callback; else a plain StaticType over the same class
	 * answers, held to maybe; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_this_type)) {
			zv::Val staticObject = pt_static_type_this_static_object_type(self);
			if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
			return pt_type_op(Z_OBJ_P(staticObject.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}

		/* $parent = new parent($this->getClassReflection(), $this->getSubtractedType()) */
		zv::Val classReflection = pt_static_type_this_class_reflection(self);
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Val subtracted = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
		zval parentRaw;
		if (UNEXPECTED(!pt_static_type_new(&parentRaw, classReflection.raw(), subtracted.isNull() ? NULL : subtracted.raw()))) return zv::Val();
		zv::Val parent = zv::Val::adopt(parentRaw);
		/* $parent->isSuperTypeOf($type)->and(IsSuperTypeOfResult::createMaybe()) —
		 * exactly a StaticType, its native body */
		zv::Val result = pt_static_type_is_super_type_of(Z_OBJ_P(parent.raw()), type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_op(Z_OBJ_P(result.raw()), PT_OP_AND, 1, maybe.raw());
	}

	/* parent::changeSubtractedType($subtractedType), re-wrapped as a
	 * ThisType over its class when it stayed a StaticType; UNDEF = pending
	 * exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		zv::Val type = pt_static_type_change_subtracted_type(self, subtractedType);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (zv::Ref(type.raw()).instanceOf(pt_ce_static_type)) {
			zv::Val classReflection = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("getclassreflection"), 0, NULL);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			return create(classReflection.raw(), Z_TYPE_P(subtractedType) == IS_NULL ? NULL : subtractedType);
		}
		return type;
	}

	/* new self($this->getClassReflection(), $cb($this->getSubtractedType()))
	 * when the callback changed it, $this otherwise; UNDEF = pending
	 * exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val subtracted = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
		zv::Val mappedType;
		if (!subtracted.isNull()) {
			zval mapped;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, subtracted.raw(), &mapped))) return zv::Val();
			mappedType = zv::Val::adopt(mapped);
		} else {
			mappedType = zv::Val::null();
		}
		/* $subtractedType !== $this->getSubtractedType() — the getter called
		 * again, as the twin does */
		zv::Val again = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(again.isUndef())) return zv::Val();
		if (zend_is_identical(mappedType.raw(), again.raw())) return thisValue();
		zv::Val classReflection = pt_static_type_this_class_reflection(self);
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		return create(classReflection.raw(), mappedType.isNull() ? NULL : mappedType.raw());
	}

	/* $this without a subtracted type, new self($this->getClassReflection())
	 * with one; UNDEF = pending exception */
	zv::Val traverseSimultaneously() const
	{
		zv::Val subtracted = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
		if (subtracted.isNull()) return thisValue();
		zv::Val classReflection = pt_static_type_this_class_reflection(self);
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		return create(classReflection.raw());
	}

	/* new ThisTypeNode() */
	static zv::Val toPhpDocNode() { return pt_type_new(PT_CLASS_THIS_TYPE_NODE, 0, NULL); }

	/* the literal class name for a class final by keyword ($this is pinned
	 * to it), parent::toClassConstantType()'s class-string<$this>
	 * otherwise; UNDEF = pending exception */
	zv::Val toClassConstantType() const
	{
		zv::Val reflection = pt_static_type_this_class_reflection(self);
		if (UNEXPECTED(reflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}
		zv::Val finalByKeyword = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("isfinalbykeyword"), 0, NULL);
		if (UNEXPECTED(finalByKeyword.isUndef())) return zv::Val();
		if (zend_is_true(finalByKeyword.raw())) {
			/* new ConstantStringType($reflection->getName(), true) */
			zv::Val name = pt_class_reflection_get_name(Z_OBJ_P(reflection.raw()));
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(name.raw()).isString())) {
				zend_type_error("phpstan_turbo: getName() must return string");
				return zv::Val();
			}
			zval result;
			if (UNEXPECTED(!pt_constant_string_type_new(&result, zv::Ref(name.raw()).asString(), true))) return zv::Val();
			return zv::Val::adopt(result);
		}
		return pt_static_type_to_class_constant_type(self);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }
};

} // namespace phpstanturbo

using phpstanturbo::ThisType;

bool pt_this_type_new(zval *out, zval *classReflection, zval *subtractedType)
{
	return pt_val_into(ThisType::create(classReflection, subtractedType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ThisType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_this_type)
{

	reg::Class cls("PHPStan\\Type\\ThisType");
	ptdecl::ThisType::declareClass(cls);
	ptdecl::ThisType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection, *subtractedType = NULL;
		if (!zp::parse<zp::Obj, zp::Opt<zp::TypeObjOrNull>>(execute_data, classReflection, subtractedType)) RETURN_THROWS();
		PT_THIS.construct(classReflection, subtractedType);
	});

	cls.method<&ThisType::changeBaseClass, zp::Obj>(sigs::changeBaseClass);
	cls.op<PT_OP_CHANGE_BASE_CLASS, &ThisType::changeBaseClass>();

	cls.method<&ThisType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &ThisType::describe>();

	cls.method<&ThisType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &ThisType::isSuperTypeOf>();

	cls.method(sigs::changeSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subtractedType;
		if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
		zval null;
		if (subtractedType == NULL) {
			ZVAL_NULL(&null);
			subtractedType = &null;
		}
		PT_RETURN_VAL(PT_THIS.changeSubtractedType(subtractedType));
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_with<ThisType>(self, argv); });

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously());
	});

	cls.method<&ThisType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.toClassConstantType());
	});

	cls.shadow(&pt_ce_this_type);
}

/* }}} */
