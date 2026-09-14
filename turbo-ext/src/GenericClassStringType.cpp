/*
 * PHPStanTurbo\GenericClassStringType — native implementation of
 * PHPStan\Type\Generic\GenericClassStringType.
 *
 * Declared as PHPStan\Type\Generic\GenericClassStringType itself at
 * activation: not final, extending the native ClassStringType (declared
 * first — Shadow.cpp materialises a parent plan before its child). State
 * is the twin's `private Type $type`, a declared class-typed property slot
 * (IS_PROP_UNINIT until the constructor writes it), so the std object
 * handlers do GC/clone. The twin uses no traits of its own; everything it
 * does not declare is inherited from ClassStringType and StringType.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it — with a direct C++ call when
 * the object is exactly a GenericClassStringType; `parent::` calls go to
 * the parents' native bodies directly.
 */

#include "TypeTraits.h"
#include "generated/GenericClassStringType.h"

namespace slots = ptdecl::GenericClassStringType::slot;
namespace sigs = ptdecl::GenericClassStringType::sig;

zend_class_entry *pt_ce_generic_class_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\GenericClassStringType. State lives in the
 * PHP object's $type. */
class GenericClassStringType
{
public:
	explicit GenericClassStringType(zend_object *self) : self(self) {}

	/* __construct(private Type $type): initializes the typed slot;
	 * parent::__construct() is ClassStringType's, which only calls
	 * StringType's empty one */
	void construct(zval *type)
	{
		zval *slot = OBJ_PROP_NUM(self, slots::type);
		ZVAL_COPY(slot, type);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* new self($type); UNDEF = pending exception */
	static zv::Val create(zval *type)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_generic_class_string_type) != SUCCESS)) return zv::Val();
		GenericClassStringType(Z_OBJ(object)).construct(type);
		return zv::Val::adopt(object);
	}

	/* $this->type (borrowed); NULL with an Error pending when the
	 * constructor never ran (ReflectionClass::newInstanceWithoutConstructor())
	 * — the twin's typed-property read raises the same */
	[[nodiscard]] zval *type() const { return typeOf(self); }

	/* $this->type->getReferencedClasses() */
	zv::Val getReferencedClasses() const { return callOnType(PT_LC("getreferencedclasses"), 0, NULL); }

	zv::Val getGenericType() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(t));
	}

	/* new ClassNameToObjectTypeResult($this->getGenericType(), true) */
	zv::Val toObjectTypeForInstanceofCheck() const
	{
		zv::Val generic = thisGenericType();
		if (UNEXPECTED(generic.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(generic.raw(), true);
	}

	/* new ClassNameToObjectTypeResult(TypeCombinator::union($this->getGenericType(), $this), false)
	 * when strings are allowed, new ClassNameToObjectTypeResult($this->getGenericType(), false)
	 * otherwise; UNDEF = pending exception */
	zv::Val toObjectTypeForIsACheck(bool allowString) const
	{
		zv::Val generic = thisGenericType();
		if (UNEXPECTED(generic.isUndef())) return zv::Val();
		if (allowString) {
			zv::Args args{generic.raw(), self};
			zv::Val unionType = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
			if (UNEXPECTED(unionType.isUndef())) return zv::Val();
			return classNameToObjectTypeResult(unionType.raw(), false);
		}
		return classNameToObjectTypeResult(generic.raw(), false);
	}

	/* $this->getGenericType() */
	zv::Val getClassStringObjectType() const { return thisGenericType(); }

	/* $this->getClassStringObjectType() */
	zv::Val getObjectTypeOrClassStringObjectType() const
	{
		if (EXPECTED(isExact())) return getClassStringObjectType();
		return pt_type_call(self, PT_LC("getclassstringobjecttype"), 0, NULL);
	}

	/* sprintf('%s<%s>', parent::describe($level), $this->type->describe($level))
	 * — ClassStringType's describe() is the literal 'class-string' */
	zv::Val describe(zval *level) const
	{
		zv::Val inner = callOnType(PT_LC("describe"), 1, level);
		if (UNEXPECTED(inner.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(inner.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		return zv::Val::adoptString(zend_strpprintf(0, "class-string<%s>", ZSTR_VAL(zv::Ref(inner.raw()).asString())));
	}

	/* the CompoundType callback; for a ConstantStringType no unless it is a
	 * class-string, then its ObjectType; another GenericClassStringType's
	 * generic type; ObjectWithoutClassType for a ClassStringType; maybe for
	 * a StringType; no otherwise — the object type going through
	 * $this->type->accepts(); UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		zv::Val objectType;
		zend_class_entry *typeCe = Z_OBJCE_P(type);
		if (instanceof_function(typeCe, pt_ce_constant_string_type)) {
			zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isclassstring"), 0, NULL);
			if (UNEXPECTED(isClassString < 0)) return zv::Val();
			if (isClassString != PT_TRI_YES) return pt_type_accepts_result(PT_TRI_NO);
			objectType = objectTypeOfConstantString(Z_OBJ_P(type));
		} else if (instanceof_function(typeCe, pt_ce_generic_class_string_type)) {
			zval *other = typeOf(Z_OBJ_P(type));
			if (UNEXPECTED(other == NULL)) return zv::Val();
			objectType = zv::Val::copyOf(zv::Ref(other));
		} else if (instanceof_function(typeCe, pt_ce_class_string_type)) {
			objectType = pt_type_new_object_without_class_type();
		} else if (instanceof_function(typeCe, pt_ce_string_type)) {
			return pt_type_accepts_result(PT_TRI_MAYBE);
		} else {
			return pt_type_accepts_result(PT_TRI_NO);
		}
		if (UNEXPECTED(objectType.isUndef())) return zv::Val();

		zv::Args args{objectType.raw(), strictTypes};
		return callOnType(PT_LC("accepts"), 2, args);
	}

	/* the CompoundType callback; for a ConstantStringType yes under a mixed
	 * generic type, else the generic type's (a StaticType's object type, a
	 * TemplateType's bound) verdict on its ObjectType, and'ed with maybe
	 * unless it is a class-string; the generic types' verdict for another
	 * GenericClassStringType; the verdict on ObjectWithoutClassType for a
	 * ClassStringType; maybe for a StringType; no otherwise; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		zend_class_entry *typeCe = Z_OBJCE_P(type);
		if (instanceof_function(typeCe, pt_ce_constant_string_type)) {
			zval *ownType = this->type();
			if (UNEXPECTED(ownType == NULL)) return zv::Val();
			zv::Val genericType = zv::Val::copyOf(zv::Ref(ownType));
			if (zv::Ref(genericType.raw()).instanceOf(pt_ce_mixed_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

			/* $genericType instanceof StaticType — the shadowing class */
			bool isStatic = zv::Ref(genericType.raw()).instanceOf(pt_ce_static_type);
			if (isStatic) {
				genericType = pt_type_call(Z_OBJ_P(genericType.raw()), PT_LC("getstaticobjecttype"), 0, NULL);
				if (UNEXPECTED(genericType.isUndef())) return zv::Val();
			}

			/* We are transforming constant class-string to ObjectType. But
			 * we need to filter out an uncertainty originating in possible
			 * ObjectType's class subtypes. */
			zv::Val objectType = objectTypeOfConstantString(Z_OBJ_P(type));
			if (UNEXPECTED(objectType.isUndef())) return zv::Val();

			/* Do not use TemplateType's isSuperTypeOf handling directly
			 * because it takes ObjectType uncertainty into account. */
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(genericType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			zv::Val isSuperType;
			if (isTemplate) {
				zv::Val bound = pt_type_call(Z_OBJ_P(genericType.raw()), PT_LC("getbound"), 0, NULL);
				if (UNEXPECTED(bound.isUndef())) return zv::Val();
				isSuperType = pt_type_call(Z_OBJ_P(bound.raw()), PT_LC("issupertypeof"), 1, objectType.raw());
			} else {
				isSuperType = pt_type_call(Z_OBJ_P(genericType.raw()), PT_LC("issupertypeof"), 1, objectType.raw());
			}
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();

			zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isclassstring"), 0, NULL);
			if (UNEXPECTED(isClassString < 0)) return zv::Val();
			if (isClassString != PT_TRI_YES) {
				/* $isSuperType->and(IsSuperTypeOfResult::createMaybe()) */
				zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
				if (UNEXPECTED(maybe.isUndef())) return zv::Val();
				isSuperType = pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("and"), 1, maybe.raw());
			}

			return isSuperType;
		}
		if (instanceof_function(typeCe, pt_ce_generic_class_string_type)) {
			zval *other = typeOf(Z_OBJ_P(type));
			if (UNEXPECTED(other == NULL)) return zv::Val();
			return callOnType(PT_LC("issupertypeof"), 1, other);
		}
		if (instanceof_function(typeCe, pt_ce_class_string_type)) {
			zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
			if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
			return callOnType(PT_LC("issupertypeof"), 1, objectWithoutClass.raw());
		}
		if (instanceof_function(typeCe, pt_ce_string_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $this when $cb($this->type) returns $this->type itself, new
	 * self($newType) otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval arg;
		ZVAL_COPY_VALUE(&arg, t);
		zval newTypeRaw;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &newTypeRaw))) return zv::Val();
		return replaced(zv::Val::adopt(newTypeRaw));
	}

	/* $this when $cb($this->type, $right->getClassStringObjectType())
	 * returns $this->type itself, new self($newType) otherwise; UNDEF =
	 * pending exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val rightObject = pt_type_call(Z_OBJ_P(right), PT_LC("getclassstringobjecttype"), 0, NULL);
		if (UNEXPECTED(rightObject.isUndef())) return zv::Val();
		zv::Args args{t, rightObject.raw()};
		zval newTypeRaw;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newTypeRaw))) return zv::Val();
		return replaced(zv::Val::adopt(newTypeRaw));
	}

	/* the union/intersection callback; $this->type->inferTemplateTypes()
	 * on a ConstantStringType's ObjectType, another GenericClassStringType's
	 * generic type, or — for any other class-string — the own generic type
	 * (a TemplateType's bound) intersected with ObjectWithoutClassType;
	 * the empty map otherwise; UNDEF = pending exception */
	zv::Val inferTemplateTypes(zval *receivedType) const
	{
		bool isUnion, isIntersection = false;
		if (UNEXPECTED(!pt_type_instanceof(receivedType, PT_CLASS_UNION_TYPE, isUnion))) return zv::Val();
		if (!isUnion && UNEXPECTED(!pt_type_instanceof(receivedType, PT_CLASS_INTERSECTION_TYPE, isIntersection))) return zv::Val();
		if (isUnion || isIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(receivedType), PT_LC("infertemplatetypeson"), 1, &selfZv);
		}

		zv::Val typeToInfer;
		zend_class_entry *receivedCe = Z_OBJCE_P(receivedType);
		if (instanceof_function(receivedCe, pt_ce_constant_string_type)) {
			typeToInfer = objectTypeOfConstantString(Z_OBJ_P(receivedType));
		} else if (instanceof_function(receivedCe, pt_ce_generic_class_string_type)) {
			zval *other = typeOf(Z_OBJ_P(receivedType));
			if (UNEXPECTED(other == NULL)) return zv::Val();
			typeToInfer = zv::Val::copyOf(zv::Ref(other));
		} else {
			zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(receivedType), PT_LC("isclassstring"), 0, NULL);
			if (UNEXPECTED(isClassString < 0)) return zv::Val();
			if (isClassString != PT_TRI_YES) return pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_MAP, PT_LC("createempty"), 0, NULL);
			zval *t = type();
			if (UNEXPECTED(t == NULL)) return zv::Val();
			typeToInfer = zv::Val::copyOf(zv::Ref(t));
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(typeToInfer.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (isTemplate) {
				typeToInfer = pt_type_call(Z_OBJ_P(typeToInfer.raw()), PT_LC("getbound"), 0, NULL);
				if (UNEXPECTED(typeToInfer.isUndef())) return zv::Val();
			}
			zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
			if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
			zv::Args args{typeToInfer.raw(), objectWithoutClass.raw()};
			typeToInfer = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("intersect"), 2, args);
		}
		if (UNEXPECTED(typeToInfer.isUndef())) return zv::Val();

		return callOnType(PT_LC("infertemplatetypes"), 1, typeToInfer.raw());
	}

	/* $this->type->getReferencedTemplateTypes($positionVariance->compose(TemplateTypeVariance::createCovariant())) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zv::Val covariant = pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_VARIANCE, PT_LC("createcovariant"), 0, NULL);
		if (UNEXPECTED(covariant.isUndef())) return zv::Val();
		zv::Val variance = pt_type_call(Z_OBJ_P(positionVariance), PT_LC("compose"), 1, covariant.raw());
		if (UNEXPECTED(variance.isUndef())) return zv::Val();
		return callOnType(PT_LC("getreferencedtemplatetypes"), 1, variance.raw());
	}

	/* another GenericClassStringType of the same class (parent::equals() is
	 * JustNullableTypeTrait's get_class($type) === static::class) whose
	 * generic type equals this one's; false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_generic_class_string_type)) {
			out = false;
			return true;
		}
		if (Z_OBJCE_P(type) != self->ce) {
			out = false;
			return true;
		}
		zval *other = typeOf(Z_OBJ_P(type));
		if (UNEXPECTED(other == NULL)) return false;
		zv::Val equal = callOnType(PT_LC("equals"), 1, other);
		if (UNEXPECTED(equal.isUndef())) return false;
		out = zend_is_true(equal.raw());
		return true;
	}

	/* new GenericTypeNode(new IdentifierTypeNode('class-string'), [$this->type->toPhpDocNode()]) */
	zv::Val toPhpDocNode() const
	{
		zv::Val name = zv::Val::string("class-string", sizeof("class-string") - 1);
		zv::Val identifier = pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
		if (UNEXPECTED(identifier.isUndef())) return zv::Val();
		zv::Val inner = callOnType(PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(inner.isUndef())) return zv::Val();
		zv::Arr genericTypes = zv::Arr::create(1);
		genericTypes.push(std::move(inner));
		zv::Args args{identifier.raw(), genericTypes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
	}

	/* for a constant class-string: never when the generic type is one
	 * final class of that name, the generic type minus the ObjectType for a
	 * sealed hierarchy (never when nothing remains, $this when nothing
	 * changed), or minus the ObjectType of a final class when the generic
	 * type names several classes; parent::tryRemove() (StringType's)
	 * otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_string_type)) {
			zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(typeToRemove), PT_LC("isclassstring"), 0, NULL);
			if (UNEXPECTED(isClassString < 0)) return zv::Val();
			if (isClassString == PT_TRI_YES) {
				zv::Val generic = thisGenericType();
				if (UNEXPECTED(generic.isUndef())) return zv::Val();

				zv::Val genericObjectClassNames = pt_type_call(Z_OBJ_P(generic.raw()), PT_LC("getobjectclassnames"), 0, NULL);
				if (UNEXPECTED(genericObjectClassNames.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(genericObjectClassNames.raw()).isArray())) {
					zend_type_error("phpstan_turbo: %s::getObjectClassNames() must return array", ZSTR_VAL(Z_OBJCE_P(generic.raw())->name));
					return zv::Val();
				}
				zv::Val reflectionProvider = pt_type_call_static(PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
				if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
				zend_object *provider = Z_OBJ_P(reflectionProvider.raw());
				zv::Val removedValue = pt_constant_string_get_value(Z_OBJ_P(typeToRemove));
				if (UNEXPECTED(removedValue.isUndef())) return zv::Val();

				zv::ArrRef names(genericObjectClassNames.raw());
				if (names.size() == 1) {
					/* $genericObjectClassNames[0] */
					zv::Ref className = names.findIndex(0);
					if (UNEXPECTED(className.raw() == NULL)) {
						zend_error(E_WARNING, "Undefined array key 0");
						if (UNEXPECTED(EG(exception))) return zv::Val();
					}
					zval nullZv;
					ZVAL_NULL(&nullZv);
					zval *classNameZv = className.raw() != NULL ? className.raw() : &nullZv;
					zv::Val hasClass = pt_type_call(provider, PT_LC("hasclass"), 1, classNameZv);
					if (UNEXPECTED(hasClass.isUndef())) return zv::Val();
					if (zend_is_true(hasClass.raw())) {
						zv::Val classReflection = pt_type_call(provider, PT_LC("getclass"), 1, classNameZv);
						if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
						zv::Val isFinal = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("isfinal"), 0, NULL);
						if (UNEXPECTED(isFinal.isUndef())) return zv::Val();
						if (zend_is_true(isFinal.raw()) && zend_is_identical(classNameZv, removedValue.raw())) return pt_type_new_never_type();

						zv::Val allowedSubTypes = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getallowedsubtypes"), 0, NULL);
						if (UNEXPECTED(allowedSubTypes.isUndef())) return zv::Val();
						if (!zv::Ref(allowedSubTypes.raw()).isNull()) {
							zv::Val objectTypeToRemove = pt_type_new_object_type(removedValue.raw());
							if (UNEXPECTED(objectTypeToRemove.isUndef())) return zv::Val();
							zv::Args args{generic.raw(), objectTypeToRemove.raw()};
							zv::Val remainingType = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("remove"), 2, args);
							if (UNEXPECTED(remainingType.isUndef())) return zv::Val();
							if (zv::Ref(remainingType.raw()).instanceOf(pt_ce_never_type)) return pt_type_new_never_type();

							zv::Val equal = pt_type_call(Z_OBJ_P(remainingType.raw()), PT_LC("equals"), 1, generic.raw());
							if (UNEXPECTED(equal.isUndef())) return zv::Val();
							if (!zend_is_true(equal.raw())) return create(remainingType.raw());
						}
					}
				} else if (names.size() > 1) {
					zv::Val objectTypeToRemove = pt_type_new_object_type(removedValue.raw());
					if (UNEXPECTED(objectTypeToRemove.isUndef())) return zv::Val();
					zv::Val hasClass = pt_type_call(provider, PT_LC("hasclass"), 1, removedValue.raw());
					if (UNEXPECTED(hasClass.isUndef())) return zv::Val();
					if (zend_is_true(hasClass.raw())) {
						zv::Val classReflection = pt_type_call(provider, PT_LC("getclass"), 1, removedValue.raw());
						if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
						zv::Val isFinal = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("isfinal"), 0, NULL);
						if (UNEXPECTED(isFinal.isUndef())) return zv::Val();
						if (zend_is_true(isFinal.raw())) {
							zv::Args args{generic.raw(), objectTypeToRemove.raw()};
							zv::Val remainingType = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("remove"), 2, args);
							if (UNEXPECTED(remainingType.isUndef())) return zv::Val();
							if (zv::Ref(remainingType.raw()).instanceOf(pt_ce_never_type)) return pt_type_new_never_type();

							return create(remainingType.raw());
						}
					}
				}
			}
		}

		/* parent::tryRemove($typeToRemove) — StringType's, ClassStringType
		 * declares none */
		return pt_string_type_try_remove(self, typeToRemove);
	}

	/* $this->type->hasTemplateOrLateResolvableType(); false = pending
	 * exception */
	[[nodiscard]] bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zv::Val result = callOnType(PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

private:
	zend_object *self;

	/* exactly a GenericClassStringType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_generic_class_string_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $object->type — the private slot declared here, read directly on a
	 * subclass too (as `$type->type` does); NULL = pending exception */
	[[nodiscard]] static zval *typeOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::type);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$type must not be accessed before initialization", ZSTR_VAL(pt_ce_generic_class_string_type->name));
			return NULL;
		}
		return slot;
	}

	/* $this->type->method(...$args); UNDEF = pending exception */
	zv::Val callOnType(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), lcname, len, argc, argv);
	}

	/* $this->getGenericType() — through the object's class */
	zv::Val thisGenericType() const
	{
		if (EXPECTED(isExact())) return getGenericType();
		return pt_type_call(self, PT_LC("getgenerictype"), 0, NULL);
	}

	/* new ObjectType($type->getValue()) for a ConstantStringType */
	static zv::Val objectTypeOfConstantString(zend_object *constantString)
	{
		zv::Val value = pt_constant_string_get_value(constantString);
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		return pt_type_new_object_type(value.raw());
	}

	/* new ClassNameToObjectTypeResult($type, $uncertainty) */
	static zv::Val classNameToObjectTypeResult(zval *type, bool uncertainty)
	{
		zv::Args args{type, uncertainty};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	/* the tail of traverse()/traverseSimultaneously(): $this when the
	 * callback returned $this->type itself, new self($newType) otherwise
	 * (the constructor's Type parameter rejects anything else, as the
	 * twin's does) */
	zv::Val replaced(zv::Val newType) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		if (zv::Ref(newType.raw()).isObject() && Z_OBJ_P(newType.raw()) == Z_OBJ_P(t)) return thisValue();
		bool isType;
		if (UNEXPECTED(!pt_type_instanceof(newType.raw(), PT_CLASS_TYPE, isType))) return zv::Val();
		if (UNEXPECTED(!isType)) {
			zend_type_error("%s::__construct(): Argument #1 ($type) must be of type %s, %s given", ZSTR_VAL(pt_ce_generic_class_string_type->name), "PHPStan\\Type\\Type", zend_zval_value_name(newType.raw()));
			return zv::Val();
		}
		return create(newType.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::GenericClassStringType;

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS GenericClassStringType(Z_OBJ_P(ZEND_THIS))

void pt_register_generic_class_string_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\GenericClassStringType");
	ptdecl::GenericClassStringType::declareClass(cls);
	/* "type" must stay the first declared property (slots::type) */
	ptdecl::GenericClassStringType::declareProperties(cls);

	cls.method<&GenericClassStringType::construct, zp::Obj>(sigs::__construct);

	cls.method<&GenericClassStringType::getReferencedClasses>(sigs::getReferencedClasses);

	cls.method<&GenericClassStringType::getGenericType>(sigs::getGenericType);

	cls.method<&GenericClassStringType::toObjectTypeForInstanceofCheck>(sigs::toObjectTypeForInstanceofCheck);

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		(void) allowSameClass;
		PT_RETURN_VAL(PT_THIS.toObjectTypeForIsACheck(allowString));
	});

	cls.method<&GenericClassStringType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&GenericClassStringType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method<&GenericClassStringType::describe, zp::Obj>(sigs::describe);

	cls.method<&GenericClassStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&GenericClassStringType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method<&GenericClassStringType::inferTemplateTypes, zp::Obj>(sigs::inferTemplateTypes);

	cls.method<&GenericClassStringType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&GenericClassStringType::equals, zp::Obj>(sigs::equals);

	cls.method<&GenericClassStringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&GenericClassStringType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&GenericClassStringType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	cls.shadow(&pt_ce_generic_class_string_type);
}

/* }}} */
