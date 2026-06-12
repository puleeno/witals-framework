<?php

declare(strict_types=1);

namespace Witals\Framework\Tests\Form;

use PHPUnit\Framework\TestCase;
use Witals\Framework\Form\FormBuilder;

class FormBuilderTest extends TestCase
{
    private FormBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FormBuilder();
    }

    public function testOpenInitializesForm(): void
    {
        $this->builder->open('/submit', 'POST', ['class' => 'form']);

        $this->assertSame('/submit', $this->builder->getAction());
        $this->assertSame('POST', $this->builder->getMethod());
        $this->assertSame(['class' => 'form'], $this->builder->getOptions());
    }

    public function testOpenResetsFields(): void
    {
        $this->builder->open('/submit');
        $this->builder->text('name');
        $this->builder->open('/submit2');

        $this->assertEmpty($this->builder->fields());
    }

    public function testOpenResetsErrors(): void
    {
        $this->builder->open('/submit');
        $this->builder->errors(['name' => 'Required']);
        $this->builder->open('/submit2');

        $this->assertEmpty($this->builder->getErrors());
    }

    public function testIdSetsFormId(): void
    {
        $this->builder->id('my-form');

        $this->assertSame('my-form', $this->builder->getId());
    }

    public function testActionSetsFormAction(): void
    {
        $this->builder->action('/new-action');

        $this->assertSame('/new-action', $this->builder->getAction());
    }

    public function testMethodUppercasesMethod(): void
    {
        $this->builder->method('post');

        $this->assertSame('POST', $this->builder->getMethod());
    }

    public function testErrorsSetsErrors(): void
    {
        $errors = ['name' => 'Required', 'email' => 'Invalid'];
        $this->builder->errors($errors);

        $this->assertSame($errors, $this->builder->getErrors());
    }

    public function testOldSetsOldInput(): void
    {
        $old = ['name' => 'John', 'email' => 'john@example.com'];
        $this->builder->old($old);

        $this->assertSame($old, $this->builder->getOld());
    }

    public function testRulesSetsValidationRules(): void
    {
        $rules = ['name' => 'required', 'email' => 'email'];
        $this->builder->rules($rules);

        $this->assertSame($rules, $this->builder->getRules());
    }

    public function testCsrfEnablesCsrf(): void
    {
        $this->builder->csrf(false);

        $this->assertFalse($this->builder->hasCsrf());
    }

    public function testCsrfEnablesByDefault(): void
    {
        $this->assertTrue($this->builder->hasCsrf());
    }

    public function testTokenSetsCsrfToken(): void
    {
        $this->builder->token('secret-token');

        $this->assertSame('secret-token', $this->builder->getCsrfToken());
        $this->assertTrue($this->builder->hasCsrf());
    }

    public function testLabelSetsCustomLabel(): void
    {
        $this->builder->label('username', 'Username');

        $this->assertSame('Username', $this->builder->getLabel('username'));
    }

    public function testInputAddsTextField(): void
    {
        $this->builder->input('text', 'username', 'john', ['class' => 'input']);

        $fields = $this->builder->fields();
        $this->assertCount(1, $fields);
        $this->assertSame('text', $fields[0]['type']);
        $this->assertSame('username', $fields[0]['name']);
        $this->assertSame('john', $fields[0]['value']);
        $this->assertSame(['class' => 'input'], $fields[0]['attrs']);
    }

    public function testInputUsesOldValue(): void
    {
        $this->builder->old(['username' => 'old_value']);
        $this->builder->input('text', 'username');

        $fields = $this->builder->fields();
        $this->assertSame('old_value', $fields[0]['value']);
    }

    public function testTextAddsTextField(): void
    {
        $this->builder->text('username');

        $fields = $this->builder->fields();
        $this->assertSame('text', $fields[0]['type']);
        $this->assertSame('username', $fields[0]['name']);
    }

    public function testEmailAddsEmailField(): void
    {
        $this->builder->email('email');

        $fields = $this->builder->fields();
        $this->assertSame('email', $fields[0]['type']);
        $this->assertSame('email', $fields[0]['name']);
    }

    public function testPasswordAddsPasswordField(): void
    {
        $this->builder->password('password');

        $fields = $this->builder->fields();
        $this->assertSame('password', $fields[0]['type']);
        $this->assertSame('password', $fields[0]['name']);
        $this->assertSame('', $fields[0]['value']);
    }

    public function testPasswordIgnoresOldValue(): void
    {
        $this->builder->old(['password' => 'old_password']);
        $this->builder->password('password');

        $fields = $this->builder->fields();
        $this->assertSame('', $fields[0]['value']);
    }

    public function testHiddenAddsHiddenField(): void
    {
        $this->builder->hidden('token', 'secret');

        $fields = $this->builder->fields();
        $this->assertSame('hidden', $fields[0]['type']);
        $this->assertSame('token', $fields[0]['name']);
        $this->assertSame('secret', $fields[0]['value']);
        $this->assertNull($fields[0]['label']);
    }

    public function testHiddenUsesOldValue(): void
    {
        $this->builder->old(['token' => 'old_token']);
        $this->builder->hidden('token');

        $fields = $this->builder->fields();
        $this->assertSame('old_token', $fields[0]['value']);
    }

    public function testTextareaAddsTextareaField(): void
    {
        $this->builder->textarea('content');

        $fields = $this->builder->fields();
        $this->assertSame('textarea', $fields[0]['type']);
        $this->assertSame('content', $fields[0]['name']);
    }

    public function testTextareaUsesOldValue(): void
    {
        $this->builder->old(['content' => 'old content']);
        $this->builder->textarea('content');

        $fields = $this->builder->fields();
        $this->assertSame('old content', $fields[0]['value']);
    }

    public function testSelectAddsSelectField(): void
    {
        $choices = ['1' => 'Option 1', '2' => 'Option 2'];
        $this->builder->select('category', $choices, '1');

        $fields = $this->builder->fields();
        $this->assertSame('select', $fields[0]['type']);
        $this->assertSame('category', $fields[0]['name']);
        $this->assertSame($choices, $fields[0]['choices']);
        $this->assertSame('1', $fields[0]['selected']);
    }

    public function testSelectUsesOldValue(): void
    {
        $this->builder->old(['category' => '2']);
        $this->builder->select('category', ['1' => 'Option 1', '2' => 'Option 2']);

        $fields = $this->builder->fields();
        $this->assertSame('2', $fields[0]['selected']);
    }

    public function testCheckboxAddsCheckboxField(): void
    {
        $this->builder->checkbox('agree', '1', true);

        $fields = $this->builder->fields();
        $this->assertSame('checkbox', $fields[0]['type']);
        $this->assertSame('agree', $fields[0]['name']);
        $this->assertSame('1', $fields[0]['value']);
        $this->assertTrue($fields[0]['checked']);
    }

    public function testCheckboxUsesOldValue(): void
    {
        $this->builder->old(['agree' => '1']);
        $this->builder->checkbox('agree', '1');

        $fields = $this->builder->fields();
        $this->assertTrue($fields[0]['checked']);
    }

    public function testRadioAddsRadioField(): void
    {
        $this->builder->radio('gender', 'male', true);

        $fields = $this->builder->fields();
        $this->assertSame('radio', $fields[0]['type']);
        $this->assertSame('gender', $fields[0]['name']);
        $this->assertSame('male', $fields[0]['value']);
        $this->assertTrue($fields[0]['checked']);
    }

    public function testRadioUsesOldValue(): void
    {
        $this->builder->old(['gender' => 'female']);
        $this->builder->radio('gender', 'female');

        $fields = $this->builder->fields();
        $this->assertTrue($fields[0]['checked']);
    }

    public function testFileAddsFileField(): void
    {
        $this->builder->file('avatar');

        $fields = $this->builder->fields();
        $this->assertSame('file', $fields[0]['type']);
        $this->assertSame('avatar', $fields[0]['name']);
        $this->assertNull($fields[0]['value']);
    }

    public function testNumberAddsNumberField(): void
    {
        $this->builder->number('age');

        $fields = $this->builder->fields();
        $this->assertSame('number', $fields[0]['type']);
        $this->assertSame('age', $fields[0]['name']);
    }

    public function testDateAddsDateField(): void
    {
        $this->builder->date('birthdate');

        $fields = $this->builder->fields();
        $this->assertSame('date', $fields[0]['type']);
        $this->assertSame('birthdate', $fields[0]['name']);
    }

    public function testUrlAddsUrlField(): void
    {
        $this->builder->url('website');

        $fields = $this->builder->fields();
        $this->assertSame('url', $fields[0]['type']);
        $this->assertSame('website', $fields[0]['name']);
    }

    public function testColorAddsColorField(): void
    {
        $this->builder->color('theme');

        $fields = $this->builder->fields();
        $this->assertSame('color', $fields[0]['type']);
        $this->assertSame('theme', $fields[0]['name']);
    }

    public function testTelAddsTelField(): void
    {
        $this->builder->tel('phone');

        $fields = $this->builder->fields();
        $this->assertSame('tel', $fields[0]['type']);
        $this->assertSame('phone', $fields[0]['name']);
    }

    public function testSearchAddsSearchField(): void
    {
        $this->builder->search('query');

        $fields = $this->builder->fields();
        $this->assertSame('search', $fields[0]['type']);
        $this->assertSame('query', $fields[0]['name']);
    }

    public function testSubmitAddsSubmitButton(): void
    {
        $this->builder->submit('Save');

        $fields = $this->builder->fields();
        $this->assertSame('submit', $fields[0]['type']);
        $this->assertSame('Save', $fields[0]['value']);
        $this->assertNull($fields[0]['label']);
    }

    public function testButtonAddsButton(): void
    {
        $this->builder->button('Click Me');

        $fields = $this->builder->fields();
        $this->assertSame('button', $fields[0]['type']);
        $this->assertSame('Click Me', $fields[0]['value']);
        $this->assertNull($fields[0]['label']);
    }

    public function testResetAddsResetButton(): void
    {
        $this->builder->reset('Clear');

        $fields = $this->builder->fields();
        $this->assertSame('reset', $fields[0]['type']);
        $this->assertSame('Clear', $fields[0]['value']);
        $this->assertNull($fields[0]['label']);
    }

    public function testNeedsMultipartReturnsTrueWhenFileFieldExists(): void
    {
        $this->builder->file('avatar');

        $this->assertTrue($this->builder->needsMultipart());
    }

    public function testNeedsMultipartReturnsFalseWhenNoFileField(): void
    {
        $this->builder->text('username');

        $this->assertFalse($this->builder->needsMultipart());
    }

    public function testHasErrorReturnsTrueWhenErrorExists(): void
    {
        $this->builder->errors(['username' => 'Required']);

        $this->assertTrue($this->builder->hasError('username'));
    }

    public function testHasErrorReturnsFalseWhenNoError(): void
    {
        $this->builder->errors(['email' => 'Invalid']);

        $this->assertFalse($this->builder->hasError('username'));
    }

    public function testGetErrorReturnsErrorMessage(): void
    {
        $this->builder->errors(['username' => ['Required', 'Min length']]);

        $this->assertSame('Required', $this->builder->getError('username'));
    }

    public function testGetErrorReturnsNullWhenNoError(): void
    {
        $this->builder->errors(['email' => 'Invalid']);

        $this->assertNull($this->builder->getError('username'));
    }

    public function testGetErrorHandlesStringError(): void
    {
        $this->builder->errors(['username' => 'Required']);

        $this->assertSame('Required', $this->builder->getError('username'));
    }

    public function testGetLabelReturnsCustomLabel(): void
    {
        $this->builder->label('username', 'Username');
        $this->builder->text('username');

        $this->assertSame('Username', $this->builder->getLabel('username'));
    }

    public function testGetLabelReturnsGeneratedLabel(): void
    {
        $this->builder->text('username');

        $this->assertSame('Username', $this->builder->getLabel('username'));
    }

    public function testGetLabelReturnsNullForHiddenField(): void
    {
        $this->builder->hidden('token');

        $this->assertNull($this->builder->getLabel('token'));
    }

    public function testOldValueHandlesNestedKeys(): void
    {
        $this->builder->old(['user' => ['name' => 'John']]);
        $this->builder->text('user.name');

        $fields = $this->builder->fields();
        $this->assertSame('John', $fields[0]['value']);
    }

    public function testOldValueReturnsNullForMissingKey(): void
    {
        $this->builder->old(['user' => ['email' => 'john@example.com']]);
        $this->builder->text('user.name');

        $fields = $this->builder->fields();
        $this->assertNull($fields[0]['value']);
    }

    public function testMakeLabelConvertsUnderscores(): void
    {
        $this->builder->text('user_name');

        $this->assertSame('User Name', $this->builder->getLabel('user_name'));
    }

    public function testMakeLabelConvertsHyphens(): void
    {
        $this->builder->text('user-name');

        $this->assertSame('User Name', $this->builder->getLabel('user-name'));
    }

    public function testMakeLabelConvertsDots(): void
    {
        $this->builder->text('user.name');

        $this->assertSame('User Name', $this->builder->getLabel('user.name'));
    }

    public function testMethodsAreChainable(): void
    {
        $result = $this->builder
            ->open('/submit')
            ->id('my-form')
            ->method('POST')
            ->text('username')
            ->email('email');

        $this->assertSame($this->builder, $result);
    }

    public function testRenderReturnsString(): void
    {
        $this->builder->open('/submit')->text('username');

        $result = $this->builder->render();

        $this->assertIsString($result);
    }
}
