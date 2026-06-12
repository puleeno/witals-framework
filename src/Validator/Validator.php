<?php

declare(strict_types=1);

namespace Witals\Framework\Validator;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class Validator implements ValidatorInterface
{
    protected SymfonyValidatorInterface $symfonyValidator;
    protected array $errors = [];
    protected array $customMessages = [];
    protected array $data = [];
    protected ?array $rulesIndex = null;
    protected static array $inlineRulesMap = [
        'confirmed' => true,
        'same' => true,
        'different' => true,
        'starts_with' => true,
        'ends_with' => true,
        'json' => true,
        'distinct' => true,
        'present' => true,
    ];

    protected static array $messages = [
        'required' => "The {field} field is required.",
        'email' => "The {field} must be a valid email address.",
        'min' => "The {field} must be at least {param}.",
        'max' => "The {field} must not exceed {param}.",
        'between' => "The {field} must be between {param}.",
        'string' => "The {field} must be a string.",
        'integer' => "The {field} must be an integer.",
        'numeric' => "The {field} must be a number.",
        'array' => "The {field} must be an array.",
        'boolean' => "The {field} must be a boolean.",
        'url' => "The {field} must be a valid URL.",
        'ip' => "The {field} must be a valid IP address.",
        'date' => "The {field} must be a valid date.",
        'regex' => "The {field} format is invalid.",
        'confirmed' => "The {field} confirmation does not match.",
        'same' => "The {field} must match {param}.",
        'different' => "The {field} must differ from {param}.",
        'in' => "The selected {field} is invalid.",
        'not_in' => "The selected {field} is invalid.",
        'alpha' => "The {field} may only contain letters.",
        'alpha_num' => "The {field} may only contain letters and numbers.",
        'alpha_dash' => "The {field} may only contain letters, numbers, dashes and underscores.",
        'phone' => "The {field} must be a valid phone number.",
        'starts_with' => "The {field} must start with {param}.",
        'ends_with' => "The {field} must end with {param}.",
        'uuid' => "The {field} must be a valid UUID.",
        'json' => "The {field} must be a valid JSON string.",
        'distinct' => "The {field} has a duplicate value.",
        'present' => "The {field} must be present.",
    ];

    public function __construct(?SymfonyValidatorInterface $validator = null)
    {
        $this->symfonyValidator = $validator ?? Validation::createValidator();
    }

    public function validate(array $data, array $rules): array
    {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $this->validateField($field, $ruleSet);
        }

