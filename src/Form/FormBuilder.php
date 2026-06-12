<?php

declare(strict_types=1);

namespace Witals\Framework\Form;

class FormBuilder
{
    protected string $method = 'POST';
    protected string $action = '';
    protected array $options = [];
    protected array $fields = [];
    protected array $errors = [];
    protected array $old = [];
    protected array $rules = [];
    protected bool $csrf = true;
    protected ?string $csrfToken = null;
    protected array $labels = [];
    protected ?string $id = null;

    public function open(string $action, string $method = 'POST', array $options = []): static
    {
        $this->action = $action;
        $this->method = strtoupper($method);
        $this->options = $options;
        $this->fields = [];
        $this->errors = [];
        $this->old = [];
        $this->rules = [];
        return $this;
    }

    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function errors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    public function old(array $old): static
    {
        $this->old = $old;
        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function csrf(bool $enable = true): static
    {
        $this->csrf = $enable;
        return $this;
    }

    public function token(string $token): static
    {
        $this->csrfToken = $token;
        $this->csrf = true;
        return $this;
    }

    public function label(string $name, string $label): static
    {
        $this->labels[$name] = $label;
        return $this;
    }

    public function input(string $type, string $name, mixed $value = null, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => $type,
            'name' => $name,
            'value' => $value ?? $this->oldValue($name),
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function text(string $name, array $attrs = []): static
    {
        return $this->input('text', $name, null, $attrs);
    }

    public function email(string $name, array $attrs = []): static
    {
        return $this->input('email', $name, null, $attrs);
    }

    public function password(string $name, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'password',
            'name' => $name,
            'value' => '',
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function hidden(string $name, mixed $value = null, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value ?? $this->oldValue($name),
            'attrs' => $attrs,
            'label' => null,
        ];
        return $this;
    }

    public function textarea(string $name, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'textarea',
            'name' => $name,
            'value' => $this->oldValue($name),
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function select(string $name, array $choices, mixed $selected = null, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'select',
            'name' => $name,
            'choices' => $choices,
            'selected' => $selected ?? $this->oldValue($name),
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function checkbox(string $name, mixed $value = '1', bool $checked = false, array $attrs = []): static
    {
        $old = $this->oldValue($name);
        $isChecked = $old !== null ? $old == $value : $checked;
        $this->fields[] = [
            'type' => 'checkbox',
            'name' => $name,
            'value' => $value,
            'checked' => $isChecked,
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function radio(string $name, mixed $value, bool $checked = false, array $attrs = []): static
    {
        $old = $this->oldValue($name);
        $isChecked = $old !== null ? $old == $value : $checked;
        $this->fields[] = [
            'type' => 'radio',
            'name' => $name,
            'value' => $value,
            'checked' => $isChecked,
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function file(string $name, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'file',
            'name' => $name,
            'value' => null,
            'attrs' => $attrs,
            'label' => $this->labels[$name] ?? $this->makeLabel($name),
        ];
        return $this;
    }

    public function number(string $name, array $attrs = []): static
    {
        return $this->input('number', $name, null, $attrs);
    }

    public function date(string $name, array $attrs = []): static
    {
        return $this->input('date', $name, null, $attrs);
    }

    public function url(string $name, array $attrs = []): static
    {
        return $this->input('url', $name, null, $attrs);
    }

    public function color(string $name, array $attrs = []): static
    {
        return $this->input('color', $name, null, $attrs);
    }

    public function tel(string $name, array $attrs = []): static
    {
        return $this->input('tel', $name, null, $attrs);
    }

    public function search(string $name, array $attrs = []): static
    {
        return $this->input('search', $name, null, $attrs);
    }

    public function submit(string $label = 'Submit', array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'submit',
            'name' => '',
            'value' => $label,
            'attrs' => $attrs,
            'label' => null,
        ];
        return $this;
    }

    public function button(string $label, array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'button',
            'name' => '',
            'value' => $label,
            'attrs' => $attrs,
            'label' => null,
        ];
        return $this;
    }

    public function reset(string $label = 'Reset', array $attrs = []): static
    {
        $this->fields[] = [
            'type' => 'reset',
            'name' => '',
            'value' => $label,
            'attrs' => $attrs,
            'label' => null,
        ];
        return $this;
    }

    public function render(): string
    {
        $renderer = new FormRenderer($this);
        return $renderer->render();
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getOld(): array
    {
        return $this->old;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function hasCsrf(): bool
    {
        return $this->csrf;
    }

    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }

    public function needsMultipart(): bool
    {
        foreach ($this->fields as $field) {
            if ($field['type'] === 'file') return true;
        }
        return false;
    }

    public function hasError(string $name): bool
    {
        return isset($this->errors[$name]);
    }

    public function getError(string $name): ?string
    {
        $errs = $this->errors[$name] ?? [];
        return is_array($errs) ? ($errs[0] ?? null) : $errs;
    }

    public function getLabel(string $name): ?string
    {
        foreach ($this->fields as $field) {
            if ($field['name'] === $name) {
                return $field['label'] ?? null;
            }
        }
        return null;
    }

    protected function oldValue(string $name): mixed
    {
        $keys = explode('.', $name);
        $value = $this->old;
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        return $value;
    }

    protected function makeLabel(string $name): string
    {
        return ucwords(str_replace(['_', '-', '.'], ' ', $name));
    }
}
