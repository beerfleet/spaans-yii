<?php

namespace app\controllers;

class WordController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
