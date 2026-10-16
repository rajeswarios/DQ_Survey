<?php
declare(strict_types=1);
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Strict','use_strict_mode'=>true,'cookie_secure'=>!empty($_SERVER['HTTPS'])]);
header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');header('X-Frame-Options: DENY');
$config=require __DIR__.'/config.php';
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
// Stored timestamps are ISO 8601; display as DD-MM-YYYY HH:MM.
function fmt_date(string $iso):string{$t=strtotime($iso);return $t===false?$iso:gmdate('d-m-Y H:i',$t);}

// Accepts a plaintext password or a password_hash() value in config.php.
function password_ok(string $stored,string $given):bool{
    if($stored==='')return false;
    if(preg_match('/^\$2[ayb]\$|^\$argon2/',$stored))return password_verify($given,$stored);
    return hash_equals($stored,$given);
}

if(isset($_POST['logout'])){$_SESSION=[];session_destroy();header('Location: admin.php');exit;}

// Failed-login lockout is tracked per client IP on disk, so dropping the session cookie does not reset it.
function lock_file():string{return __DIR__.'/data/login_attempts.json';}
function lock_update(string $ip,?string $action):int{
    $fp=@fopen(lock_file(),'c+');if(!$fp)return 0;
    flock($fp,LOCK_EX);$d=json_decode((string)stream_get_contents($fp),true);if(!is_array($d))$d=[];
    $now=time();foreach($d as $k=>$v){if(($v['lock_until']??0)<$now&&($v['last']??0)<$now-3600)unset($d[$k]);}
    $e=$d[$ip]??['tries'=>0,'lock_until'=>0,'last'=>0];
    if($action==='fail'){$e['last']=$now;if(++$e['tries']>=5){$e['tries']=0;$e['lock_until']=$now+60;}}
    elseif($action==='ok'){unset($d[$ip]);$e=null;}
    if($e!==null)$d[$ip]=$e;
    if($action!==null){ftruncate($fp,0);rewind($fp);fwrite($fp,json_encode($d));fflush($fp);}
    flock($fp,LOCK_UN);fclose($fp);
    return max(0,(int)($e['lock_until']??0)-$now);
}
$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
$error='';
if(isset($_POST['password'])){
    if(lock_update($ip,null)>0){
        $error='Too many attempts. Please wait a minute and try again.';
    }elseif(password_ok((string)$config['admin_password'],(string)$_POST['password'])){
        lock_update($ip,'ok');
        session_regenerate_id(true);$_SESSION=['survey_admin'=>true];
        header('Location: admin.php');exit;
    }else{
        usleep(400000);
        lock_update($ip,'fail');
        $error='Incorrect password.';
    }
}

