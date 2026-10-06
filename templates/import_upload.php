<?php
/** @var int $maxGroesse */
declare(strict_types=1);

use App\Helpers;

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<div class=”karte” style=”max-width:640px;”>
    <h3><?= Helpers::e(t('import.tabelle_hochladen')) ?></h3>
    <p class=”text-sekundaer”><?= Helpers::e(t('import.beschreibung')) ?></p>
    <form method=”post” action=”/import.php” enctype=”multipart/form-data”>
        <?= Helpers::csrfField() ?>
        <input type=”hidden” name=”aktion” value=”hochladen”>
        <div class=”feld”>
            <label for=”datei”><?= Helpers::e(t('import.datei_label', ['max' => Helpers::formatGroesse($maxGroesse)])) ?></label>
            <input type=”file” id=”datei” name=”datei” accept=”.xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv” required>
        </div>
        <button type=”submit” class=”btn btn--primaer”><?= Helpers::e(t('import.weiter')) ?></button>
    </form>
    <p class=”text-klein text-sekundaer mt-m”><?= Helpers::e(t('import.hinweis')) ?></p>
</div>
