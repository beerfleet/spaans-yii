<?php

namespace app\controllers;

use app\models\Chapter;
use app\models\PracticeAnswer;
use app\models\PracticeSelection;
use app\models\Word;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class PracticeController extends Controller
{
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
            $correctAnswer = $practice['nl_to_sp']
                ? $word->spanish
                : $word->dutch;

            $isCorrect = mb_strtolower(trim($answerModel->answer))
                === mb_strtolower(trim($correctAnswer));

            if ($isCorrect) {
                $practice['correct']++;
            }

            $session->setFlash(
                $isCorrect ? 'success' : 'error',
                $isCorrect
                ? trim($answerModel->answer) . ' is correct!'
                : trim($answerModel->answer) . ' is fout!'
            );

            $practice['position']++;
            $session->set('practice', $practice);

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
}