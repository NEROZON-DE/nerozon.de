<?php
declare(strict_types=1);
namespace Nerozon\Tests;
use Nerozon\Questionnaire\{QuestionnaireRepository,QuestionnaireSubmission};
final class MemoryRepository implements QuestionnaireRepository{public array $saved=[];public function save(QuestionnaireSubmission $submission):void{$this->saved[]=$submission;}}
