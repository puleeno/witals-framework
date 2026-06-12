<?php

declare(strict_types=1);

namespace Witals\Framework\Form;

class FormRenderer
{
    protected FormBuilder $form;

    public function __construct(FormBuilder $form)
    {
        $this->form = $form;
    }

    public function render(): string
    {
        $html = $this->renderOpen() . "\n";

        foreach ($this->form->fields() as $field) {
            $html .= $this->renderField($field) . "\n";
        }

        $html .= $this->renderClose() . "\n";

        return $html;
    }

    public function renderOpen(): string
    {
        $attrs = $this->form->getOptions();
        $attrs['action'] = $this->form->getAction();
        $attrs['method'] = 'POST';

        $method = $this->form->getMethod();
        $spoof = !in_array($method, ['GET', 'POST'], true);

        if ($spoof) {
            $attrs['method'] = 'POST';
        } elseif ($method === 'GET') {
            $attrs['method'] = 'GET';
        }

        if ($this->form->needsMultipart()) {
            $attrs['enctype'] = 'multipart/form-data';
        }

        if ($this->form->getId()) {
            $attrs['id'] = $this->form->getId();
        }

        $html = '<form' . $this->renderAttrs($attrs) . '>' . "\n";

        if ($this->form->hasCsrf()) {
            $token = $this->form->getCsrfToken() ?? '{{ csrf_token }}';
            $html .= '    <input type="hidden" name="_token" value="' . $token . '" />' . "\n";
        }

        if ($spoof) {
            $html .= '    <input type="hidden" name="_method" value="' . $method . '" />' . "\n";
        }

        return $html;
    }

    public function renderClose(): string
    {
        return '</form>';
    }

    public function renderField(array $field): string
    {
        $type = $field['type'];
        $name = $field['name'];

        if (in_array($type, ['hidden', 'submit', 'button', 'reset'], true)) {
            return $this->{'render' . ucfirst($type)}($field);
        }

        $html = '    <div class="form-group' . ($this->form->hasError($name) ? ' has-error' : '') . '">' . "\n";

        if ($field['label'] && $type !== 'checkbox' && $type !== 'radio') {
            $html .= $this->renderLabel($field);
        }

        $html .= $this->{'render' . ucfirst($type)}($field);

        if ($this->form->hasError($name)) {
            $html .= $this->renderError($name);
        }

        $html .= '    </div>';

        return $html;
    }

    protected function renderLabel(array $field): string
    {
        $label = $field['label'];
        $name = $field['name'];
        $id = $field['attrs']['id'] ?? str_replace(['[]', '[', ']'], ['', '_', ''], $name);
        $attrs = ['for' => $id];

        return '        <label' . $this->renderAttrs($attrs) . '>' . htmlspecialchars($label) . '</label>' . "\n";
    }

    protected function renderText(array $field): string
    {
        return $this->renderInput('text', $field);
    }

    protected function renderEmail(array $field): string
    {
        return $this->renderInput('email', $field);
    }

    protected function renderPassword(array $field): string
    {
        return $this->renderInput('password', $field);
    }

    protected function renderNumber(array $field): string
    {
        return $this->renderInput('number', $field);
    }

    protected function renderDate(array $field): string
    {
        return $this->renderInput('date', $field);
    }

    protected function renderUrl(array $field): string
    {
        return $this->renderInput('url', $field);
    }

    protected function renderColor(array $field): string
    {
        return $this->renderInput('color', $field);
    }

    protected function renderTel(array $field): string
    {
        return $this->renderInput('tel', $field);
    }

    protected function renderSearch(array $field): string
    {
        return $this->renderInput('search', $field);
    }

    protected function renderHidden(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'hidden';
        $attrs['name'] = $field['name'];
        if ($field['value'] !== null && $field['value'] !== '') {
            $attrs['value'] = $field['value'];
        }

        return '    <input' . $this->renderAttrs($attrs) . ' />';
    }

    protected function renderInput(string $type, array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = $type;
        $attrs['name'] = $field['name'];
        $attrs['value'] = $field['value'] ?? '';

        if (!isset($attrs['id'])) {
            $attrs['id'] = str_replace(['[]', '[', ']'], ['', '_', ''], $field['name']);
        }

        return '        <input' . $this->renderAttrs($attrs) . ' />';
    }

