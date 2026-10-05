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
                ->select(['chapter_id', 'COUNT(*) AS n'])
                ->groupBy(['chapter_id'])
                ->asArray()
                ->all(),
            'chapter_id',
            'n'
        );
        $translatableTotal = (int) Word::findTranslatable()->count();
        $untranslatedCounts = ArrayHelper::map(
            Word::find()
                ->select(['chapter_id', 'COUNT(*) AS n'])
                ->where(['or', ['dutch' => null], ['dutch' => '']])
                ->groupBy(['chapter_id'])
                ->asArray()
                ->all(),
            'chapter_id',
            'n'
        );
        $untranslatedTotal = (int) Word::find()
            ->where(['or', ['dutch' => null], ['dutch' => '']])
            ->count();

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
            $query->andWhere(['chapter_id' => $model->chapters]);
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

        return $this->render('practice', [
            'word' => $word,
            'nlToSp' => $practice['nl_to_sp'],
            'progress' => $position + 1,
            'total' => count($wordIds),
            'answerModel' => $answerModel,
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

        $wordStatistic->save(false);

        $practice['results'] = $practice['results'] ?? [];
        $practice['results'][] = [
            'word_id' => $word->id,
            'prompt' => (string) $word->getWordBasedOnDirection((bool) $practice['nl_to_sp']),
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
     * Action to display statistics.
     */
    public function actionStats()
    {
        $query = WordStatistic::find()
            ->select([
                '{{%word_statistic}}.*',
                'success' => new \yii\db\Expression('ROUND(100 * {{%word_statistic}}.[[correct_count]] / NULLIF({{%word_statistic}}.[[correct_count]] + {{%word_statistic}}.[[incorrect_count]], 0))'),
            ])
            ->joinWith('word');

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
                ],
            ],
        ]);

        return $this->render('stats', [
            'dataProvider' => $dataProvider,
        ]);
    }
}
