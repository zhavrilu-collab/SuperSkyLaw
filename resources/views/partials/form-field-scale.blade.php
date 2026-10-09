{{-- Unos kao forma događaja u Clubu. Pravilo: .cursor/rules/form-fields.mdc --}}
.form-label,
.forma-label {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.25;
    margin-bottom: 4px;
}
.form-control,
.form-select,
.form-control-sm,
.form-select-sm,
.form-control-lg,
.form-select-lg,
.input-group-text {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.5;
    padding: .25rem .5rem;
}
.form-control:not(textarea):not([type="file"]),
.form-select:not([multiple]):not([size]),
.input-group > .form-control:not(textarea):not([type="file"]),
.input-group > .form-select:not([multiple]):not([size]),
.input-group > .btn,
.input-group > .input-group-text {
    height: calc(1.5em + .5rem + 2px);
    min-height: calc(1.5em + .5rem + 2px);
}
textarea.form-control,
textarea.form-control-sm,
select.form-select[multiple],
select.form-select[size] {
    height: auto;
}
.form-check-label,
.form-text,
.invalid-feedback {
    font-size: 12px;
}
.form-check-label { font-weight: 400; }
