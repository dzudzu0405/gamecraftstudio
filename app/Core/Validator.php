<?php
namespace App\Core;

/**
 * Form validation.
 * Usage:
 *   $v = new Validator($request->body);
 *   $v->required('name', 'a game title')->max('name', 120);
 *   if ($v->fails()) { ... $v->errors() ... }
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function val(string $field)
    {
        return $this->data[$field] ?? null;
    }

    private function label(string $field, ?string $label): string
    {
        return $label ?? $field;
    }

    private function fail(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /** Skip later rules once a field already has an error */
    private function skip(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function required(string $field, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = $this->val($field);
        if ($v === null || (is_string($v) && trim($v) === '') || (is_array($v) && count($v) === 0)) {
            $this->fail($field, 'Please enter ' . $this->label($field, $label) . '.');
        }
        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = (string) $this->val($field);
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->fail($field, 'That is not a valid email address.');
        }
        return $this;
    }

    public function min(string $field, int $length, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = (string) $this->val($field);
        if ($v !== '' && mb_strlen($v) < $length) {
            $this->fail($field, ucfirst($this->label($field, $label)) . ' must be at least ' . $length . ' characters.');
        }
        return $this;
    }

    public function max(string $field, int $length, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = (string) $this->val($field);
        if (mb_strlen($v) > $length) {
            $this->fail($field, ucfirst($this->label($field, $label)) . ' cannot be longer than ' . $length . ' characters.');
        }
        return $this;
    }

    public function in(string $field, array $allowed, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $this->fail($field, 'That is not a valid ' . $this->label($field, $label) . '.');
        }
        return $this;
    }

    public function numeric(string $field, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !is_numeric($v)) {
            $this->fail($field, ucfirst($this->label($field, $label)) . ' must be a number.');
        }
        return $this;
    }

    public function between(string $field, int $lo, int $hi, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        $v = $this->val($field);
        if ($v !== null && $v !== '' && is_numeric($v)) {
            $n = (int) $v;
            if ($n < $lo || $n > $hi) {
                $this->fail($field, ucfirst($this->label($field, $label)) . ' must be between ' . $lo . ' and ' . $hi . '.');
            }
        }
        return $this;
    }

    public function matches(string $field, string $otherField, ?string $label = null): self
    {
        if ($this->skip($field)) return $this;
        if ((string) $this->val($field) !== (string) $this->val($otherField)) {
            $this->fail($field, ucfirst($this->label($field, $label)) . ' does not match.');
        }
        return $this;
    }

    /** A custom rule */
    public function rule(string $field, bool $passes, string $message): self
    {
        if ($this->skip($field)) return $this;
        if (!$passes) {
            $this->fail($field, $message);
        }
        return $this;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    public function passes(): bool
    {
        return count($this->errors) === 0;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }
}
