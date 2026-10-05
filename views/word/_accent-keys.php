<?php

use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Reusable Spanish accent keyboard. Renders one row of character buttons
 * that insert at the cursor position of the focused input.
 *
 * @var yii\web\View $this
 * @var string[] $inputIds DOM ids of the text inputs to feed (first is default)
 * @var string[]|null $chars characters to offer
 */

$chars = $chars ?? ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', '¡', '¿'];
$inputIds = array_values($inputIds ?? []);

static $counter = 0;
$counter++;
$boxId = 'accent-keys-' . $counter;
$idsJson = Json::htmlEncode($inputIds);
?>

<?php if (!empty($inputIds)): ?>
<div class="mb-3 accent-keys" id="<?= $boxId ?>" role="group" aria-label="Spaanse tekens">
    <?php foreach ($chars as $char): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm accent-key" data-char="<?= Html::encode($char) ?>"><?= Html::encode($char) ?></button>
    <?php endforeach; ?>
</div>
<?php
$this->registerJs(<<<JS
(function () {
    var box = document.getElementById('$boxId');
    if (!box) {
        return;
    }
    var ids = $idsJson;
    var current = ids[0];
    ids.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('focusin', function () { current = id; });
        }
    });
    box.querySelectorAll('.accent-key').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(current);
            if (!input) {
                return;
            }
            var start = input.selectionStart === null ? input.value.length : input.selectionStart;
            var end = input.selectionEnd === null ? start : input.selectionEnd;
            input.value = input.value.slice(0, start) + btn.dataset.char + input.value.slice(end);
            input.focus();
            var pos = start + btn.dataset.char.length;
            input.setSelectionRange(pos, pos);
        });
    });
})();
JS);
?>
<?php endif; ?>
