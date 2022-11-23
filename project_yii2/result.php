<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = Yii::$app->name;
?>
<div class="site-index">
    <div class="content-box-container">
        <?php if (isset($countTransactions) && !empty($countTransactions)) { ?>
            <div class="text-center content-box-result">
                <div class="content-box">
                    <h2 class="form-title">Всего сделок</h2>
                    <p title="<?= $countTransactions ?>"><?= $countTransactions ?></p>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($changeBalance) && !empty($changeBalance)) { ?>
            <div class="text-center content-box-result">
                <div class="content-box">
                    <h2 class="form-title">Изменение баланса</h2>
                    <p title="<?= $changeBalance ?>"><?= $changeBalance ?></p>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($countBuy) && !empty($countBuy)) { ?>
            <div class="text-center content-box-result">
                <div class="content-box">
                    <h2 class="form-title">Сделки типа buy</h2>
                    <p title="<?= $countBuy ?>"><?= $countBuy ?></p>
                </div>
            </div>
        <?php } ?>
        <?php if (isset($countBalance) && !empty($countBalance)) { ?>
            <div class="text-center content-box-result">
                <div class="content-box">
                    <h2 class="form-title">Сделки типа balance</h2>
                    <p title="<?= $countBalance ?>"><?= $countBalance ?></p>
                </div>
            </div>
        <?php } ?>

    </div>
    <?php if (isset($dates) && !empty($dates) && isset($changingBalanceArray) && !empty($changingBalanceArray)) { ?>
        <h2>График изменения баланса</h2>
        <div class="chart-container">
            <canvas id="myChart"></canvas>
        </div>
        <p class="result-text">Дата: <?=Yii::$app->formatter->asDate(time(), 'php:d.m.Y')?></p>
        <p><a class="link print-button" href="#" onclick="window.print();">Распечатать отчёт</a></p>
        <?php
        $js = <<<JS
        const ctx = document.getElementById('myChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [$dates],
                datasets: [{
                    label: 'Баланс',
                    backgroundColor: 'rgb(255, 93, 93)',
                    borderColor: 'rgb(217 65 65)',
                    data: [$changingBalanceArray],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        }); 
    JS;

        $this->registerJs($js, \yii\web\View::POS_READY);

        ?>
    <?php } ?>
</div>