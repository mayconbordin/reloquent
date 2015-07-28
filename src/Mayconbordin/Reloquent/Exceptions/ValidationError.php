<?php namespace Mayconbordin\Reloquent\Exceptions;

use Illuminate\Validation\Validator;

/**
 * Describes errors in the input data.
 *
 * @package Mayconbordin\Reloquent\Exceptions
 */
class ValidationError extends RepositoryException
{
    /**
     * @var array Associative array with keys as field names and values as array of error messages.
     */
    protected $errors;

    /**
     * Create a new validation error with an associative array of errors.
     * @param array $errors
     */
    public function __construct(array $errors) {
        $this->errors = $errors;
    
        parent::__construct("Validation error", 0, null);
    }
    
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Create a new validation error with a single field error message.
     *
     * @param string $field
     * @param string $message
     * @return ValidationError
     */
    public static function withSingleError($field, $message)
    {
        return new ValidationError([$field => [$message]]);
    }

    /**
     * Create a new validation error with messages from a validator.
     *
     * @param Validator $validator
     * @return ValidationError
     */
    public static function fromValidator(Validator $validator)
    {
        return new ValidationError($validator->errors()->toArray());
    }
}
