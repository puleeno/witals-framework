<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Validator\Validator;
use Symfony\Component\Validator\Validation;

class ValidatorTest extends TestCase
{
    protected Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function test_validate_returns_empty_array_on_success(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_validate_returns_errors_on_failure(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_passed_returns_true_when_no_errors(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $this->validator->validate($data, $rules);
        $this->assertTrue($this->validator->passed());
    }

    public function test_passed_returns_false_when_errors(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];

        $this->validator->validate($data, $rules);
        $this->assertFalse($this->validator->passed());
    }

    public function test_fails_returns_false_when_no_errors(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $this->validator->validate($data, $rules);
        $this->assertFalse($this->validator->fails());
    }

    public function test_fails_returns_true_when_errors(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];

        $this->validator->validate($data, $rules);
        $this->assertTrue($this->validator->fails());
    }

    public function test_errors_returns_validation_errors(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];

        $this->validator->validate($data, $rules);
        $errors = $this->validator->errors();

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_required_rule(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_required_rule_with_value(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_nullable_rule(): void
    {
        $data = ['name' => null];
        $rules = ['name' => 'nullable'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_email_rule(): void
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_email_rule_valid(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_min_rule(): void
    {
        $data = ['name' => 'ab'];
        $rules = ['name' => 'min:3'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_min_rule_valid(): void
    {
        $data = ['name' => 'abc'];
        $rules = ['name' => 'min:3'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_max_rule(): void
    {
        $data = ['name' => 'abcdef'];
        $rules = ['name' => 'max:5'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_max_rule_valid(): void
    {
        $data = ['name' => 'abc'];
        $rules = ['name' => 'max:5'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_between_rule(): void
    {
        $data = ['age' => 5];
        $rules = ['age' => 'between:10,20'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_between_rule_valid(): void
    {
        $data = ['age' => 15];
        $rules = ['age' => 'between:10,20'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_string_rule(): void
    {
        $data = ['name' => 123];
        $rules = ['name' => 'string'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_string_rule_valid(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'string'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_integer_rule(): void
    {
        $data = ['age' => 'not-a-number'];
        $rules = ['age' => 'integer'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_integer_rule_valid(): void
    {
        $data = ['age' => 25];
        $rules = ['age' => 'integer'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_numeric_rule(): void
    {
        $data = ['price' => 'not-a-number'];
        $rules = ['price' => 'numeric'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_numeric_rule_valid(): void
    {
        $data = ['price' => 19.99];
        $rules = ['price' => 'numeric'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_array_rule(): void
    {
        $data = ['items' => 'not-an-array'];
        $rules = ['items' => 'array'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_array_rule_valid(): void
    {
        $data = ['items' => [1, 2, 3]];
        $rules = ['items' => 'array'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_boolean_rule(): void
    {
        $data = ['active' => 'not-a-boolean'];
        $rules = ['active' => 'boolean'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_boolean_rule_valid(): void
    {
        $data = ['active' => true];
        $rules = ['active' => 'boolean'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_url_rule(): void
    {
        $data = ['website' => 'not-a-url'];
        $rules = ['website' => 'url'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_url_rule_valid(): void
    {
        $data = ['website' => 'https://example.com'];
        $rules = ['website' => 'url'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_ip_rule(): void
    {
        $data = ['ip' => 'not-an-ip'];
        $rules = ['ip' => 'ip'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_ip_rule_valid(): void
    {
        $data = ['ip' => '192.168.1.1'];
        $rules = ['ip' => 'ip'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_date_rule(): void
    {
        $data = ['date' => 'not-a-date'];
        $rules = ['date' => 'date'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_date_rule_valid(): void
    {
        $data = ['date' => '2023-01-01'];
        $rules = ['date' => 'date'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_regex_rule(): void
    {
        $data = ['code' => 'abc'];
        $rules = ['code' => 'regex:/^[0-9]+$/'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_regex_rule_valid(): void
    {
        $data = ['code' => '123'];
        $rules = ['code' => 'regex:/^[0-9]+$/'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_in_rule(): void
    {
        $data = ['status' => 'invalid'];
        $rules = ['status' => 'in:active,inactive,pending'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_in_rule_valid(): void
    {
        $data = ['status' => 'active'];
        $rules = ['status' => 'in:active,inactive,pending'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_not_in_rule(): void
    {
        $data = ['status' => 'active'];
        $rules = ['status' => 'not_in:active,inactive'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_not_in_rule_valid(): void
    {
        $data = ['status' => 'pending'];
        $rules = ['status' => 'not_in:active,inactive'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_alpha_rule(): void
    {
        $data = ['name' => 'John123'];
        $rules = ['name' => 'alpha'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_alpha_rule_valid(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'alpha'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_alpha_num_rule(): void
    {
        $data = ['name' => 'John-Doe'];
        $rules = ['name' => 'alpha_num'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_alpha_num_rule_valid(): void
    {
        $data = ['name' => 'John123'];
        $rules = ['name' => 'alpha_num'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_alpha_dash_rule(): void
    {
        $data = ['name' => 'John Doe'];
        $rules = ['name' => 'alpha_dash'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_alpha_dash_rule_valid(): void
    {
        $data = ['name' => 'John-Doe_123'];
        $rules = ['name' => 'alpha_dash'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_confirmed_rule(): void
    {
        $data = ['password' => 'secret', 'password_confirmation' => 'different'];
        $rules = ['password' => 'confirmed'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_confirmed_rule_valid(): void
    {
        $data = ['password' => 'secret', 'password_confirmation' => 'secret'];
        $rules = ['password' => 'confirmed'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_same_rule(): void
    {
        $data = ['password' => 'secret', 'confirm_password' => 'different'];
        $rules = ['password' => 'same:confirm_password'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_same_rule_valid(): void
    {
        $data = ['password' => 'secret', 'confirm_password' => 'secret'];
        $rules = ['password' => 'same:confirm_password'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_different_rule(): void
    {
        $data = ['new_password' => 'secret', 'old_password' => 'secret'];
        $rules = ['new_password' => 'different:old_password'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_different_rule_valid(): void
    {
        $data = ['new_password' => 'secret', 'old_password' => 'old'];
        $rules = ['new_password' => 'different:old_password'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_starts_with_rule(): void
    {
        $data = ['username' => 'john_doe'];
        $rules = ['username' => 'starts_with:@'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_starts_with_rule_valid(): void
    {
        $data = ['username' => '@john_doe'];
        $rules = ['username' => 'starts_with:@'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_ends_with_rule(): void
    {
        $data = ['email' => 'test@example'];
        $rules = ['email' => 'ends_with:.com'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_ends_with_rule_valid(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'ends_with:.com'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_json_rule(): void
    {
        $data = ['data' => 'not-json'];
        $rules = ['data' => 'json'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_json_rule_valid(): void
    {
        $data = ['data' => '{"key":"value"}'];
        $rules = ['data' => 'json'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_distinct_rule(): void
    {
        $data = ['tags' => [1, 2, 2, 3]];
        $rules = ['tags' => 'distinct'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_distinct_rule_valid(): void
    {
        $data = ['tags' => [1, 2, 3]];
        $rules = ['tags' => 'distinct'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_present_rule(): void
    {
        $data = [];
        $rules = ['name' => 'present'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_present_rule_valid(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'present'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_multiple_rules(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'required|email|min:10'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_multiple_rules_valid(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email|min:10'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_array_rules(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => ['required', 'email']];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_nested_data_validation(): void
    {
        $data = ['user' => ['email' => 'invalid']];
        $rules = ['user.email' => 'email'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertNotEmpty($errors);
    }

    public function test_custom_messages(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];

        $this->validator->setCustomMessages(['email' => 'Custom email message']);
        $errors = $this->validator->validate($data, $rules);

        $this->assertContains('Custom email message', $errors['email']);
    }

    public function test_custom_field_messages(): void
    {
        $data = ['email' => 'invalid'];
        $rules = ['email' => 'email'];

        $this->validator->setCustomMessages(['email.email' => 'Custom field email message']);
        $errors = $this->validator->validate($data, $rules);

        $this->assertContains('Custom field email message', $errors['email']);
    }

    public function test_setCustomMessages_is_chainable(): void
    {
        $result = $this->validator->setCustomMessages(['email' => 'Custom']);
        $this->assertSame($this->validator, $result);
    }

    public function test_required_with_zero_value(): void
    {
        $data = ['count' => 0];
        $rules = ['count' => 'required'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_required_with_string_zero(): void
    {
        $data = ['count' => '0'];
        $rules = ['count' => 'required'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_nullable_with_empty_string(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'nullable'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_nullable_with_empty_array(): void
    {
        $data = ['items' => []];
        $rules = ['items' => 'nullable'];

        $errors = $this->validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function test_custom_validator_instance(): void
    {
        $symfonyValidator = Validation::createValidator();
        $validator = new Validator($symfonyValidator);

        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];

        $errors = $validator->validate($data, $rules);
        $this->assertEmpty($errors);
    }
}
