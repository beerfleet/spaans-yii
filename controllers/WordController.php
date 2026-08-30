<?php

namespace app\controllers;

use app\models\Word;
use app\models\WordSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;

/**
 * WordController implements the CRUD actions for Word model.
 */
class WordController extends Controller
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
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Word models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new WordSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionIndexByChapter($chapter_id)
    {
        $searchModel = new WordSearch();
        $dataProvider = $searchModel->searchByChapter($chapter_id, $this->request->queryParams);
        
        $chapter_name = $searchModel->getChapterName($chapter_id);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'chapter_id' => $chapter_id,
            'chapter_name' => $chapter_name,
        ]);
    }

    /**
     * Displays a single Word model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionListUntranslated()
    {

        $searchModel = new WordSearch();
        $dataProvider = $searchModel->searchUntranslated($this->request->queryParams);

        return $this->render('list-untranslated', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Word model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Word();

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
     * Creates multiple Words and adds them to the database
     * @return string|\yii\web\Response
     */
    public function actionCreateMultiple()
    {
        $model = new Word();
        $model->scenario = 'bulkCreate'; // Set the scenario to bulkCreate

        if ($model->load($this->request->post()) && $model->validate()) {
            $words = preg_split('/[\s,;]+/', $model->spanish, -1, PREG_SPLIT_NO_EMPTY);

            Yii::debug('Words to be saved: ' . print_r($words, true));

            foreach ($words as $word) {
                $word = trim($word);
                if (!empty($word)) {
                    $newWord = new Word();
                    $newWord->scenario = "bulkCreate";
                    $newWord->spanish = $word;
                    $newWord->chapter_id = $model->chapter_id;
                    $newWord->created_at = strtotime(date('Y-m-d H:i:s'));
                    $newWord->updated_at = strtotime(date('Y-m-d H:i:s'));

                    // Check if the word is valid before saving
                    if ($newWord->validate()) {
                        $newWord->save();
                        //Yii::debug('Word saved: ' . $newWord->spanish);
                    } else {
                        // Handle validation errors
                        Yii::error('Failed to save word: ' . print_r($newWord->errors, true));
                    }
                }
            }
            return $this->redirect(['index']);
        }

        return $this->render('create-multiple', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Word model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            if ($this->request->post('returnUrl') === 'list-untranslated') {
                return $this->redirect(['list-untranslated']);
            }

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Word model.
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
     * Finds the Word model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Word the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Word::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
