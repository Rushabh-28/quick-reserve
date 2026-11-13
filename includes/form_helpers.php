<?php
/** Tiny helpers that print Purple Admin form groups. */

function form_input(string $label, string $name, $value, array $errors, string $type = 'text', string $extra = ''): void
{
    echo '<div class="form-group"><label for="f_' . h($name) . '">' . h($label) . '</label>'
       . '<input type="' . h($type) . '" class="form-control" id="f_' . h($name) . '" name="' . h($name) . '" '
       . 'value="' . h($value) . '" ' . $extra . '>'
       . field_error($errors, $name) . '</div>';
}

function form_textarea(string $label, string $name, $value, array $errors, int $rows = 5): void
{
    echo '<div class="form-group"><label for="f_' . h($name) . '">' . h($label) . '</label>'
       . '<textarea class="form-control" id="f_' . h($name) . '" name="' . h($name) . '" rows="' . $rows . '">' . h($value) . '</textarea>'
       . field_error($errors, $name) . '</div>';
}

/** $options: [value => label] */
function form_select(string $label, string $name, array $options, $selected, array $errors, string $placeholder = 'Select'): void
{
    echo '<div class="form-group"><label for="f_' . h($name) . '">' . h($label) . '</label>'
       . '<select class="form-control" id="f_' . h($name) . '" name="' . h($name) . '"><option value="">' . h($placeholder) . '</option>';
    foreach ($options as $val => $text) {
        echo '<option value="' . h($val) . '"' . ((string) $val === (string) $selected ? ' selected' : '') . '>' . h($text) . '</option>';
    }
    echo '</select>' . field_error($errors, $name) . '</div>';
}

function form_image(string $label, string $name, ?string $current, array $errors): void
{
    echo '<div class="form-group"><label for="f_' . h($name) . '">' . h($label) . '</label>';
    if ($current) {
        echo '<div class="mb-2">' . image_tag($current) . ' <small class="text-muted">Current image. Choose a file to replace it.</small></div>';
    }
    echo '<input type="file" class="form-control" id="f_' . h($name) . '" name="' . h($name) . '" accept="image/jpeg,image/png,image/webp">'
       . '<small class="text-muted">JPG, PNG or WEBP, max 2 MB.</small>'
       . field_error($errors, $name) . '</div>';
}

function page_open(string $title, ?string $action_label = null, ?string $action_href = null): void
{
    echo '<div class="content-wrapper"><div class="page-header"><h3 class="page-title">' . h($title) . '</h3>';
    if ($action_label) {
        echo '<nav aria-label="breadcrumb"><a href="' . h($action_href) . '" class="btn btn-gradient-dark btn-fw">' . h($action_label) . '</a></nav>';
    }
    echo '</div>';
}

function page_close(): void
{
    echo '</div>';
}

function card_open(string $title): void
{
    echo '<div class="row"><div class="col-12 grid-margin stretch-card"><div class="card"><div class="card-body">'
       . '<h4 class="card-title">' . h($title) . '</h4>';
}

function card_close(): void
{
    echo '</div></div></div></div>';
}
