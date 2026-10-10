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
                        'bulk-assign' => ['POST'],
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
     * Displays a single Word model as a hub: edit actions, direct practice
     * links, homonyms sharing the Spanish form, and practice statistics.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
            'homonyms' => Word::find()
                ->where(['spanish' => $model->spanish])
                ->andWhere(['not', ['id' => $model->id]])
                ->with('chapters')
                ->all(),
            'statistics' => \app\models\WordStatistic::find()
                ->where(['word_id' => $model->id])
                ->all(),
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
            $model->load($this->request->queryParams); // Allows preselecting, e.g. chapter from a list page.
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
        $model->scenario = 'bulkForm'; // Bulk textarea lives in bulkText (no 255 limit)

        $preview = null;

        if ($model->load($this->request->post()) && $model->validate()) {
            $lines = self::parseBulkLines($model->bulkText);

            if (empty($lines)) {
                $model->addError('bulkText', 'Voer minimaal één woord in (één per regel).');
            } else {
                $preview = $this->buildBulkPreview($lines);

                // Confirm step: save checked rows.
                if ($this->request->post('confirm') !== null) {
                    $chapterIds = $model->getChapterIds();
                    [$saved, $skipped] = $this->saveBulkPreview($preview, $chapterIds);
                    $this->flashBulkResult($saved, $skipped);

                    if (count($chapterIds) === 1) {
                        return $this->redirect(['list-untranslated', 'WordSearch[chapter_id]' => $chapterIds[0]]);
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
     * Builds one preview row per line, flagging duplicates within the input
     * and forms that already exist (with their known meanings for context).
     * Spanish alone is not unique: the same form can have different
     * meanings (e.g. "camino": de weg / ik loop). So we only warn and let
     * the user decide per row whether to add it anyway.
     * @param string[] $lines parsed expressions
     * @return array rows with spanish, status, existing meanings and add flag
     */
    private function buildBulkPreview(array $lines)
    {
        $seen = [];
        $existingByForm = [];
        $existingByNormalized = [];
        $existingWords = Word::find()
            ->with('chapters')
            ->where(['spanish' => array_values(array_unique($lines))])
            ->all();
        foreach ($existingWords as $existing) {
            $key = mb_strtolower(trim((string) $existing->spanish));
            $meaning = $existing->dutch !== null && trim((string) $existing->dutch) !== ''
                ? (string) $existing->dutch
                : '(nog onvertaald)';
            if ($existing->getListsText() !== null) {
                $meaning .= ' [' . $existing->getListsText() . ']';
            }
            $existingByForm[$key][] = $meaning;
            $existingByNormalized[Word::normalizeAnswer($key)][] = (string) $existing->spanish;
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
            $row = [
                'spanish' => $line,
                'status' => $status,
                'existing' => $existingByForm[$key] ?? [],
                'add' => $status === 'new',
            ];
            if ($status === 'new') {
                // Same word without accents (typed on a keyboard lacking them)?
                // Point at the existing form instead of silently doubling it.
                foreach (array_unique($existingByNormalized[Word::normalizeAnswer($key)] ?? []) as $form) {
                    if (mb_strtolower($form) !== $key) {
                        $row['existing'][] = 'lijkt op “' . $form . '” (accent?)';
                    }
                }
            }
            $preview[] = $row;
        }

        return $preview;
    }

    /**
     * Saves the checked preview rows in a transaction.
     * Unchecked boxes are absent from POST, so only explicitly checked
     * rows are saved (homonyms stay opt-in).
     * @param array $preview rows from buildBulkPreview()
     * @param int[] $chapterIds lists for all new words
     * @return int[] [saved, skipped]
     */
    private function saveBulkPreview(array $preview, array $chapterIds)
    {
        $checked = $this->request->post('add', []);
        $saved = 0;
        $skipped = 0;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($preview as $i => $row) {
                if (!isset($checked[$i])) {
                    $skipped++;
                    continue;
                }
                $newWord = new Word();
                $newWord->scenario = 'bulkCreate';
                $newWord->spanish = $row['spanish'];
                $newWord->chapterIds = $chapterIds;
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

        return [$saved, $skipped];
    }

    /**
     * Reports the bulk save outcome to the user.
     * @param int $saved
     * @param int $skipped
     */
    private function flashBulkResult($saved, $skipped)
    {
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
    }

    /**
     * Parses bulk textarea input into a list of expressions.
     * Strictly one expression per line: commas and semicolons inside
     * a line are content (e.g. "Hola, ¿cómo estás?" stays one record),
     * so sentences survive bulk import.
     * @param string|null $text
     * @return string[]
     */
    public static function parseBulkLines($text)
    {
        if ($text === null || trim($text) === '') {
            return [];
        }
        $parts = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            // Strip stray leading/trailing separators, keep inner punctuation.
            $part = trim($part, ",;");
            $part = trim(preg_replace('/\s+/', ' ', $part));
            if ($part !== '') {
                $result[] = $part;
            }
        }
        return $result;
    }

    /**
     * Saves multiple translations + lists from the bulk grids in one go.
     * Expects POST data: Translation[id][dutch], Translation[id][chapter_ids][],
     * returnUrl. Lists are multi-select: the submitted set replaces the
     * word's lists (empty selection clears them).
     * @return \yii\web\Response
     */
    public function actionBulkTranslate()
    {
        $translations = $this->request->post('Translation', []);
        $returnUrl = $this->request->post('returnUrl', ['list-untranslated']);

        // Validate all submitted list ids in one query.
        $postedIds = [];
        foreach ((array) $translations as $row) {
            foreach ((array) ($row['chapter_ids'] ?? []) as $v) {
                if ($v !== '' && $v !== null) {
                    $postedIds[] = (int) $v;
                }
            }
        }
        $validIds = array_map('intval', \app\models\Chapter::find()
            ->select(['id'])
            ->where(['id' => array_values(array_unique($postedIds))])
            ->column());

        $saved = 0;
        $failed = 0;
        // Current lists for all posted words in one query.
        $oldByWord = [];
        foreach ((new \yii\db\Query())->select(['word_id', 'chapter_id'])->from('{{%chapter_word}}')->where(['word_id' => array_map('intval', array_keys((array) $translations))])->all() as $link) {
            $oldByWord[(int) $link['word_id']][] = (int) $link['chapter_id'];
        }
        foreach ($translations as $id => $row) {
            $word = Word::findOne((int) $id);
            if ($word === null) {
                continue;
            }
            // Partial update: dutch/lists may legitimately stay empty here,
            // so validate with the lenient bulkTranslate scenario instead of
            // the default one (which requires dutch).
            $word->scenario = 'bulkTranslate';
            $newDutch = isset($row['dutch']) ? trim((string) $row['dutch']) : '';
            $newChapterIds = [];
            foreach ((array) ($row['chapter_ids'] ?? []) as $v) {
                if ($v !== '' && $v !== null && in_array((int) $v, $validIds, true)) {
                    $newChapterIds[] = (int) $v;
                }
            }
            $newChapterIds = array_values(array_unique($newChapterIds));
            $oldChapterIds = $oldByWord[(int) $id] ?? [];
            sort($oldChapterIds);
            $compared = $newChapterIds;
            sort($compared);

            $changed = false;
            if ($newDutch !== '' && $newDutch !== (string) $word->dutch) {
                $word->dutch = $newDutch;
                $changed = true;
            }
            if ($compared !== $oldChapterIds) {
                $word->chapterIds = $newChapterIds;
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
                    ? $saved . ($saved === 1 ? ' wijziging opgeslagen.' : ' wijzigingen opgeslagen.')
                    : 'Niets gewijzigd.'
            );
        }

        return $this->redirect($returnUrl);
    }

    /**
     * Links selected words to one list in a single action — e.g. a pile of
     * list-less words from the "no list" filter. Existing lists are kept;
     * value 'none' instead removes words from all their lists.
     * Expects POST data: assign_ids[], assign_list, returnUrl.
     * @return \yii\web\Response
     */
    public function actionBulkAssign()
    {
        $ids = array_map('intval', (array) $this->request->post('assign_ids', []));
        $target = $this->request->post('assign_list', '');
        $returnUrl = $this->request->post('returnUrl', ['index']);

        if ($ids === []) {
            Yii::$app->session->setFlash('info', 'Niets geselecteerd.');
            return $this->redirect($returnUrl);
        }
        if ($target === '' || $target === null) {
            Yii::$app->session->setFlash('warning', 'Kies eerst een lijst om naar toe te wijzen.');
            return $this->redirect($returnUrl);
        }
        if ($target === 'none') {
            $chapterIds = [];
        } elseif (ctype_digit((string) $target) && \app\models\Chapter::findOne((int) $target) !== null) {
            $chapterIds = [(int) $target];
        } else {
            Yii::$app->session->setFlash('warning', 'Onbekende lijst gekozen.');
            return $this->redirect($returnUrl);
        }

        $saved = 0;
        $skipped = 0;
        foreach (Word::findAll(['id' => $ids]) as $word) {
            // Lenient scenario: assigning lists must also work for
            // untranslated words (dutch may stay empty).
            $word->scenario = 'bulkTranslate';
            $oldChapterIds = $word->getChapterIds();
            sort($oldChapterIds);
            $newChapterIds = $chapterIds === []
                ? []
                : array_values(array_unique(array_merge($oldChapterIds, $chapterIds)));
            sort($newChapterIds);
            if ($newChapterIds === $oldChapterIds) {
                $skipped++;
                continue;
            }
            $word->chapterIds = $newChapterIds;
            if ($word->save()) {
                $saved++;
            } else {
                $skipped++;
                Yii::error('Failed to bulk-assign word ' . $word->id . ': ' . print_r($word->errors, true));
            }
        }

        Yii::$app->session->setFlash(
            $saved > 0 ? 'success' : 'info',
            $saved > 0
                ? $saved . ($saved === 1 ? ' woord toegewezen.' : ' woorden toegewezen.')
                : 'Niets gewijzigd.'
        );

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
     * Shows an overview of 'double' words: Spanish forms occurring in more
     * than one row, either as homonyms (different meanings, e.g. "camino":
     * de weg / ik loop) or as exact duplicate entries. Grouping uses the
     * accent-lenient form, so "pasion" and "pasión" land in one group,
     * while "ano" and "año" stay apart (ñ is a distinct letter).
     * @return string
     */
    public function actionDuplicates()
    {
        $groups = Word::findDuplicateGroups();

        $wordCount = 0;
        foreach ($groups as $group) {
            $wordCount += count($group);
        }

        return $this->render('duplicates', [
            'groups' => $groups,
            'groupCount' => count($groups),
            'wordCount' => $wordCount,
        ]);
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
