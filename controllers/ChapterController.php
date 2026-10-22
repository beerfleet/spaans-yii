<?php

namespace app\controllers;

use app\models\Chapter;
use app\models\ChapterSearch;
use app\models\Word;
use app\models\WordSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ChapterController implements the CRUD actions for Chapter model.
 */
class ChapterController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                        'remove-word' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Chapter models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ChapterSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Chapter model as a hub: its words with translation
     * progress, links to manage them, and buttons to practice this list.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $words = $model->getWords()->orderBy(['spanish' => SORT_ASC])->all();

        $translated = 0;
        foreach ($words as $word) {
            if (trim((string) $word->dutch) !== '') {
                $translated++;
            }
        }

        return $this->render('view', [
            'model' => $model,
            'words' => $words,
            'translatedCount' => $translated,
        ]);
    }

    /**
     * Creates a new Chapter model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Chapter();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Chapter model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Chapter model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Manage which words belong to this list: shows words not yet in the
     * list with a checkbox column so they can be added in bulk.
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionManageWords($id)
    {
        $model = $this->findModel($id);

        $searchModel = new WordSearch();
        $dataProvider = $searchModel->searchNotInChapter($id, $this->request->queryParams);

        $chapterList = \app\models\Chapter::find()
            ->select(['name'])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->column();

        $returnUrl = Yii::$app->request->url;

        return $this->render('manage-words', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chapterList' => $chapterList,
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Removes a single word from this list (keeps its other lists).
     * @param int $id chapter id
     * @param int $word_id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRemoveWord($id, $word_id)
    {
        $this->findModel($id);
        Yii::$app->db->createCommand()
            ->delete('{{%chapter_word}}', ['chapter_id' => (int) $id, 'word_id' => (int) $word_id])
            ->execute();
        Yii::$app->session->setFlash('success', 'Woord uit lijst gehaald.');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Finds the Chapter model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Chapter the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Chapter::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
