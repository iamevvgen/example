<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
/** @var yii\web\View $this */

$this->title = Yii::$app->name;
?>
<div class="site-index">

    <div class="text-center content-box">
        <h1 class="form-title">Загрузите отчёт</h1>
        <p>Отчёты загружаются в формате html.</p>
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

        <?= $form->field($model, 'imageFile', [
    'inputOptions' => [
        'onChange' => 'getFileNameWithExt(event)',
    ],
])->fileInput()->label(false) ?>
        <label for="uploadform-imagefile" class="input__file-button">
            <span class="input__file-icon-wrapper"><img class="input__file-icon" src="../../web/assets/img/download.svg" alt="Выбрать файл" width="25"></span>
            <span class="input__file-button-text">Выберите файл</span>
        </label>
        <?= Html::submitButton('Загрузить', ['class' => 'input__file-submit', 'name' => 'login-button']) ?>

        <?php ActiveForm::end() ?>
    </div>
    <p class="index-text">Файл должен представлять собой html таблицу со столбцами Ticket, Open Time, Type, Size, Item, Price, S / L, T / P, Close Time, Price, Commission, Taxes, Swap, Profit. Файл должен быть загружен в таком виде, в каком был сгенерирован, любое ручное изменение информации может привести к повреждению файла. Для корректной работы сайта, необходимо, чтобы в файле содержались сделки как минимум за 2 дня. <a class="link" href="../../web/example.html" download>Пример корректного файла.</a></p>
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
JS;

    $this->registerJs($js, \yii\web\View::POS_END);

    ?>

</div>
