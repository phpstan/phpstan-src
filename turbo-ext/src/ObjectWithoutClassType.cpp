/*
 * PHPStanTurbo\ObjectWithoutClassType — native implementation of
 * PHPStan\Type\ObjectWithoutClassType.
 *
 * Declared as PHPStan\Type\ObjectWithoutClassType itself at activation: not
 * final (the PHP TemplateObjectWithoutClassType extends it — its
 * constructor calls parent::__construct(), so the constructor is a proper
 * method), implementing PHPStan\Type\SubtractableType. State is the twin's
 * `private ?Type $subtractedType`, a declared typed property slot
 * (IS_PROP_UNINIT until the constructor writes it), so the std object
 * handlers do GC/clone and a PHP subclass's own properties follow it. The
 * traits the twin is composed of come from the shared registrars in
 * TypeTraits.cpp (ObjectTypeTrait's `use` chain spelled out), run after the
 * class's own methods so the class body wins over the traits exactly as in
 * PHP.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it
 * (TemplateObjectWithoutClassType's getClassStringType(), or a compound
 * trait's isSuperTypeOf()) — with a direct C++ call when the object is
 * exactly an ObjectWithoutClassType. The private slot of another instance
 * (`$type->subtractedType`) is read directly, as the twin does from inside
 * the class.
 */

#include "TypeTraits.h"
#include "generated/ObjectWithoutClassType.h"

namespace slots = ptdecl::ObjectWithoutClassType::slot;
namespace sigs = ptdecl::ObjectWithoutClassType::sig;

zend_class_entry *pt_ce_object_without_class_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ObjectWithoutClassType. State lives in the PHP
 * object's $subtractedType. */
class ObjectWithoutClassType
{
public:
	explicit ObjectWithoutClassType(zend_object *self) : self(self) {}

