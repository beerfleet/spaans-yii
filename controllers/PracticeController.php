<?php

namespace app\controllers;

use app\models\Chapter;
use app\models\PracticeAnswer;
use app\models\PracticeSelection;
use app\models\Word;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use app\models\WordStatistic;

class PracticeController extends Controller
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
                        'repeat' => ['POST'],
                        'stop' => ['POST'],
                        'practice-word' => ['POST'],
                        'reset-stat' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Action to start the practice session.
     * @return string|Response
     */
    public function actionStart(): string|Response
    {
        $model = new PracticeSelection();
        $chapters = Chapter::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        // Translatable words per list (for the counts next to each list).
        // Untranslated words never practice, in any direction.
        // NB: Query::column() returns the FIRST selected column, so map
        // chapter_id => count explicitly instead.
        $counts = ArrayHelper::map(
            Word::findTranslatable()
                ->select(['cw.chapter_id', 'COUNT(*) AS n'])
                ->innerJoin(['cw' => '{{%chapter_word}}'], 'cw.word_id = {{%word}}.[[id]]')
                ->groupBy(['cw.chapter_id'])
                ->asArray()
                ->all(),
            'chapter_id',
            'n'
        );
        $translatableTotal = (int) Word::findTranslatable()->count();
        $untranslatedCounts = ArrayHelper::map(
            Word::find()
                ->select(['cw.chapter_id', 'COUNT(*) AS n'])
                ->innerJoin(['cw' => '{{%chapter_word}}'], 'cw.word_id = {{%word}}.[[id]]')
                ->where(['or', ['dutch' => null], ['dutch' => '']])
                ->groupBy(['cw.chapter_id'])
                ->asArray()
                ->all(),
            'chapter_id',
            'n'
        );
        $untranslatedTotal = (int) Word::find()
            ->where(['or', ['dutch' => null], ['dutch' => '']])
            ->count();
        // Translated words without any practice history, per list.
        $freshCounts = ArrayHelper::map(
            Word::findTranslatable()
                ->select(['cw.chapter_id', 'COUNT(*) AS n'])
                ->innerJoin(['cw' => '{{%chapter_word}}'], 'cw.word_id = {{%word}}.[[id]]')
                ->leftJoin(
                    ['wsu' => WordStatistic::tableName()],
                    'wsu.word_id = {{%word}}.[[id]]'
                )
                ->andWhere(['wsu.id' => null])
                ->groupBy(['cw.chapter_id'])
                ->asArray()
                ->all(),
            'chapter_id',
            'n'
        );

        if ($model->load($this->request->post()) && $model->validate()) {
            $useAll = (bool) $model->all_chapters;
            if (!$useAll && empty($model->chapters)) {
                $model->addError('chapters', 'Kies minimaal één lijst of vink “Alle woorden oefenen” aan.');
            } else {
                $selectedWordIds = $this->buildSessionWordIds($model, $useAll);

                if ($selectedWordIds === []) {
                    $model->addError('chapters', 'Er zijn geen (vertaalde) woorden gevonden voor deze keuze.');
                } else {
                    Yii::$app->session->set('practice', [
                        'chapters' => $model->chapters,
                        'nl_to_sp' => $model->nl_to_sp,
                        'word_ids' => $selectedWordIds,
                        'position' => 0,
                        'correct' => 0,
                        'results' => [],
                    ]);

                    return $this->redirect(['practice']);
                }
            }
        }

        return $this->render('start', [
            'model' => $model,
            'chapters' => $chapters,
            'counts' => $counts,
            'translatableTotal' => $translatableTotal,
            'untranslatedCounts' => $untranslatedCounts,
            'untranslatedTotal' => $untranslatedTotal,
            'freshCounts' => $freshCounts,
        ]);
    }

    /**
     * Builds the word id list for a practice session.
     * Difficult-first orders by misses (using this direction's stats);
     * otherwise the candidates are shuffled. Same-prompt homonyms are
     * deduplicated so each prompt appears once per session, then the list
     * is cut to max_words.
     * @param PracticeSelection $model
     * @param bool $useAll practice all lists instead of the selected ones
     * @return int[]
     */
    private function buildSessionWordIds(PracticeSelection $model, $useAll)
    {
        $promptCol = $model->nl_to_sp ? 'dutch' : 'spanish';
        $query = Word::findTranslatable()->select([
            '{{%word}}.[[id]] AS id',
            '{{%word}}.[[' . $promptCol . ']] AS prompt',
        ]);
        if (!$useAll) {
            $query->innerJoin(
                ['cw' => '{{%chapter_word}}'],
                'cw.word_id = {{%word}}.[[id]]'
            )->andWhere(['cw.chapter_id' => $model->chapters]);
        }
        if ($model->only_unpracticed) {
            // Truly fresh words: no practice history in either direction.
            $query->leftJoin(
                ['wsu' => WordStatistic::tableName()],
                'wsu.word_id = {{%word}}.[[id]]'
            )->andWhere(['wsu.id' => null]);
        }

        if ($model->difficult_first) {
            $query->leftJoin(
                ['ws' => WordStatistic::tableName()],
                'ws.word_id = {{%word}}.[[id]] AND ws.nl_to_sp = :dir',
                [':dir' => $model->nl_to_sp ? 1 : 0]
            )
            ->addSelect(['COALESCE(ws.incorrect_count, 0) AS ic', 'COALESCE(ws.correct_count, 0) AS cc'])
            ->orderBy(['ic' => SORT_DESC, 'cc' => SORT_ASC]);
            $rows = $query->asArray()->all();
        } else {
            $rows = $query->asArray()->all();
            shuffle($rows);
        }

        $seen = [];
        $wordIds = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) ($row['prompt'] ?? '')));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $wordIds[] = (int) $row['id'];
        }

        $maxWords = max(1, (int) $model->max_words);
        return array_slice($wordIds, 0, $maxWords);
    }

    /**
     * Action to handle the practice session.
     * @return string|Response
     */
    public function actionPractice(): string|Response
    {
        $session = Yii::$app->session;
        $practice = $session->get('practice');

        if ($practice === null || empty($practice['word_ids'])) {
            return $this->redirect(['start']);
        }

        $position = $practice['position'];
        $wordIds = $practice['word_ids'];

        if ($position >= count($wordIds)) {
            $session->set('practiceResult', [
                'correct' => $practice['correct'] ?? 0,
                'total' => count($wordIds),
                'nl_to_sp' => $practice['nl_to_sp'] ?? true,
                'results' => $practice['results'] ?? [],
            ]);
            $session->remove('practice');

            return $this->redirect(['result']);
        }

        $word = Word::findOne($wordIds[$position]);

        if ($word === null) {
            // Word deleted mid-session: skip it instead of looping forever.
            $practice['position']++;
            $session->set('practice', $practice);
            return $this->redirect(['practice']);
        }

        $answerModel = new PracticeAnswer();

        if (
            $answerModel->load($this->request->post())
            && $answerModel->validate()
        ) {
            $this->processAnswer($word, $answerModel, $practice, $session);
            return $this->redirect(['practice']);
        }

        // Show a single random variant of multi-alternative prompts.
        $question = $word->pickPromptVariant((bool) $practice['nl_to_sp']);
        $practice['shown'][$position] = $question;
        $session->set('practice', $practice);

        return $this->render('practice', [
            'word' => $word,
            'nlToSp' => $practice['nl_to_sp'],
            'progress' => $position + 1,
            'total' => count($wordIds),
            'answerModel' => $answerModel,
            'question' => $question,
            'meaningsCount' => $word->countPracticeMeanings((bool) $practice['nl_to_sp']),
        ]);
    }

    /**
     * Private method to process the user's answer.
     * @param Word $word
     * @param PracticeAnswer $answerModel
     * @param array &$practice
     * @param \yii\web\Session $session
     */
    private function processAnswer(Word $word, PracticeAnswer $answerModel, array &$practice, \yii\web\Session $session): void
    {
        $acceptedAnswers = $word->getPracticeAnswers((bool) $practice['nl_to_sp']);
        $givenRaw = trim($answerModel->answer);
        $given = mb_strtolower($givenRaw);

        // Exact match first; otherwise fall back to accent-lenient comparison
        // (for keyboards without Spanish accents). ñ stays strict, see normalizeAnswer().
        $isCorrect = false;
        $isExact = false;
        $displayCorrect = $acceptedAnswers[0] ?? '';
        foreach ($acceptedAnswers as $correctAnswer) {
            $correctAnswer = trim($correctAnswer);
            if ($given === mb_strtolower($correctAnswer)) {
                $isCorrect = true;
                $isExact = true;
                $displayCorrect = $correctAnswer;
                break;
            }
        }
        if (!$isCorrect) {
            $normalizedGiven = Word::normalizeAnswer($given);
            foreach ($acceptedAnswers as $correctAnswer) {
                $correctAnswer = trim($correctAnswer);
                if ($normalizedGiven === Word::normalizeAnswer($correctAnswer)) {
                    $isCorrect = true;
                    $displayCorrect = $correctAnswer;
                    break;
                }
            }
        }

        $wordStatistic = WordStatistic::find()->where(['word_id' => $word->id, 'nl_to_sp' => $practice['nl_to_sp']])->one();

        if ($wordStatistic === null) {
            $wordStatistic = new WordStatistic([
                'word_id' => $word->id,
                'correct_count' => 0,
                'incorrect_count' => 0,
                'nl_to_sp' => $practice['nl_to_sp'],
            ]);
        }

        if ($isCorrect) {
            $practice['correct']++;
            $wordStatistic->correct_count += 1;
        } else {
            $wordStatistic->incorrect_count += 1;
        }

        $wordStatistic->last_practiced_at = time();
        $wordStatistic->save(false);

        $practice['results'] = $practice['results'] ?? [];
        $practice['results'][] = [
            'word_id' => $word->id,
            'prompt' => (string) ($practice['shown'][$practice['position']] ?? $word->getWordBasedOnDirection((bool) $practice['nl_to_sp'])),
            'given' => trim($answerModel->answer),
            'accepted' => $acceptedAnswers,
            'correct' => $isCorrect,
        ];

        $session->setFlash(
            $isCorrect ? 'success' : 'error',
            $isCorrect
                ? ($isExact
                    ? $givenRaw . ' is correct!'
                    : 'Goed — let op het accent: ' . $displayCorrect)
                : trim($answerModel->answer) . ' is fout! Mogelijk: ' . implode(', ', array_slice($acceptedAnswers, 0, 5))
                    . (count($acceptedAnswers) > 5 ? ' …' : '')
        );

        $practice['position']++;
        $session->set('practice', $practice);
    }

    /**
     * Starts a one-word practice session straight from a word page.
     * @param int $id word ID
     * @return Response
     */
    public function actionPracticeWord($id)
    {
        $word = Word::findOne((int) $id);
        if ($word === null) {
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }

        if (trim((string) $word->dutch) === '') {
            Yii::$app->session->setFlash('info', 'Vertaal dit woord eerst — onvertaalde woorden doen niet mee aan oefenen.');
            return $this->redirect(['word/view', 'id' => $word->id]);
        }

        $nlToSp = Yii::$app->request->post('dir', 'nl') !== 'sp';
        Yii::$app->session->set('practice', [
            'chapters' => $word->getChapterIds(),
            'nl_to_sp' => $nlToSp,
            'word_ids' => [(int) $word->id],
            'position' => 0,
            'correct' => 0,
            'results' => [],
        ]);

        return $this->redirect(['practice']);
    }

    /**
     * Aborts the running practice session and forgets its progress.
     * @return Response
     */
    public function actionStop(): Response
    {
        Yii::$app->session->remove('practice');
        Yii::$app->session->setFlash('info', 'Oefening gestopt.');
        return $this->redirect(['start']);
    }

    /**
     * Action to display the practice result, with a per-word review.
     * @return string|Response
     */
    public function actionResult(): string|Response
    {
        $result = Yii::$app->session->get('practiceResult');

        if ($result === null) {
            return $this->redirect(['start']);
        }

        $results = $result['results'] ?? [];
        $wrongIds = [];
        foreach ($results as $row) {
            if (empty($row['correct'])) {
                $wrongIds[] = (int) $row['word_id'];
            }
        }

        return $this->render('result', [
            'correct' => $result['correct'],
            'total' => $result['total'],
            'results' => $results,
            'wrongCount' => count(array_unique($wrongIds)),
        ]);
    }

    /**
     * Starts a repeat session with only the words answered wrong.
     * Deleted words are filtered out first.
     * @return Response
     */
    public function actionRepeat(): Response
    {
        $result = Yii::$app->session->get('practiceResult');

        if ($result === null || empty($result['results'])) {
            return $this->redirect(['start']);
        }

        $wrongIds = [];
        foreach ($result['results'] as $row) {
            if (empty($row['correct'])) {
                $wrongIds[] = (int) $row['word_id'];
            }
        }
        $wrongIds = array_values(array_unique($wrongIds));

        if ($wrongIds === []) {
            return $this->redirect(['result']);
        }

        $existing = Word::find()->select('id')->where(['id' => $wrongIds])->column();
        $wrongIds = array_values(array_intersect($wrongIds, array_map('intval', $existing)));

        if ($wrongIds === []) {
            Yii::$app->session->setFlash('info', 'De foute woorden bestaan niet meer.');
            return $this->redirect(['result']);
        }

        Yii::$app->session->set('practice', [
            'chapters' => [],
            'nl_to_sp' => $result['nl_to_sp'] ?? true,
            'word_ids' => $wrongIds,
            'position' => 0,
            'correct' => 0,
            'results' => [],
        ]);

        return $this->redirect(['practice']);
    }

    /**
     * Action to display statistics, optionally filtered by list.
     * Filter value 0 means "no list" (words practiced without one).
     */
    public function actionStats()
    {
        $chapterId = Yii::$app->request->get('chapter_id', '');
        $query = WordStatistic::find()
            ->select([
                '{{%word_statistic}}.*',
                'success' => new \yii\db\Expression('ROUND(100 * {{%word_statistic}}.[[correct_count]] / NULLIF({{%word_statistic}}.[[correct_count]] + {{%word_statistic}}.[[incorrect_count]], 0))'),
            ])
            ->joinWith('word');

        if ((string) $chapterId === '0') {
            $query->andWhere(['not exists', (new \yii\db\Query())
                ->select(['cw.word_id'])
                ->from(['cw' => '{{%chapter_word}}'])
                ->where('cw.word_id = {{%word}}.[[id]]')]);
        } elseif ($chapterId !== '' && $chapterId !== null && ctype_digit((string) $chapterId)) {
            $query->andWhere(['exists', (new \yii\db\Query())
                ->select(['cw.word_id'])
                ->from(['cw' => '{{%chapter_word}}'])
                ->where('cw.word_id = {{%word}}.[[id]]')
                ->andWhere(['cw.chapter_id' => (int) $chapterId])]);
        } else {
            $chapterId = '';
        }

        $dir = Yii::$app->request->get('dir', '');
        if ($dir === '1' || $dir === '0') {
            $query->andWhere(['word_statistic.nl_to_sp' => (int) $dir]);
        } else {
            $dir = '';
        }

        // Summary over the filtered set (not just the page).
        $sumRow = (clone $query)
            ->select([
                'answers' => 'SUM({{%word_statistic}}.[[correct_count]] + {{%word_statistic}}.[[incorrect_count]])',
                'good' => 'SUM({{%word_statistic}}.[[correct_count]])',
                'words' => 'COUNT(DISTINCT {{%word_statistic}}.[[word_id]])',
            ])
            ->asArray()
            ->one();
        $summary = [
            'answers' => (int) ($sumRow['answers'] ?? 0),
            'good' => (int) ($sumRow['good'] ?? 0),
            'words' => (int) ($sumRow['words'] ?? 0),
        ];
        $summary['percent'] = $summary['answers'] > 0 ? round(100 * $summary['good'] / $summary['answers']) : null;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['incorrect_count' => SORT_DESC],
                'attributes' => [
                    'spanish' => [
                        'asc' => ['word.spanish' => SORT_ASC],
                        'desc' => ['word.spanish' => SORT_DESC],
                        'label' => 'Spaans',
                    ],
                    'dutch' => [
                        'asc' => ['word.dutch' => SORT_ASC],
                        'desc' => ['word.dutch' => SORT_DESC],
                        'label' => 'Nederlands',
                    ],
                    'nl_to_sp' => [
                        'asc' => ['word_statistic.nl_to_sp' => SORT_ASC],
                        'desc' => ['word_statistic.nl_to_sp' => SORT_DESC],
                        'label' => 'Richting',
                    ],
                    'correct_count' => ['label' => 'Goed'],
                    'incorrect_count' => ['label' => 'Fout'],
                    'success' => [
                        'asc' => ['success' => SORT_ASC],
                        'desc' => ['success' => SORT_DESC],
                        'label' => 'Succes %',
                    ],
                    'last_practiced_at' => ['label' => 'Laatst'],
                ],
            ],
        ]);

        return $this->render('stats', [
            'dataProvider' => $dataProvider,
            'chapterId' => $chapterId,
            'chapterList' => Chapter::find()
                ->select(['name'])
                ->orderBy(['name' => SORT_ASC])
                ->indexBy('id')
                ->column(),
            'dir' => $dir,
            'summary' => $summary,
        ]);
    }

    /**
     * Deletes one word's statistics (fresh start for that word+direction).
     * @param int $id statistic ID
     * @return Response
     */
    public function actionResetStat($id)
    {
        $stat = WordStatistic::findOne((int) $id);
        $chapterId = Yii::$app->request->post('chapter_id', '');
        $dir = Yii::$app->request->post('dir', '');
        if ($stat !== null) {
            $stat->delete();
            Yii::$app->session->setFlash('success', 'Statistieken gewist — dit woord begint opnieuw.');
        }
        return $this->redirect(['stats', 'chapter_id' => $chapterId, 'dir' => $dir]);
    }
}
