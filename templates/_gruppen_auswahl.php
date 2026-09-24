<?php
/** @var array $gruppen */
/** @var int[] $ausgewaehlt */
/** @var string $auswahlId */
declare(strict_types=1);

use App\Helpers;
?>
<div class="feld" id="<?= Helpers::e($auswahlId) ?>">
    <label>Sichtbare Gruppen</label>
    <?php if (!$gruppen): ?>
        <p class="text-klein text-sekundaer">Es sind noch keine Gruppen angelegt.</p>
    <?php else: ?>
        <div class="checkliste">
            <?php foreach ($gruppen as $g): ?>
                <label>
                    <input type="checkbox" name="gruppen[]" value="<?= (int) $g['id'] ?>" <?= in_array((int) $g['id'], $ausgewaehlt, true) ? 'checked' : '' ?>>
                    <?= Helpers::e($g['name']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