	/* __construct(?Type $subtractedType = null): a NeverType subtracted type
	 * is dropped; $subtractedType borrowed, NULL for null */
	void construct(zval *subtractedType)
	{
		if (subtractedType != NULL && instanceof_function(Z_OBJCE_P(subtractedType), pt_ce_never_type)) {
			subtractedType = NULL;
		}
		zval *slot = OBJ_PROP_NUM(self, slots::subtractedType);
		/* the slot is overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, slot);
		if (subtractedType == NULL) {
			ZVAL_NULL(slot);
		} else {
			ZVAL_COPY(slot, subtractedType);
		}
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* new self($subtractedType) — exactly the class, as the twin's `new
	 * self` sites spell it; UNDEF = pending exception */
	static zv::Val create(zval *subtractedType = NULL)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_object_without_class_type) != SUCCESS)) return zv::Val();
		ObjectWithoutClassType(Z_OBJ(object)).construct(subtractedType);
		return zv::Val::adopt(object);
	}

	/* $this->subtractedType (borrowed, IS_NULL or IS_OBJECT); NULL with an
	 * Error pending when the constructor never ran */
	zval *subtractedType() const { return subtractedTypeOf(self); }

	/* $type->subtractedType of another instance */
	static zval *subtractedTypeOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::subtractedType);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$subtractedType must not be accessed before initialization", ZSTR_VAL(pt_ce_object_without_class_type->name));
			return NULL;
		}
		return slot;
	}

	/* new ClassStringType() */
	static zv::Val getClassStringType()
	{
		return pt_val_of<pt_class_string_type_new>();
	}

	/* the CompoundType callback; yes for an object type of any kind (this
	 * class, an object shape, a class name), no otherwise; UNDEF = pending
	 * exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		bool isObject = instanceof_function(Z_OBJCE_P(type), pt_ce_object_without_class_type)
			|| instanceof_function(Z_OBJCE_P(type), pt_ce_object_shape_type);
		if (!isObject) {
			bool hasClassNames;
			if (UNEXPECTED(!objectClassNamesNotEmpty(type, hasClassNames))) return zv::Val();
			isObject = hasClassNames;
		}
		return pt_type_accepts_result(isObject ? PT_TRI_YES : PT_TRI_NO);
	}

	/* the CompoundType callback; against another instance by the subtracted
	 * types; yes for an object shape; no for a non-object; otherwise the
	 * negated answer of the subtracted type; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_object_without_class_type)) {
			zval *subtracted = subtractedType();
			if (UNEXPECTED(subtracted == NULL)) return zv::Val();
			if (Z_TYPE_P(subtracted) == IS_NULL) return pt_type_is_super_type_of_result(PT_TRI_YES);
			zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
			if (UNEXPECTED(typeSubtracted == NULL)) return zv::Val();
			if (Z_TYPE_P(typeSubtracted) != IS_NULL) {
				zv::Val isSuperType = pt_type_call(Z_OBJ_P(typeSubtracted), PT_LC("issupertypeof"), 1, subtracted);
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
				zend_long value = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(value < 0)) return zv::Val();
				if (value == PT_TRI_YES) return isSuperType;
			}
			return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_object_shape_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

		bool hasClassNames;
		if (UNEXPECTED(!objectClassNamesNotEmpty(type, hasClassNames))) return zv::Val();
		if (!hasClassNames) return pt_type_is_super_type_of_result(PT_TRI_NO);

		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return pt_type_is_super_type_of_result(PT_TRI_YES);

		/* $this->subtractedType->isSuperTypeOf($type)->negate() */
		zv::Val isSuperType = pt_type_call(Z_OBJ_P(subtracted), PT_LC("issupertypeof"), 1, type);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isSuperType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("negate"), 0, NULL);
	}

	/* another instance (of any subclass) with an equal subtracted type;
	 * false with an exception pending on an uninitialized slot */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_object_without_class_type)) {
			out = false;
			return true;
		}
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
		if (UNEXPECTED(typeSubtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			out = Z_TYPE_P(typeSubtracted) == IS_NULL;
			return true;
		}
		if (Z_TYPE_P(typeSubtracted) == IS_NULL) {
			out = false;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(subtracted), PT_LC("equals"), 1, typeSubtracted, out);
	}

	/* $level->handle(): 'object' for the type-only and value levels, with
	 * the subtracted type otherwise; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE) return zv::Val::string("object", 6);

		/* 'object' . $this->describeSubtractedType($this->subtractedType, $level) */
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val subtractedDescription;
		if (EXPECTED(pt_type_method_is(self, PT_LC("describesubtractedtype"), pt_type_trait_substractable_describe_subtracted_type))) {
			subtractedDescription = pt_type_describe_subtracted_type(subtracted, level);
		} else {
			zv::Args args{subtracted, level};
			subtractedDescription = pt_type_call(self, PT_LC("describesubtractedtype"), 2, args);
		}
		if (UNEXPECTED(subtractedDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(subtractedDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: describeSubtractedType() must return string");
			return zv::Val();
		}
		smart_str description = {NULL, 0};
		smart_str_appendl(&description, "object", 6);
		smart_str_append(&description, zv::Ref(subtractedDescription.raw()).asString());
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* never for another instance, else object without $type (unioned with
	 * what is already subtracted); UNDEF = pending exception */
	zv::Val subtract(zval *type) const
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_object_without_class_type)) return pt_type_new_never_type();
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val unioned;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Args args{subtracted, type};
			unioned = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
			if (UNEXPECTED(unioned.isUndef())) return zv::Val();
			type = unioned.raw();
		}
		return create(type);
	}

	/* new self() */
	static zv::Val getTypeWithoutSubtractedType() { return create(); }

	/* new self($subtractedType) */
	static zv::Val changeSubtractedType(zval *subtractedType) { return create(subtractedType); }

	/* new self($cb($this->subtractedType)) when the callback changed it,
	 * $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return thisValue();
		zval mapped;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, subtracted, &mapped))) return zv::Val();
		zv::Val mappedType = zv::Val::adopt(mapped);
		if (Z_TYPE(mapped) == IS_OBJECT && Z_OBJ(mapped) == Z_OBJ_P(subtracted)) return thisValue();
		return create(Z_TYPE(mapped) == IS_NULL ? NULL : mappedType.raw());
	}

	/* $this without a subtracted type, new self() with one; UNDEF = pending
	 * exception */
	zv::Val traverseSimultaneously() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return thisValue();
		return create();
	}

	/* null when $this->isSuperTypeOf($typeToRemove) is no, else
	 * $this->subtract($typeToRemove); UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		zend_long isSuperType = thisIsSuperTypeOf(typeToRemove);
		if (UNEXPECTED(isSuperType < 0)) return zv::Val();
		if (isSuperType == PT_TRI_NO) return zv::Val::null();
		if (EXPECTED(isExact())) return subtract(typeToRemove);
		return pt_type_call(self, PT_LC("subtract"), 1, typeToRemove);
	}

	/* TypeCombinator::union($this, $exponent) unless the exponent is never
	 * or no subtype of $this, float|int (benevolent) otherwise; UNDEF =
	 * pending exception */
	zv::Val exponentiate(zval *exponent) const
	{
		if (!instanceof_function(Z_OBJCE_P(exponent), pt_ce_never_type)) {
			zend_long isSuperType = thisIsSuperTypeOf(exponent);
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType != PT_TRI_NO) {
				zv::Args args{self, exponent};
				return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
			}
		}
		/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
		zval floatRaw, integerRaw;
		if (UNEXPECTED(!pt_float_type_new(&floatRaw))) return zv::Val();
		zv::Val floatType = zv::Val::adopt(floatRaw);
		if (UNEXPECTED(!pt_integer_type_new(&integerRaw))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(floatType));
		types.push(zv::Val::adopt(integerRaw));
		return pt_type_new(PT_CLASS_BENEVOLENT_UNION_TYPE, 1, types.raw());
	}

	/* new IdentifierTypeNode('object') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("object", 6);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	/* exactly an ObjectWithoutClassType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_object_without_class_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $type->getObjectClassNames() !== []; false = pending exception */
	[[nodiscard]] static bool objectClassNamesNotEmpty(zval *type, bool &out)
	{
		zv::Val classNames = pt_type_call(Z_OBJ_P(type), PT_LC("getobjectclassnames"), 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) return false;
		out = !zv::Ref(classNames.raw()).isArray() || zv::ArrRef(classNames.raw()).size() != 0;
		return true;
	}

	/* $this->isSuperTypeOf($type)'s trinary — through the object's class;
	 * -1 = pending exception */
	[[nodiscard]] zend_long thisIsSuperTypeOf(zval *type) const
	{
		zv::Val result = isExact() ? isSuperTypeOf(type) : pt_type_call(self, PT_LC("issupertypeof"), 1, type);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::ObjectWithoutClassType;

bool pt_object_without_class_type_new(zval *out, zval *subtractedType)
{
	return pt_val_into(ObjectWithoutClassType::create(subtractedType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ObjectWithoutClassType(Z_OBJ_P(ZEND_THIS))

static void ZEND_FASTCALL owctEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

void pt_register_object_without_class_type()
{
	reg::Class cls("PHPStan\\Type\\ObjectWithoutClassType");
	ptdecl::ObjectWithoutClassType::declareClass(cls);
	/* "subtractedType" must stay the first declared property (slots::subtractedType) */
	ptdecl::ObjectWithoutClassType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subtractedType = NULL;
		if (!zp::parse<zp::Opt<zp::ObjOrNull>>(execute_data, subtractedType)) RETURN_THROWS();
		PT_THIS.construct(subtractedType);
	});

	cls.method(sigs::getReferencedClasses, owctEmptyArray0);
	cls.method(sigs::getObjectClassNames, owctEmptyArray0);
	cls.method(sigs::getObjectClassReflections, owctEmptyArray0);

	cls.method<&ObjectWithoutClassType::getClassStringType>(sigs::getClassStringType);

	cls.method<&ObjectWithoutClassType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&ObjectWithoutClassType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&ObjectWithoutClassType::equals, zp::Obj>(sigs::equals);

	cls.method<&ObjectWithoutClassType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::getEnumCases, owctEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});

	cls.method<&ObjectWithoutClassType::subtract, zp::Obj>(sigs::subtract);

	cls.method<&ObjectWithoutClassType::getTypeWithoutSubtractedType>(sigs::getTypeWithoutSubtractedType);

	cls.method<&ObjectWithoutClassType::changeSubtractedType, zp::ObjOrNull>(sigs::changeSubtractedType);

	cls.method(sigs::getSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *subtracted = PT_THIS.subtractedType();
		if (UNEXPECTED(subtracted == NULL)) RETURN_THROWS();
		RETURN_COPY(subtracted);
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously());
	});

	cls.method<&ObjectWithoutClassType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&ObjectWithoutClassType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method(sigs::getFiniteTypes, owctEmptyArray0);

	cls.method<&ObjectWithoutClassType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (ObjectTypeTrait brings the
	 * MaybeCallable, MaybeIterable, MaybeOffsetAccessible, NonArray and
	 * TruthyBoolean traits with it); the class body above wins over every
	 * name it declares */
	pt_type_trait_object(cls);
	pt_type_trait_maybe_callable(cls);
	pt_type_trait_maybe_iterable(cls);
	pt_type_trait_maybe_offset_accessible(cls);
	pt_type_trait_non_array(cls);
	pt_type_trait_truthy_boolean(cls);
	pt_type_trait_non_generic(cls);
	pt_type_trait_undecided_comparison(cls);
	pt_type_trait_non_generalizable(cls);
	pt_type_trait_substractable(cls);

	cls.shadow(&pt_ce_object_without_class_type);
}

/* }}} */
