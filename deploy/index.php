<?php

declare(strict_types=1);
http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') exit;
$base=dirname(__DIR__); $action=(string)($_GET['action']??''); $component=(string)($_GET['component']??''); $token=(string)($_GET['token']??'');
if(!in_array($action,['prepare','deploy','verify','confirm','rollback'],true)) exit;
if(!in_array($component,['www','api','dispatcher','factory'],true)) exit;
if($component!=='factory'&&in_array($action,['verify','confirm','rollback'],true)) exit;
if(!preg_match('/^dpl-[0-9]{13}-[a-f0-9]{32}$/',$token)) exit;
$tokenFile=$base.'/'.$token; if(!is_file($tokenFile)) exit; $age=time()-filemtime($tokenFile); if($age<0||$age>1200){@unlink($tokenFile);exit;}
$live=$base.'/'.$component; $staging=$base.'/'.$component.'-deploy'; $rollback=$base.'/'.$component.'-rollback';
function removeTree(string $path): bool { if(!file_exists($path))return true; if(is_link($path)||is_file($path))return unlink($path); $items=scandir($path); if($items===false)return false; foreach($items as $item){if($item==='.'||$item==='..')continue; if(!removeTree($path.DIRECTORY_SEPARATOR.$item))return false;} return rmdir($path); }
if($action==='prepare'){if(!removeTree($staging)||!mkdir($staging,0755)){http_response_code(500);exit("Prepare failed.\n");}http_response_code(200);exit("NEROZON {$component} prepare successful\n");}
if($action==='verify'){
    $expectedEnvironment=strtoupper(trim((string)($_GET['expected_environment']??'')));
    $expectedRevision=trim((string)($_GET['expected_revision']??''));
    if(!in_array($expectedEnvironment,['DEV1','DEV2','DEV3'],true)||!preg_match('/^[a-f0-9]{40}$/',$expectedRevision)){http_response_code(400);exit("Verification input invalid.\n");}
    $bootstrap=$live.'/src/bootstrap.php';
    if(!is_file($bootstrap)||!is_readable($bootstrap)){http_response_code(500);exit("FACTORY verification failed.\n");}
    try{
        require_once $bootstrap;
        if(!function_exists('factory_bootstrap_state')) throw new RuntimeException('Bootstrap unavailable.');
        $state=factory_bootstrap_state();
        if(($state['environment']??null)!==$expectedEnvironment||($state['revision']??null)!==$expectedRevision) throw new RuntimeException('Bootstrap mismatch.');
    }catch(Throwable $e){http_response_code(500);exit("FACTORY verification failed.\n");}
    http_response_code(200);exit("FACTORY application verification successful\n");
}
if($action==='confirm'){if(!removeTree($rollback)||!unlink($tokenFile)){http_response_code(500);exit("Confirmation failed.\n");}http_response_code(200);exit("NEROZON factory deployment confirmed\n");}
if($action==='rollback'){
    if(is_dir($rollback)){
        if(is_dir($live)&&!removeTree($live)){http_response_code(500);exit("Rollback failed.\n");}
        if(!rename($rollback,$live)){http_response_code(500);exit("Rollback failed.\n");}
        $message="NEROZON factory rollback restored previous live\n";
    } else {
        if(is_dir($live)&&!removeTree($live)){http_response_code(500);exit("Rollback failed.\n");}
        $message="NEROZON factory rollback removed failed initial live\n";
    }
    @removeTree($staging); if(!unlink($tokenFile)){http_response_code(500);exit("Rollback token cleanup failed.\n");}
    http_response_code(200); exit($message);
}
if(!is_dir($staging)||count(array_diff(scandir($staging)?:[],['.','..']))===0){http_response_code(500);exit("Deployment failed: staging unavailable.\n");}
if(!removeTree($rollback)){http_response_code(500);exit("Deployment failed: rollback cleanup.\n");}
if(is_dir($live)&&!rename($live,$rollback)){http_response_code(500);exit("Deployment failed: live preservation.\n");}
if(!rename($staging,$live)){if(is_dir($rollback)&&!is_dir($live))@rename($rollback,$live);http_response_code(500);exit("Deployment failed: activation.\n");}
if($component!=='factory'&&!unlink($tokenFile)){http_response_code(500);exit("Deployment failed: token cleanup.\n");}
http_response_code(200); echo "NEROZON {$component} deployment successful\n";
