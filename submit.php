<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function respond(int $code,array $v):void{http_response_code($code);echo json_encode($v);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')respond(405,['ok'=>false,'error'=>'Method not allowed']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>16000)respond(413,['ok'=>false,'error'=>'Submission is too large']);
$body=file_get_contents('php://input',false,null,0,16001);if($body===false||strlen($body)>16000)respond(413,['ok'=>false,'error'=>'Submission is too large']);
try{$r=json_decode($body,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){respond(400,['ok'=>false,'error'=>'Invalid submission']);}
if(!is_array($r))respond(400,['ok'=>false,'error'=>'Invalid submission']);
if(!empty($r['website']))respond(200,['ok'=>true,'overall'=>0,'level'=>'Thank you','dimensions'=>[]]);
function clean($v,int $max):string{$s=trim(is_string($v)?$v:'');return function_exists('mb_substr')?mb_substr($s,0,$max):substr($s,0,$max);}
$name=clean($r['name']??'',120);$company=clean($r['company']??'',120);$email=clean($r['email']??'',120);$role=clean($r['role']??'',120);$companyWebsite=clean($r['companyWebsite']??'',120);
if($name===''||$company===''||$role===''||!filter_var($email,FILTER_VALIDATE_EMAIL))respond(422,['ok'=>false,'error'=>'Name, company, designation / role and valid email are required']);
if(!preg_match('~^(https?://)?([a-z0-9-]+\.)+[a-z]{2,}(/\S*)?$~i',$companyWebsite))respond(422,['ok'=>false,'error'=>'A valid company website is required']);
$categories=['Culture'=>range(1,5),'Strategy'=>range(6,8),'Capabilities'=>range(9,14),'Organization & Talent'=>range(15,17)];$ratings=[];
foreach(range(1,17) as $n){$k=sprintf('q%02d',$n);if(!isset($r[$k])||!is_int($r[$k])||$r[$k]<1||$r[$k]>5)respond(422,['ok'=>false,'error'=>'All 17 ratings must be between 1 and 5']);$ratings[$k]=$r[$k];}
// Factor names per question; the priority dropdown in index.html offers exactly these (grouped by dimension).
$factors=['q01'=>['Culture','Risk Appetite'],'q02'=>['Culture','Test and Learn'],'q03'=>['Culture','Speed / Agility'],'q04'=>['Culture','Collaboration'],'q05'=>['Culture','External Orientation'],'q06'=>['Strategy','Linked to Business Strategy'],'q07'=>['Strategy','Bold, Long-term Orientation'],'q08'=>['Strategy','Centered Around Customer Needs'],'q09'=>['Capabilities','Connectivity'],'q10'=>['Capabilities','Automation'],'q11'=>['Capabilities','Content'],'q12'=>['Capabilities','Data / Analytics'],'q13'=>['Capabilities','Customer Experience'],'q14'=>['Capabilities','Technology'],'q15'=>['Organization & Talent','Partners'],'q16'=>['Organization & Talent','Processes'],'q17'=>['Organization & Talent','People']];
$pickPriority=function(string $raw)use($factors):string{foreach($factors as $f){if($f[1]===$raw)return $f[0].': '.$f[1];}return '';};
$priority=$pickPriority(clean($r['priorityArea']??'',100));$priority2=$pickPriority(clean($r['priorityArea2']??'',100));
if($priority===''||$priority2==='')respond(422,['ok'=>false,'error'=>'Please select a valid top and second-highest priority']);
if($priority===$priority2)respond(422,['ok'=>false,'error'=>'Second-highest priority must be different from the top priority']);
$outcome=clean($r['outcome']??'',1000);
if($outcome==='')respond(422,['ok'=>false,'error'=>'Please describe the business outcome you would most like to improve']);
$dimensions=[];foreach($categories as $category=>$ids){$vals=[];foreach($ids as $n)$vals[]=$ratings[sprintf('q%02d',$n)];$dimensions[$category]=(int)round((array_sum($vals)/count($vals)-1)*25);}
$overall=(int)round((array_sum($ratings)/17-1)*25);$level=$overall<25?'Exploring':($overall<50?'Emerging':($overall<75?'Progressing':'Advancing'));
// Prevent spreadsheet formula injection in downloaded CSV.
function csvsafe(string $v):string{return preg_match('/^[\s]*[=+\-@]/u',$v)?"'".$v:$v;}
$row=[gmdate('c'),bin2hex(random_bytes(8)),$name,$email,$company,$companyWebsite,$role];foreach($ratings as $v)$row[]=$v;
array_push($row,$priority,$priority2,$outcome,$overall,$level);foreach($dimensions as $v)$row[]=$v;
$path=__DIR__.'/data/responses.csv';$header=array_merge(['submitted_at_utc','response_id','name','email','company','company_website','role'],array_keys($ratings),['top_priority','second_highest_priority','desired_outcome','dq_score_100','readiness_level','culture_score','strategy_score','capabilities_score','organization_talent_score']);
$fp=@fopen($path,'c+');if(!$fp)respond(500,['ok'=>false,'error'=>'Storage is unavailable. Please contact the site administrator.']);
if(!flock($fp,LOCK_EX)){fclose($fp);respond(500,['ok'=>false,'error'=>'Could not save your response']);}
try{fseek($fp,0,SEEK_END);if(ftell($fp)===0)fputcsv($fp,$header);fputcsv($fp,array_map(fn($v)=>is_string($v)?csvsafe($v):$v,$row));fflush($fp);}finally{flock($fp,LOCK_UN);fclose($fp);}
// Email the PDF summary; a mail failure must never block the saved response.
$emailed=false;
try{
 $cfg=(require __DIR__.'/config.php')['smtp']??[];
 if(!empty($cfg['host'])){
  require_once __DIR__.'/mailer.php';
  $emailed=send_summary_email($cfg,['name'=>$name,'email'=>$email,'company'=>$company,'website'=>$companyWebsite,'role'=>$role,'overall'=>$overall,'level'=>$level,'dimensions'=>$dimensions,'ratings'=>$ratings,'factors'=>$factors,'priority'=>$priority,'priority2'=>$priority2,'outcome'=>$outcome,'date'=>gmdate('d-m-Y')]);
 }
}catch(Throwable $e){error_log('DQ survey mail failed: '.$e->getMessage());}
respond(200,['ok'=>true,'overall'=>$overall,'level'=>$level,'dimensions'=>$dimensions,'emailed'=>$emailed]);
