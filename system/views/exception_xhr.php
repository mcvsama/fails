——— <?= get_class ($e) ?> ———
<?= capitalize ($e->getMessage()) ?>


——— Stack trace ———
<?= $e->getTraceAsString() ?>
<? foreach (Fails::get_state() as $h => $c): ?>


——— <?= $h ?> ———
<?= $c ?>
<? endforeach ?>