function page_open(string $title):void{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>'.e($title).'</title><style>
:root{--ink:#153b57;--navy:#123e59;--turq:#04aebe;--orange:#e96b13;--bg:#f3f7fa;--muted:#607184;--border:#dce6ee}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);font:15px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;color:#1e3445}
header{background:var(--navy);color:#fff;padding:clamp(10px,2vw,18px) max(16px,calc((100vw - 1100px)/2));display:flex;align-items:center;justify-content:space-between;gap:12px}
header strong{font-family:Georgia,serif;font-size:clamp(20px,3.2vw,26px);font-weight:500;letter-spacing:1px;white-space:nowrap}
.hright{display:flex;align-items:center;gap:12px;margin-left:auto;min-width:0}
.hright span{font-size:clamp(11px,1.6vw,13px);line-height:1.3;color:#c4edf0;text-align:right}
.hright b{white-space:nowrap}
.logo{height:clamp(30px,5vw,40px);width:auto;background:#fff;padding:4px 8px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.25);flex:none}
.wrap{max-width:1100px;margin:clamp(14px,3vw,28px) auto 48px;padding:0 clamp(12px,3vw,20px)}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:0 6px 26px #102a4010;padding:clamp(16px,3.5vw,28px)}
h1{font-size:clamp(21px,3.6vw,28px);line-height:1.2;color:var(--ink);margin:0 0 6px}
h2{font-size:18px;color:var(--ink);margin:0 0 12px}
.eyebrow{font-size:12px;text-transform:uppercase;color:var(--orange);font-weight:800;letter-spacing:1.5px}
.muted{color:var(--muted)}
label{display:block;font-weight:650;margin-bottom:6px}
input[type=password]{width:100%;border:1px solid #bbcdd8;border-radius:9px;padding:10px 12px;min-height:44px;font:inherit;font-size:16px}
.btn{display:inline-block;border:0;padding:10px 20px;min-height:44px;border-radius:9px;background:var(--navy);color:#fff;cursor:pointer;font:inherit;font-weight:700;text-decoration:none;line-height:24px}
.btn.secondary{background:#e7eff4;color:var(--ink)}
.btn.block{width:100%}
.error{color:#b0251d;font-weight:650;margin:12px 0 0}
.login{max-width:420px;margin:clamp(24px,10vh,90px) auto}
.bar{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:18px}
.actions{display:flex;gap:10px;flex-wrap:wrap}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:18px}
.stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:16px}
.stat small{display:block;color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.6px;font-weight:700}
.stat b{display:block;font-size:26px;color:var(--ink);line-height:1.2;margin-top:4px}
table{width:100%;border-collapse:collapse;font-size:14px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
th{background:#f1f6f9;color:var(--ink);font-size:12px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
.pill{display:inline-block;background:#e6f8f8;color:#026a71;padding:2px 10px;border-radius:20px;font-weight:700;font-size:12px;white-space:nowrap}
@media(max-width:700px){
header{flex-direction:column;align-items:center;text-align:center;gap:8px}
.hright{flex-direction:column;margin:0}
.hright span{text-align:center}
.bar{flex-direction:column}
.actions{width:100%}
.actions .btn,.actions form{flex:1}
.actions form .btn{width:100%}
table,thead,tbody,tr,td{display:block}
thead{display:none}
tr{border:1px solid var(--border);border-radius:10px;margin-bottom:10px;padding:6px 0}
td{border:0;padding:4px 12px;display:flex;gap:10px;justify-content:space-between}
td::before{content:attr(data-label);font-weight:700;color:var(--muted);flex:none}
td>*{text-align:right}
}
</style></head><body><header><strong>JAYANTHRA</strong><div class="hright"><span>Practical Road-map for <b>Digital&nbsp;Transformation</b></span><img class="logo" src="logo.png" alt="Logo"></div></header><main class="wrap">';
}
function page_close():void{echo '</main></body></html>';}

if(!($_SESSION['survey_admin']??false)){
    page_open('Survey administration');
    echo '<div class="card login"><div class="eyebrow">Administration</div><h1>Sign in</h1><p class="muted">Enter the admin password to view survey responses.</p>'
        .'<form method="post" autocomplete="off"><label for="password">Admin password</label><input id="password" type="password" name="password" required autofocus autocomplete="current-password">'
        .'<p style="margin:16px 0 0"><button class="btn block" type="submit">Sign in</button></p>'
        .($error!==''?'<p class="error" role="alert">'.e($error).'</p>':'').'</form></div>';
    page_close();exit;
}

$file=__DIR__.'/data/responses.csv';
if(isset($_GET['download'])){
    if(!is_file($file)){
        http_response_code(404);
        page_open('File not found');
        echo '<div class="card login"><div class="eyebrow">Download unavailable</div><h1>File not found</h1>'
            .'<p class="muted">The responses file (<code>data/responses.csv</code>) does not exist yet. It is created automatically when the first survey response is submitted. Please check again later.</p>'
            .'<p style="margin:16px 0 0"><a class="btn block" href="admin.php">Back to dashboard</a></p></div>';
        page_close();exit;
    }
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="dq-survey-responses.csv"');readfile($file);exit;
}

// Read responses (header row first) for the summary and recent-submissions table.
$rows=[];$head=[];
if(is_file($file)&&($f=fopen($file,'r'))!==false){
    $head=fgetcsv($f)?:[];
    while(($r=fgetcsv($f))!==false){if($head&&count($r)===count($head))$rows[]=array_combine($head,$r);}
    fclose($f);
}
$count=count($rows);
$scores=array_map(fn($r)=>(int)($r['dq_score_100']??0),$rows);
$avg=$count?(int)round(array_sum($scores)/$count):null;
$latest=$count?$rows[$count-1]['submitted_at_utc']:null;
$recent=array_slice(array_reverse($rows),0,15);

page_open('Survey administration');
echo '<div class="bar"><div><div class="eyebrow">Administration</div><h1>DQ survey responses</h1><div class="muted">Digital Transformation Readiness Assessment</div></div>'
    .'<div class="actions"><a class="btn" href="admin.php?download=1">Download CSV</a><form method="post" style="margin:0"><button class="btn secondary" name="logout" value="1">Sign out</button></form></div></div>';
echo '<div class="stats"><div class="stat"><small>Total submissions</small><b>'.$count.'</b></div>'
    .'<div class="stat"><small>Average DQ score</small><b>'.($avg===null?'–':$avg.' / 100').'</b></div>'
    .'<div class="stat"><small>Latest submission</small><b style="font-size:16px;margin-top:10px">'.($latest?e(fmt_date($latest)):'–').'</b></div></div>';
echo '<div class="card"><h2>Recent submissions</h2>';
if(!$recent){echo '<p class="muted">No responses yet.</p>';}
else{
    echo '<table><thead><tr><th>Date</th><th>Name</th><th>Organization</th><th>Role</th><th>Score</th><th>Level</th></tr></thead><tbody>';
    foreach($recent as $r){
        echo '<tr><td data-label="Date">'.e(fmt_date($r['submitted_at_utc']??'')).'</td>'
            .'<td data-label="Name">'.e($r['name']??'').'</td>'
            .'<td data-label="Organization">'.e($r['company']??'').'</td>'
            .'<td data-label="Role">'.e($r['role']??'').'</td>'
            .'<td data-label="Score"><b>'.e($r['dq_score_100']??'').'</b></td>'
            .'<td data-label="Level"><span class="pill">'.e($r['readiness_level']??'').'</span></td></tr>';
    }
    echo '</tbody></table><p class="muted" style="margin:12px 0 0">Showing the latest '.count($recent).' of '.$count.'. Download the CSV for all responses and ratings.</p>';
}
echo '</div>';
page_close();
