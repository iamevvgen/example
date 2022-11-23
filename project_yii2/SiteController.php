<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\UploadForm;
use yii\web\UploadedFile;
use DOMDocument;


class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */

     

    public function actionIndex()
    {
        $model = new UploadForm();
        $result = false;
        $countTransactions = 0;
        if (Yii::$app->request->isPost) { // проверяем POST

            function is_Date($str)
            {
                return is_numeric(strtotime(preg_replace('/[.]+/', '-', $str)));
            }

            // получаем файл и загружаем его в директорию

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $filename = uniqid();
            $filePath = "uploads/{$filename}.{$model->imageFile->extension}";
            if (file_exists('uploads/')) $model->imageFile->saveAs($filePath);

            if (!file_get_contents($filePath)) {
                $errorText = 'Загружен пустой файл.';
                return $this->render('error', compact('errorText'));
            }

            // получаем html из файла

            if (file_exists($filePath)) {
                $DOM = new DOMDocument;
                $DOM->loadHTML(file_get_contents($filePath));
                $items = $DOM->getElementsByTagName('tr');
                if ($items->length == 0) {
                    $errorText = 'Загружен некорректный файл.';
                    return $this->render('error', compact('errorText'));
                }
                // echo "<pre>";
                // var_dump($DOM->textContent);
                // echo "</pre>";
                $tableArray = array();
                // получаем строки таблицы

                function tdrows($elements)
                {
                    $tr = array();
                    foreach ($elements as $element) {
                        $tr[] = $element->nodeValue;
                    }
                    return $tr;
                }

                $countBuy = 0;
                $countBalance = 0;
                $dates = array();
                foreach ($items as $node) {
                    $transaction = tdrows($node->childNodes);

                    if (!is_array($transaction)) {
                        if ($items->length == 0) {
                            $errorText = 'Загружен некорректный файл.';
                            return $this->render('error', compact('errorText'));
                        }
                    }

                    // отсеиваем ненужные строки, оставляем транзакции типа buy и balance

                    // if (in_array('buy', $transaction)  || in_array('balance', $transaction)) {
                    if ($transaction[2] == 'buy' || $transaction[2] == 'balance') {

                        if (is_numeric(strtr(end($transaction), [' ' => '']))) { // проверяем указано ли в столбце profit число
                            if (is_Date($transaction[1])) { // проверяем указана ли дата Open Time

                                $dates[] = strtok($transaction[1], ' ');




                                // счётчики

                                // if (in_array('buy', $transaction)) $countBuy++;
                                // if (in_array('balance', $transaction)) $countBalance++;

                                if ($transaction[2] == 'buy') $countBuy++;
                                if ($transaction[2] == 'balance') $countBalance++;;
                                $countTransactions++;


                                $dateAndProfitArray = array(
                                    strtok($transaction[1], ' '),
                                    bcdiv((float) strtr(end($transaction), [' ' => '']), 1, 2),
                                );


                                $profitArray[] = $dateAndProfitArray;
                                $changingBalanceArray = array();
                            }
                        }
                    }
                }

                if ($countTransactions == 0) {
                    $errorText = 'Не найдено ни одной сделки. Возможно повреждён файл.';
                    return $this->render('error', compact('errorText'));
                }

                $dates = array_values(array_unique($dates));

                if (count($profitArray) <= 0) {
                    $errorText = 'Внутренняя ошибка сервера.';
                    return $this->render('error', compact('errorText'));
                }

                if (count($dates) <= 0) {
                    $errorText = 'Внутренняя ошибка сервера.';
                    return $this->render('error', compact('errorText'));
                }

                if (count($dates) == 1) {
                    $errorText = 'Загруженные сделки датируются одним днём. Для корректной работы сайта необходимо минимум 2 дня. Возможно была найдена всего одна сделка.';
                    return $this->render('error', compact('errorText'));
                }

                // складываем все сделки одного дня

                for ($i = 0; $i < count($dates); $i++) {
                    $datesAndProfit[][] = $dates[$i];
                }

                for ($i = 0; $i < count($datesAndProfit); $i++) {
                    foreach ($profitArray as $deal) {
                        if ($deal[0] == $datesAndProfit[$i][0]) {
                            $datesAndProfit[$i][1][] = $deal[1];
                        }
                    }
                }

                for ($i = 0; $i < count($datesAndProfit); $i++) {
                    for ($j = 0; $j < count($datesAndProfit[$i][1]); $j++) {
                        $datesAndProfit[$i][2] += $datesAndProfit[$i][1][$j];
                    }
                    unset($datesAndProfit[$i][0]);
                    unset($datesAndProfit[$i][1]);
                }

                for ($i = 0; $i < count($datesAndProfit); $i++) {
                    $datesProfit[] = round($datesAndProfit[$i][2], 2);
                }

                for ($i = 0; $i < count($dates); $i++) {
                    $dates[$i] = "'" . $dates[$i] . "'";
                }
                $dates = implode(',', $dates);

                // высчитываем изменения баланса


                $balanceValue = $datesProfit[0];
                $changingBalanceArray[] = $balanceValue;
                for ($i = 1; $i < count($datesProfit); $i++) {
                    $balanceValue += $datesProfit[$i];
                    $changingBalanceArray[] = round($balanceValue, 2);
                }

                unlink($filePath); // удаляем файл из директории

                $cB = end($changingBalanceArray) - $changingBalanceArray[0];
                if ($cB > 0) {
                    $changeBalance = '+' . $cB;
                } elseif ($cB < 0) {
                    $changeBalance = $cB;
                } else {
                    $changeBalance = 0;
                }

                for ($i = 0; $i < count($changingBalanceArray); $i++) {
                    $changingBalanceArray[$i] = "'" . $changingBalanceArray[$i] . "'";
                }
                $changingBalanceArray = implode(',', $changingBalanceArray);
            }



            return $this->render('result', compact('result', 'countTransactions', 'countBuy', 'countBalance', 'changeBalance', 'dates', 'changingBalanceArray'));
        } else {
            return $this->render('index', compact('model'));
        }
    }

    // /**
    //  * Login action.
    //  *
    //  * @return Response|string
    //  */
    // public function actionLogin()
    // {
    //     if (!Yii::$app->user->isGuest) {
    //         return $this->goHome();
    //     }

    //     $model = new LoginForm();
    //     if ($model->load(Yii::$app->request->post()) && $model->login()) {
    //         return $this->goBack();
    //     }

    //     $model->password = '';
    //     return $this->render('login', [
    //         'model' => $model,
    //     ]);
    // }

    // /**
    //  * Logout action.
    //  *
    //  * @return Response
    //  */
    // public function actionLogout()
    // {
    //     Yii::$app->user->logout();

    //     return $this->goHome();
    // }

    // /**
    //  * Displays contact page.
    //  *
    //  * @return Response|string
    //  */
    // public function actionContact()
    // {
    //     $model = new ContactForm();
    //     if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
    //         Yii::$app->session->setFlash('contactFormSubmitted');

    //         return $this->refresh();
    //     }
    //     return $this->render('contact', [
    //         'model' => $model,
    //     ]);
    // }

    // /**
    //  * Displays about page.
    //  *
    //  * @return string
    //  */
    // public function actionAbout()
    // {
    //     return $this->render('about');
    // }
}
