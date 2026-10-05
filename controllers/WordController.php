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
                        'bulk-translate' => ['POST'],
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
     * Creates multiple Words and adds them to the database.
     * Line-based: one expression per line, so multi-word expressions stay intact.
     * Two steps: preview first, then confirm & save.
     * @return string|\yii\web\Response
     */
    public function actionCreateMultiple()
    {
        $model = new Word();
        $model->scenario = 'bulkCreate'; // Set the scenario to bulkCreate

        $preview = null;

        if ($model->load($this->request->post()) && $model->validate()) {
            $chapterId = $model->chapter_id === '' ? null : $model->chapter_id;
            $lines = self::parseBulkLines($model->spanish);

            if (empty($lines)) {
                $model->addError('spanish', 'Voer minimaal één woord in (één per regel).');
            } else {
                // Spanish alone is not unique: the same form can have different
                // meanings (e.g. "camino": de weg / ik loop). So we only warn
                // about duplicates / existing forms and let the user decide
                // per row whether to add it anyway.
                $seen = [];
                $existingByForm = [];
                $existingWords = Word::find()
                    ->with('chapter')
                    ->where(['spanish' => array_values(array_unique($lines))])
                    ->all();
                foreach ($existingWords as $existing) {
                    $key = mb_strtolower(trim((string) $existing->spanish));
                    $meaning = $existing->dutch !== null && trim((string) $existing->dutch) !== ''
                        ? (string) $existing->dutch
                        : '(nog onvertaald)';
                    if ($existing->listLabel !== null) {
                        $meaning .= ' [' . $existing->listLabel . ']';
                    }
                    $existingByForm[$key][] = $meaning;
                }

                $preview = [];
                foreach ($lines as $line) {
                    $key = mb_strtolower($line);
                    $status = 'new';
                    if (isset($seen[$key])) {
                        $status = 'duplicate';
                    } elseif (isset($existingByForm[$key])) {
                        $status = 'exists';
                    }
                    $seen[$key] = true;
                    $preview[] = [
                        'spanish' => $line,
                        'status' => $status,
                        'existing' => $existingByForm[$key] ?? [],
                        'add' => $status === 'new',
                    ];
                }

                // Confirm step: save checked rows.
                if ($this->request->post('confirm') !== null) {
                    $checked = $this->request->post('add', []);
                    $saved = 0;
                    $skipped = 0;
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        foreach ($preview as $i => $row) {
                            $wantAdd = isset($checked[$i]);
                            if (!$wantAdd) {
                                $skipped++;
                                continue;
                            }
                            $newWord = new Word();
                            $newWord->scenario = 'bulkCreate';
                            $newWord->spanish = $row['spanish'];
                            $newWord->chapter_id = $chapterId;
                            if ($newWord->save()) {
                                $saved++;
                            } else {
                                Yii::error('Failed to save word: ' . print_r($newWord->errors, true));
                                $skipped++;
                            }
                        }
                        $transaction->commit();
                    } catch (\Throwable $e) {
                        $transaction->rollBack();
                        throw $e;
                    }

                    $parts = [];
                    if ($saved > 0) {
                        $parts[] = $saved . ($saved === 1 ? ' woord toegevoegd' : ' woorden toegevoegd');
                    }
                    if ($skipped > 0) {
                        $parts[] = $skipped . ' overgeslagen';
                    }
                    Yii::$app->session->setFlash(
                        $saved > 0 ? 'success' : 'warning',
                        $saved > 0
                            ? implode(', ', $parts) . '.'
                            : 'Niets toegevoegd. ' . implode(', ', $parts) . '.'
                    );

                    if ($chapterId !== null) {
                        return $this->redirect(['list-untranslated', 'WordSearch[chapter_id]' => $chapterId]);
                    }
                    return $this->redirect(['list-untranslated']);
                }
            }
        }

        return $this->render('create-multiple', [
            'model' => $model,
            'preview' => $preview,
        ]);
    }

    /**
     * Parses bulk textarea input into a list of expressions.
     * One expression per line; falls back to comma/semicolon split
     * when the whole input is a single line (backwards compatible).
     * @param string|null $text
     * @return string[]
     */
    public static function parseBulkLines($text)
    {
        if ($text === null || trim($text) === '') {
            return [];
        }
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        if (strpos($text, "\n") !== false) {
            $parts = explode("\n", $text);
        } else {
            // Single line: allow comma/semicolon separated words (old behaviour).
            $parts = preg_split('/[,;]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            // Strip stray leading/trailing commas/semicolons from line mode.
            $part = trim($part, ",;");
            $part = trim(preg_replace('/\s+/', ' ', $part));
            if ($part !== '') {
                $result[] = $part;
            }
        }
        return $result;
    }

    /**
     * Saves multiple translations + chapters from the untranslated list in one go.
     * Expects POST data: Translation[id][dutch], Translation[id][chapter_id], returnUrl.
     * @return \yii\web\Response
     */
    public function actionBulkTranslate()
    {
        $translations = $this->request->post('Translation', []);
        $returnUrl = $this->request->post('returnUrl', ['list-untranslated']);

        $saved = 0;
        $failed = 0;
        foreach ($translations as $id => $row) {
            $word = Word::findOne((int) $id);
            if ($word === null) {
                continue;
            }
            // Partial update: dutch/chapter may legitimately stay empty here,
            // so validate with the lenient bulkTranslate scenario instead of
            // the default one (which requires dutch).
            $word->scenario = 'bulkTranslate';
            $newDutch = isset($row['dutch']) ? trim((string) $row['dutch']) : '';
            $newChapterId = isset($row['chapter_id']) && $row['chapter_id'] !== '' ? (int) $row['chapter_id'] : null;
            $oldChapterId = $word->chapter_id === null || $word->chapter_id === '' ? null : (int) $word->chapter_id;

            $changed = false;
            if ($newDutch !== '' && $newDutch !== (string) $word->dutch) {
                $word->dutch = $newDutch;
                $changed = true;
            }
            if ($newChapterId !== $oldChapterId) {
                $word->chapter_id = $newChapterId;
                $changed = true;
            }
            if ($changed) {
                if ($word->save()) {
                    $saved++;
                } else {
                    $failed++;
                    Yii::error('Failed to bulk-save word ' . $word->id . ': ' . print_r($word->errors, true));
                }
            }
        }

        if ($failed > 0) {
            Yii::$app->session->setFlash('warning', $saved . ' opgeslagen, ' . $failed . ' mislukt (zie log).');
        } else {
            Yii::$app->session->setFlash(
                $saved > 0 ? 'success' : 'info',
                $saved > 0
                    ? $saved . ($saved === 1 ? ' vertaling opgeslagen.' : ' vertalingen opgeslagen.')
                    : 'Niets gewijzigd.'
            );
        }

        return $this->redirect($returnUrl);
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
            $returnUrl = $this->request->post('returnUrl', '');
            if (is_string($returnUrl) && str_contains($returnUrl, 'list-untranslated')) {
                return $this->redirect($returnUrl);
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
