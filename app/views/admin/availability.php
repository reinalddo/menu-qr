<?php

use MenuQr\Csrf;

?>
<h1 class="page-title"><?= e(t('nav.availability')) ?></h1>
<p class="muted"><?= e(t('availability.intro')) ?></p>

<?php if ($groups === []): ?>
    <section class="card empty">
        <p><?= e(t('dishes.empty')) ?></p>
        <a class="button button-primary" href="/admin/dishes/new"><?= e(t('dishes.new')) ?></a>
    </section>
<?php endif; ?>

<?php foreach ($groups as $categoryName => $dishes): ?>
    <section class="card list-card">
        <h2 class="list-title"><?= e($categoryName) ?></h2>
        <ul class="item-list">
            <?php foreach ($dishes as $dish): ?>
                <?php $available = (bool) $dish['is_available']; ?>
                <li class="item" id="dish-<?= e($dish['id']) ?>">
                    <span class="item-name<?= $available ? '' : ' is-sold-out' ?>"><?= e($dish['name']) ?></span>
                    <form method="post" action="/admin/dishes/<?= e($dish['id']) ?>/availability" class="toggle-form" data-availability>
                        <?= Csrf::field() ?>
                        <input type="hidden" name="available" value="<?= $available ? '0' : '1' ?>">
                        <button type="submit" class="switch" role="switch" aria-checked="<?= $available ? 'true' : 'false' ?>"
                                data-on="<?= e(t('availability.available')) ?>" data-off="<?= e(t('availability.sold_out')) ?>">
                            <span class="switch-track" aria-hidden="true"><span class="switch-thumb"></span></span>
                            <span class="switch-text"><?= e($available ? t('availability.available') : t('availability.sold_out')) ?></span>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
