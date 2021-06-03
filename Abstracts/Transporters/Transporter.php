<?php

namespace Apiato\Core\Abstracts\Transporters;

use Apiato\Core\Abstracts\Requests\Request;
use Apiato\Core\Traits\SanitizerTrait;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionProperty;
use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\FlexibleDataTransferObject as Dto;

abstract class Transporter extends Dto
{
    use SanitizerTrait;

    /**
     * Override the Dto constructor to extend it for supporting Requests objects as $input.
     * Transporter constructor.
     *
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(Request | array $parameters = [])
    {
        // if the transporter got a Request object, get the content and headers
        // and pass them as array input to the Dto constructor.
        if ($parameters instanceof Request) {
            $content = $parameters->toArray();
            $headers = [
                '_headers' => $parameters->headers->all(),
                'request'  => $parameters,
            ];

            $parameters = array_merge($headers, $content);
        }

        $validators = $this->getFieldValidators();

        $valueCaster = $this->getValueCaster();

        /** string[] */
        $invalidTypes = [];

        foreach ($validators as $field => $validator) {
            if (
                ! isset($parameters[$field])
                && ! $validator->hasDefaultValue
                && ! $validator->isNullable
            ) {
                throw DataTransferObjectError::uninitialized(
                    static::class,
                    $field
                );
            }

            if (
                ! array_key_exists($field, $parameters)
                && $validator->isNullable
                && $this->ignoreMissing
            ) {
                continue;
            }

            $value = $parameters[$field] ?? $this->{$field} ?? null;

            $value = $this->castValue($valueCaster, $validator, $value);

            if (! $validator->isValidType($value)) {
                $invalidTypes[] = DataTransferObjectError::invalidTypeMessage(
                    static::class,
                    $field,
                    $validator->allowedTypes,
                    $value
                );

                continue;
            }

            $this->{$field} = $value;

            unset($parameters[$field]);
        }

        if ($invalidTypes) {
            DataTransferObjectError::invalidTypes($invalidTypes);
        }

        if (! $this->ignoreMissing && count($parameters)) {
            throw DataTransferObjectError::unknownProperties(array_keys($parameters), static::class);
        }
    }

    public function all(): array
    {
        $data = [];

        $class = new ReflectionClass(static::class);

        $properties = array_filter(
            $class->getProperties(ReflectionProperty::IS_PUBLIC),
            fn ($property) => $property->isInitialized($this)
        );

        foreach ($properties as $reflectionProperty) {
            // Skip static properties
            if ($reflectionProperty->isStatic()) {
                continue;
            }

            $data[$reflectionProperty->getName()] = $reflectionProperty->getValue($this);
        }

        return $data;
    }

    /**
     * This method mimics the $request->input() method but works on the "decoded" values.
     */
    public function getInputByKey(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->toArray(), $key, $default);
    }
}