    protected function renderTextarea(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['name'] = $field['name'];

        if (!isset($attrs['id'])) {
            $attrs['id'] = str_replace(['[]', '[', ']'], ['', '_', ''], $field['name']);
        }

        if (!isset($attrs['rows'])) {
            $attrs['rows'] = 5;
        }

        $value = $field['value'] ?? '';

        return '        <textarea' . $this->renderAttrs($attrs) . '>' . htmlspecialchars((string) $value) . '</textarea>';
    }

    protected function renderSelect(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['name'] = $field['name'];

        if (!isset($attrs['id'])) {
            $attrs['id'] = str_replace(['[]', '[', ']'], ['', '_', ''], $field['name']);
        }

        $selected = $field['selected'] ?? null;
        $html = '        <select' . $this->renderAttrs($attrs) . '>' . "\n";

        foreach ($field['choices'] as $value => $label) {
            $optionAttrs = ['value' => $value];
            if ((string) $selected === (string) $value) {
                $optionAttrs['selected'] = 'selected';
            }
            $html .= '            <option' . $this->renderAttrs($optionAttrs) . '>' . htmlspecialchars((string) $label) . '</option>' . "\n";
        }

        $html .= '        </select>';
        return $html;
    }

    protected function renderCheckbox(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'checkbox';
        $attrs['name'] = $field['name'];
        $attrs['value'] = $field['value'];

        if (!isset($attrs['id'])) {
            $attrs['id'] = str_replace(['[]', '[', ']'], ['', '_', ''], $field['name']);
        }

        if ($field['checked']) {
            $attrs['checked'] = 'checked';
        }

        $label = $field['label'] ?? '';
        $input = '<input' . $this->renderAttrs($attrs) . ' />';

        return '        <label class="checkbox-label">' . $input . ' ' . htmlspecialchars($label) . '</label>';
    }

    protected function renderRadio(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'radio';
        $attrs['name'] = $field['name'];
        $attrs['value'] = $field['value'];

        if (!isset($attrs['id'])) {
            $attrs['id'] = str_replace(['[]', '[', ']'], ['', '_', ''], $field['name']) . '_' . $field['value'];
        }

        if ($field['checked']) {
            $attrs['checked'] = 'checked';
        }

        $label = $field['label'] ?? '';
        $input = '<input' . $this->renderAttrs($attrs) . ' />';

        return '        <label class="radio-label">' . $input . ' ' . htmlspecialchars($label) . '</label>';
    }

    protected function renderFile(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'file';
        $attrs['name'] = $field['name'];

        if (!isset($attrs['id'])) {
            $attrs['id'] = str_replace(['[]', '[', ']'], ['', '_', ''], $field['name']);
        }

        return '        <input' . $this->renderAttrs($attrs) . ' />';
    }

    protected function renderSubmit(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'submit';
        $attrs['value'] = $field['value'] ?? 'Submit';

        return '        <button' . $this->renderAttrs($attrs) . '>' . htmlspecialchars($field['value'] ?? 'Submit') . '</button>';
    }

    protected function renderButton(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'button';

        return '        <button' . $this->renderAttrs($attrs) . '>' . htmlspecialchars($field['value'] ?? 'Button') . '</button>';
    }

    protected function renderReset(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'reset';

        return '        <button' . $this->renderAttrs($attrs) . '>' . htmlspecialchars($field['value'] ?? 'Reset') . '</button>';
    }

    protected function renderError(string $name): string
    {
        $error = $this->form->getError($name);
        if ($error === null) return '';

        return '        <span class="form-error">' . htmlspecialchars($error) . '</span>' . "\n";
    }

    protected function renderAttrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            if ($value === true) {
                $parts[] = $key;
            } elseif ($value !== false && $value !== null) {
                $parts[] = $key . '="' . htmlspecialchars((string) $value) . '"';
            }
        }
        return $parts ? ' ' . implode(' ', $parts) : '';
    }
}
