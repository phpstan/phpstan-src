<?php

namespace Bug8004;

use function PHPStan\Testing\assertType;

class Importer
{
    /**
     * @param array{questions: array<int, array{question: string|null, answer_1: string|\DateTimeInterface|null, answer_2: string|\DateTimeInterface|null, answer_3: string|\DateTimeInterface|null, answer_4: string|\DateTimeInterface|null, right_answer: int|float|string|\DateTimeInterface|null, right_answer_explanation: string|null}>} $importQuiz
     *
     * @return list<array{line: int, type: QuizImportErrorType::*, value: int|float|string|\DateTimeInterface|null}>
     */
    public function getErrorsOnInvalidQuestions(array $importQuiz, int $key): array
    {
        $errors = [];

        foreach ($importQuiz['questions'] as $index => $question) {
            if (empty($question['question']) && empty($question['answer_1']) && empty($question['answer_2']) && empty($question['answer_3']) && empty($question['answer_4']) && empty($question['right_answer']) && empty($question['right_answer_explanation'])) {
                continue;
            }

            if (empty($question['question'])) {
                $errors[] = ['line' => $key, 'type' => QuizImportErrorType::EMPTY_QUESTION, 'value' => $index + 1];
            } elseif (255 < mb_strlen($question['question'])) {
                $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_QUESTION_TOO_LONG, 'value' => $index + 1];
            }

            if (null === $question['answer_1'] || '' === $question['answer_1'] || null === $question['answer_2'] || '' === $question['answer_2']) {
                $errors[] = ['line' => $key, 'type' => QuizImportErrorType::EMPTY_ANSWER, 'value' => $index + 1];
            }

            if (null !== $question['answer_1'] && '' !== $question['answer_1']) {
                if (\is_string($question['answer_1']) && 150 < mb_strlen($question['answer_1'])) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_1_TOO_LONG, 'value' => $index + 1];
                } elseif ($question['answer_1'] instanceof \DateTimeInterface) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_1_TYPE, 'value' => $index + 1];
                }
            }

            if (null !== $question['answer_2'] && '' !== $question['answer_2']) {
                if (\is_string($question['answer_2']) && 150 < mb_strlen($question['answer_2'])) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_2_TOO_LONG, 'value' => $index + 1];
                } elseif ($question['answer_2'] instanceof \DateTimeInterface) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_2_TYPE, 'value' => $index + 1];
                }
            }

            $hasQuestion3 = isset($question['answer_3']) && null !== $question['answer_3'] && '' !== $question['answer_3'];

            if ($hasQuestion3) {
                if (\is_string($question['answer_3']) && 150 < mb_strlen($question['answer_3'])) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_3_TOO_LONG, 'value' => $index + 1];
                } elseif ($question['answer_3'] instanceof \DateTimeInterface) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_3_TYPE, 'value' => $index + 1];
                }
            }

            $hasQuestion4 = isset($question['answer_4']) && null !== $question['answer_4'] && '' !== $question['answer_4'];

            if ($hasQuestion4) {
                if (\is_string($question['answer_4']) && 150 < mb_strlen($question['answer_4'])) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_4_TOO_LONG, 'value' => $index + 1];
                } elseif ($question['answer_4'] instanceof \DateTimeInterface) {
                    $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_ANSWER_4_TYPE, 'value' => $index + 1];
                }
            }

            $answerCount = 2 + ($hasQuestion3 ? 1 : 0) + ($hasQuestion4 ? 1 : 0);

            if (empty($question['right_answer']) || !is_numeric($question['right_answer']) || $question['right_answer'] <= 0 || (int) $question['right_answer'] > $answerCount) {
                $errors[] = ['line' => $key, 'type' => QuizImportErrorType::INVALID_RIGHT_ANSWER, 'value' => $index + 1];
            }
        }

		assertType("list<array{line: int, type: 'empty_answer'|'empty_question'|'invalid_answer_1_too_long'|'invalid_answer_1_type'|'invalid_answer_2_too_long'|'invalid_answer_2_type'|'invalid_answer_3_too_long'|'invalid_answer_3_type'|'invalid_answer_4_too_long'|'invalid_answer_4_type'|'invalid_question_too_long'|'invalid_right_answer', value: int}>", $errors);

        return $errors;
    }
}

class QuizImportErrorType
{
    public const EMPTY_ANSWER = 'empty_answer';
    public const EMPTY_BONUS_DURATION = 'empty_bonus_duration';
    public const EMPTY_BONUS_POINTS = 'empty_bonus_points';
    public const EMPTY_COLLECTION = 'empty_collection';
    public const EMPTY_INTRODUCTION = 'empty_introduction';
    public const EMPTY_QUESTION = 'empty_question';
    public const EMPTY_TITLE = 'empty_title';
    public const INVALID_ANSWER_1_TOO_LONG = 'invalid_answer_1_too_long';
    public const INVALID_ANSWER_2_TOO_LONG = 'invalid_answer_2_too_long';
    public const INVALID_ANSWER_3_TOO_LONG = 'invalid_answer_3_too_long';
    public const INVALID_ANSWER_4_TOO_LONG = 'invalid_answer_4_too_long';
    public const INVALID_ANSWER_1_TYPE = 'invalid_answer_1_type';
    public const INVALID_ANSWER_2_TYPE = 'invalid_answer_2_type';
    public const INVALID_ANSWER_3_TYPE = 'invalid_answer_3_type';
    public const INVALID_ANSWER_4_TYPE = 'invalid_answer_4_type';
    public const INVALID_AUTHOR = 'invalid_author';
    public const INVALID_BONUS_DURATION = 'invalid_bonus_duration';
    public const INVALID_BONUS_POINTS = 'invalid_bonus_points';
    public const INVALID_CLOSING_DATE = 'invalid_closing_date';
    public const INVALID_COLLECTION = 'invalid_collection';
    public const INVALID_NEWS_FEED = 'invalid_news_feed';
    public const INVALID_PARTICIPATION_POINTS = 'invalid_participation_points';
    public const INVALID_PERFECT_POINTS = 'invalid_perfect_points';
    public const INVALID_PUBLICATION_DATE = 'invalid_publication_date';
    public const INVALID_QUESTION_TOO_LONG = 'invalid_question_too_long';
    public const INVALID_RESPONSE_TIME = 'invalid_response_time';
    public const INVALID_RIGHT_ANSWER = 'invalid_right_answer';
    public const INVALID_RIGHT_ANSWER_POINTS = 'invalid_right_answer_points';
    public const INVALID_TITLE_TOO_LONG = 'invalid_title_too_long';
    public const INVALID_WRONG_ANSWER_POINTS = 'invalid_wrong_answer_points';
}
