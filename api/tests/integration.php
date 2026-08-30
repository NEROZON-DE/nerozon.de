<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Nerozon\Database\PdoDatabaseConnector;use Nerozon\Questionnaire\{DatabaseQuestionnaireRepository,SubmitQuestionnaire};use Nerozon\Http\HttpConnector;
foreach(['TEST_DB_HOST','TEST_DB_NAME','TEST_DB_USER','TEST_DB_PASSWORD'] as $k){if(getenv($k)===false){fwrite(STDERR,"SKIP integration: $k missing\n");exit(77);}}
$db=new PdoDatabaseConnector(['host'=>getenv('TEST_DB_HOST'),'port'=>(int)(getenv('TEST_DB_PORT')?:3306),'database'=>getenv('TEST_DB_NAME'),'username'=>getenv('TEST_DB_USER'),'password'=>getenv('TEST_DB_PASSWORD')]);$http=new HttpConnector(new SubmitQuestionnaire(new DatabaseQuestionnaireRepository($db)));[$status]=$http->dispatch('POST','/v1/questionnaire/submissions','application/json',json_encode(['questionnaire_id'=>'S092026','questionnaire_version'=>'20260830','answers'=>[['question_id'=>'S092026Q20','type'=>'short_text','value'=>'Integration']]]));if($status!==201){fwrite(STDERR,"FAIL integration HTTP status $status\n");exit(1);}echo "PASS integration HTTP -> business -> repository -> DB\n";
