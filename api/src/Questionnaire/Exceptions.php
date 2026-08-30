<?php
declare(strict_types=1);
namespace Nerozon\Questionnaire;
class QuestionnaireException extends \RuntimeException {}
class UnsupportedQuestionnaire extends QuestionnaireException {}
class UnsupportedQuestionnaireVersion extends QuestionnaireException {}
class InvalidQuestionnaireSubmission extends QuestionnaireException {}
class QuestionnairePersistenceFailed extends QuestionnaireException {}
