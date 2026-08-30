<?php
declare(strict_types=1);
$files=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/src',FilesystemIterator::SKIP_DOTS));foreach($it as $f){if($f->isFile()&&$f->getExtension()==='php')$files[]=$f->getPathname();}sort($files);foreach($files as $file)require_once $file;
