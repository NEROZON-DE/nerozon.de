<?php

declare(strict_types=1);
http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') exit;
$action=(string)($_GET['action']??''); $token=(string)($_GET['token']??'');
if(!in_array($action,['prepare','activate','confirm','rollback'],true)||!preg_match('/^dpl-[0-9]{13}-[a-f0-9]{32}$/',$token)) exit;
$base=dirname(__DIR__); $tokenFile=$base.'/'.$token; if(!is_file($tokenFile)) exit; $age=time()-(int)filemtime($tokenFile); if($age<0||$age>1200){@unlink($tokenFile);exit;}
$live=$base.'/factory'; $staging=$base.'/factory-deploy'; $rollback=$base.'/factory-rollback';
function factory_remove_tree(string $path): bool { if(!file_exists($path))return true; if(is_link($path)||is_file($path))return unlink($path); $items=scandir($path); if($items===false)return false; foreach($items as $item){if($item==='.'||$item==='..')continue;if(!factory_remove_tree($path.DIRECTORY_SEPARATOR.$item))return false;} return rmdir($path); }
if($action==='prepare'){if(!factory_remove_tree($staging)||!mkdir($staging,0755)){http_response_code(500);exit("prepare failed\n");}http_response_code(200);exit("prepare ok\n");}
if($action==='activate'){ $items=is_dir($staging)?array_diff(scandir($staging)?:[],['.','..']):[]; if(!$items||!factory_remove_tree($rollback)){http_response_code(500);exit("activate failed\n");} if(is_dir($live)&&!rename($live,$rollback)){http_response_code(500);exit("activate failed\n");} if(!rename($staging,$live)){if(is_dir($rollback)&&!is_dir($live))@rename($rollback,$live);http_response_code(500);exit("activate failed\n");} http_response_code(200);exit("activate ok\n"); }
if($action==='rollback'){ $ok=factory_remove_tree($live); if($ok&&is_dir($rollback))$ok=rename($rollback,$live); @factory_remove_tree($staging); @unlink($tokenFile); if(!$ok){http_response_code(500);exit("rollback failed\n");}http_response_code(200);exit("rollback ok\n"); }
@factory_remove_tree($staging); if(!unlink($tokenFile)){http_response_code(500);exit("confirm failed\n");}http_response_code(200);exit("confirm ok\n");
