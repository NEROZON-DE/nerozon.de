<?php
declare(strict_types=1);
namespace Nerozon\Questionnaire;
use Nerozon\Database\{DatabaseConnector,DatabaseException};
interface QuestionnaireRepository{public function save(QuestionnaireSubmission $submission):void;}
final class DatabaseQuestionnaireRepository implements QuestionnaireRepository{public function __construct(private DatabaseConnector $db){}public function save(QuestionnaireSubmission $s):void{try{$payload=$this->db->encodeJson($s->toArray());$this->db->transaction(function()use($s,$payload):void{$this->db->execute('INSERT INTO questionnaire_submissions (submission_id, questionnaire_id, questionnaire_version, schema_version, submitted_at, payload_json) VALUES (:submission_id,:questionnaire_id,:questionnaire_version,:schema_version,:submitted_at,:payload_json)',['submission_id'=>$s->submissionId,'questionnaire_id'=>$s->questionnaireId,'questionnaire_version'=>$s->questionnaireVersion,'schema_version'=>$s->schemaVersion,'submitted_at'=>$s->submittedAt->format('Y-m-d H:i:s.u'),'payload_json'=>$payload]);});}catch(DatabaseException $e){throw new QuestionnairePersistenceFailed('Questionnaire persistence failed',0,$e);}}}
