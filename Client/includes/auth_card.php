<?php
function auth_card_open(string $title, string $sub = ''): void
{
    client_header($title); ?>
<div class="wrap"><div class="card form-card">
  <h1 style="font-size:1.6rem"><?php echo h($title); ?></h1>
  <?php if ($sub) : ?><p class="muted"><?php echo h($sub); ?></p><?php endif; ?>
<?php }

function auth_card_close(): void
{ ?>
</div></div>
<?php client_footer(); }

function field(string $label, string $name, string $value, array $errors, string $type = 'text', string $extra = ''): void
{
    echo '<div class="field"><label for="f_' . h($name) . '">' . h($label) . '</label><input id="f_' . h($name) . '" type="' . h($type) . '" name="' . h($name) . '" value="' . h($value) . '" ' . $extra . '>'
       . (isset($errors[$name]) ? '<span class="err">' . h($errors[$name]) . '</span>' : '') . '</div>';
}
