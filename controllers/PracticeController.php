<?php

namespace app\controllers;

use app\models\Chapter;
use app\models\PracticeAnswer;
use app\models\PracticeSelection;
use app\models\Word;
use Yii;
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
            ->orderBy(['number' => SORT_ASC])
            ->all();

        if ($model->load($this->request->post()) && $model->validate()) {
            $wordIds = Word::find()
                ->select('id')
                ->where(['chapter_id' => $model->chapters])
                ->andWhere(['not', ['dutch' => null]])
                ->andWhere(['not', ['dutch' => '']])
                ->column();

            shuffle($wordIds);

            if ($wordIds === []) {
                $model->addError('chapters', 'Er zijn geen woorden gevonden.');
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

        return $this->render('start', [
            'model' => $model,
            'chapters' => $chapters,
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
        $correctAnswers = array_map('trim', explode(',', $practice['nl_to_sp']
            ? $word->spanish
            : $word->dutch));

        $isCorrect = false;
        foreach ($correctAnswers as $correctAnswer) {
            if (mb_strtolower(trim($answerModel->answer)) === mb_strtolower(trim($correctAnswer))) {
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
                : trim($answerModel->answer) . ' is fout!'
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
