<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
/** @var yii\web\View $this */

$this->title = Yii::$app->name;
?>
<?php if (isset($result)) : ?>
<pre><?php var_dump($result);  ?></pre>
<?php endif; ?>
<div class="site-index">

    <div class="text-center content-box">
        <img class="error-img" src="../../web/assets/img/error.svg" alt="">
        <h1 class="form-title">Ошибка</h1>
        <p><?= $errorText ?></p>
        <a class="link" href="<?= \yii\helpers\Url::to(['site/index']) ?>">Вернуться назад</a>
    </div>

    <?php
    $js = <<<JS
function getFileNameWithExt(event) {

  if (!event || !event.target || !event.target.files || event.target.files.length === 0) {
    return;
  }

  const name = event.target.files[0].name;
  const lastDot = name.lastIndexOf('.');

  const fileName = name.substring(0, lastDot);
  const ext = name.substring(lastDot + 1);
  let fullName = fileName+'.'+ext;
  $('.input__file-button-text').text(fullName);
  
}
    // let inputs = document.querySelector('#uploadform-imagefile');
    // Array.prototype.forEach.call(inputs, function (input) {
    //   let label = input.nextElementSibling,
    //     labelVal = label.querySelector('.input__file-button-text').innerText;
    //
    //   input.addEventListener('change', function (e) {
    //     let countFiles = '';
    //     if (this.files && this.files.length >= 1)
    //       countFiles = this.files.length;
    //
    //     if (countFiles)
    //       label.querySelector('.input__file-button-text').innerText = 'Выбрано файлов: ' + countFiles;
    //     else
    //       label.querySelector('.input__file-button-text').innerText = labelVal;
    //   });
    // });
JS;

    $this->registerJs($js, \yii\web\View::POS_END);

    ?>

</div>