        return $this->errors;
    }

    public function passed(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passed();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function setCustomMessages(array $messages): static
    {
        $this->customMessages = $messages;
        return $this;
    }

    protected function validateField(string $field, array|string $ruleSet): void
    {
        $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
        $value = $this->getValue($field);
        $isRequired = $rules['required'] ?? in_array('required', $rules, true);

        if (is_array($rules) && !isset($rules['required'])) {
            $rules = array_flip($rules);
            $isRequired = isset($rules['required']);
        }

        if (!$isRequired && $this->isEmpty($value) && !isset($rules['nullable'])) {
            return;
        }

        if ($isRequired && $this->isEmpty($value) && $value !== '0' && $value !== 0) {
            $this->addError($field, 'required');
            return;
        }

        if ($this->isEmpty($value) && isset($rules['nullable'])) {
            return;
        }

        $symfonyRules = [];

        foreach ($rules as $rule => $_) {
            $params = [];

            if (str_contains($rule, ':')) {
                [$ruleName, $paramStr] = explode(':', $rule, 2);
                $params = str_contains($paramStr, ',') ? explode(',', $paramStr) : [$paramStr];
            } else {
                $ruleName = $rule;
            }

            if ($ruleName === 'required' || $ruleName === 'nullable') {
                continue;
            }

            if (isset(self::$inlineRulesMap[$ruleName])) {
                $this->{"rule{$this->studly($ruleName)}"}($field, $value, $params);
            } elseif ($ruleName === 'not_in') {
                $this->ruleNotIn($field, $value, $params);
            } else {
                $symfonyRules[] = ['name' => $ruleName, 'params' => $params];
            }
        }

        $constraints = $this->buildSymfonyConstraints($field, $symfonyRules);
        if (!empty($constraints)) {
            $violations = $this->symfonyValidator->validate($value, $constraints);
            foreach ($violations as $violation) {
                $this->errors[$field][] = $violation->getMessage();
            }
        }
    }

    protected function buildSymfonyConstraints(string $field, array $rules): array
    {
        $constraints = [];

        foreach ($rules as $rule) {
            $c = $this->resolveSymfonyConstraint($field, $rule['name'], $rule['params']);
            if ($c !== null) {
                $constraints[] = $c;
            }
        }

        return $constraints;
    }

    protected function resolveSymfonyConstraint(string $field, string $rule, array $params): mixed
    {
        return match ($rule) {
            'email' => new Assert\Email(mode: 'html5'),
            'min' => new Assert\Length(min: (int) ($params[0] ?? 0)),
            'max' => new Assert\Length(max: (int) ($params[0] ?? 0)),
            'between' => new Assert\Range(
                notInRangeMessage: $this->message($field, 'between', ($params[0] ?? '0') . ' and ' . ($params[1] ?? '0')),
                min: $params[0] ?? 0,
                max: $params[1] ?? 0,
            ),
            'string' => new Assert\Type('string'),
            'integer' => new Assert\Type('integer'),
            'numeric' => new Assert\Type('numeric'),
            'array' => new Assert\Type('array'),
            'boolean' => new Assert\Type('bool'),
            'url' => new Assert\Url(),
            'ip' => new Assert\Ip(),
            'date' => new Assert\Date(),
            'regex' => new Assert\Regex(pattern: $params[0] ?? '/^.*$/'),
            'in' => new Assert\Choice(
                choices: $params,
                message: $this->message($field, 'in'),
            ),
            'alpha' => new Assert\Regex(pattern: '/^[a-zA-Z]+$/', message: $this->message($field, 'alpha')),
            'alpha_num' => new Assert\Regex(pattern: '/^[a-zA-Z0-9]+$/', message: $this->message($field, 'alpha_num')),
            'alpha_dash' => new Assert\Regex(pattern: '/^[\w-]+$/', message: $this->message($field, 'alpha_dash')),
            'phone' => new Assert\Regex(pattern: '/^\+?[\d\s\-().]{7,20}$/', message: $this->message($field, 'phone')),
            'uuid' => new Assert\Uuid(message: $this->message($field, 'uuid')),
            default => null,
        };
    }

    protected function ruleConfirmed(string $field, mixed $value, array $params): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);

        if ((string) $value !== (string) $confirmationValue) {
            $this->addError($field, 'confirmed');
        }
    }

    protected function ruleSame(string $field, mixed $value, array $params): void
    {
        $other = $params[0] ?? '';
        $otherValue = $this->getValue($other);

        if ((string) $value !== (string) $otherValue) {
            $this->addError($field, 'same', $other);
        }
    }

    protected function ruleDifferent(string $field, mixed $value, array $params): void
    {
        $other = $params[0] ?? '';
        $otherValue = $this->getValue($other);

        if ((string) $value === (string) $otherValue) {
            $this->addError($field, 'different', $other);
        }
    }

    protected function ruleStartsWith(string $field, mixed $value, array $params): void
    {
        $prefix = $params[0] ?? '';
        if (!is_string($value) || !str_starts_with($value, $prefix)) {
            $this->addError($field, 'starts_with', $prefix);
        }
    }

    protected function ruleEndsWith(string $field, mixed $value, array $params): void
    {
        $suffix = $params[0] ?? '';
        if (!is_string($value) || !str_ends_with($value, $suffix)) {
            $this->addError($field, 'ends_with', $suffix);
        }
    }

    protected function ruleJson(string $field, mixed $value, array $params): void
    {
        if (!is_string($value)) {
            $this->addError($field, 'json');
            return;
        }
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError($field, 'json');
        }
    }

    protected function ruleDistinct(string $field, mixed $value, array $params): void
    {
        if (!is_array($value)) return;
        $unique = array_unique($value);
        if (count($unique) !== count($value)) {
            $this->addError($field, 'distinct');
        }
    }

    protected function rulePresent(string $field, mixed $value, array $params): void
    {
        if (!array_key_exists($field, $this->data)) {
            $this->addError($field, 'present');
        }
    }

    protected function ruleNotIn(string $field, mixed $value, array $params): void
    {
        if (in_array((string) $value, $params, true)) {
            $this->addError($field, 'not_in');
        }
    }

    protected function addError(string $field, string $rule, string $param = ''): void
    {
        $this->errors[$field][] = $this->message($field, $rule, $param);
    }

    protected function message(string $field, string $rule, string $param = ''): string
    {
        $custom = $this->customMessages["{$field}.{$rule}"] ?? $this->customMessages[$rule] ?? null;

        if ($custom !== null) {
            return str_replace(['{field}', '{param}'], [$field, $param], $custom);
        }

        $template = self::$messages[$rule] ?? "The {field} validation failed.";

        return str_replace(['{field}', '{param}'], [$field, $param], $template);
    }

    protected function getValue(string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    protected function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') return true;
        if (is_array($value) && empty($value)) return true;
        return false;
    }

    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $value)));
    }
}
