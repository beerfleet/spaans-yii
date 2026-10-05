<?php

namespace app\controllers;

use app\models\Chapter;
use app\models\PracticeAnswer;
use app\models\PracticeSelection;
use app\models\Word;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use app\models\WordStatistic;

class PracticeController extends Controller
{
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
                $query = Word::findTranslatable()->select('id');
                if (!$useAll) {
                    $query->andWhere(['chapter_id' => $model->chapters]);
                }

                $wordIds = $query->column();

                shuffle($wordIds);

                if ($wordIds === []) {
                    $model->addError('chapters', 'Er zijn geen (vertaalde) woorden gevonden voor deze keuze.');
                } else {
                    $maxWords = max(1, (int) $model->max_words);
                    $selectedWordIds = array_map('intval', array_slice($wordIds, 0, $maxWords));

                    Yii::$app->session->set('practice', [
                        'chapters' => $model->chapters,
                        'nl_to_sp' => $model->nl_to_sp,
                        'word_ids' => $selectedWordIds,
                        'position' => 0,
                        'correct' => 0,
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
            ]);
            $session->remove('practice');

            return $this->redirect(['result']);
        }

        $word = Word::findOne($wordIds[$position]);

        if ($word === null) {
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
        $given = mb_strtolower(trim($answerModel->answer));

        $isCorrect = false;
        foreach ($acceptedAnswers as $correctAnswer) {
            if ($given === mb_strtolower(trim($correctAnswer))) {
                $isCorrect = true;
                break;
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

        $session->setFlash(
            $isCorrect ? 'success' : 'error',
            $isCorrect
                ? trim($answerModel->answer) . ' is correct!'
                : trim($answerModel->answer) . ' is fout! Mogelijk: ' . implode(', ', array_slice($acceptedAnswers, 0, 5))
                    . (count($acceptedAnswers) > 5 ? ' …' : '')
        );

        $practice['position']++;
        $session->set('practice', $practice);
    }

    /**
     * Action to display the practice result.
     * @return string|Response
     */
    public function actionResult(): string|Response
    {
        $result = Yii::$app->session->get('practiceResult');

        if ($result === null) {
            return $this->redirect(['start']);
        }

        return $this->render('result', [
            'correct' => $result['correct'],
            'total' => $result['total'],
        ]);
    }

    /**
     * Action to display statistics.
     */
    public function actionStats()
    {
        $stats = WordStatistic::find()->with('word')->all();
        return $this->render('stats', [
            'stats' => $stats,
        ]);
    }
}
