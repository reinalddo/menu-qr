<?php if (!empty($errors[$field])): ?>
    <p class="field-error" id="<?= e($field) ?>-error"><?= e($errors[$field]) ?></p>
<?php endif; ?>
