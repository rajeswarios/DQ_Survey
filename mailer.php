<?php
declare(strict_types=1);
// Builds a PDF summary of a submission (no external PDF library) and emails it with PHPMailer.
// SMTP settings live in config.php under 'smtp'.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/phpmailer/Exception.php';
require_once __DIR__.'/phpmailer/PHPMailer.php';
require_once __DIR__.'/phpmailer/SMTP.php';

/** Minimal multi-page PDF writer: Helvetica text and filled rectangles on A4. */
final class SimplePdf {
    private array $pages=[];private string $buf='';
    public const W=595,H=842;
    public function __construct(){$this->addPage();}
    public function addPage():void{if($this->buf!=='')$this->pages[]=$this->buf;$this->buf='';}
    private static function enc(string $s):string{
        $t=function_exists('iconv')?@iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s):false;
        if($t===false||$t===null)$t=preg_replace('/[^\x20-\x7E]/','?',$s);
        return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)','',' '],$t);
    }
    private static function col(array $c):string{return sprintf('%.3F %.3F %.3F',$c[0]/255,$c[1]/255,$c[2]/255);}
    /** $y is measured from the top of the page. */
    public function text(float $x,float $y,string $s,float $size=10,bool $bold=false,array $rgb=[30,40,50]):void{
        $this->buf.=sprintf("BT %s rg /%s %.1F Tf %.1F %.1F Td (%s) Tj ET\n",self::col($rgb),$bold?'F2':'F1',$size,$x,self::H-$y,self::enc($s));
    }
    public function rect(float $x,float $y,float $w,float $h,array $rgb):void{
        $this->buf.=sprintf("%s rg %.1F %.1F %.1F %.1F re f\n",self::col($rgb),$x,self::H-$y-$h,$w,$h);
    }
    public function output():string{
        $pages=$this->pages;$pages[]=$this->buf;
        $objs=[1=>'<< /Type /Catalog /Pages 2 0 R >>',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $kids=[];$n=5;
        foreach($pages as $content){
            $c=$n++;$p=$n++;
            $objs[$c]="<< /Length ".strlen($content)." >>\nstream\n".$content."endstream";
            $objs[$p]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ".self::W." ".self::H."] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $c 0 R >>";
            $kids[]="$p 0 R";
        }
        $objs[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
        ksort($objs);
        $out="%PDF-1.4\n";$off=[];
        foreach($objs as $i=>$o){$off[$i]=strlen($out);$out.="$i 0 obj\n$o\nendobj\n";}
        $xref=strlen($out);$max=max(array_keys($objs));
        $out.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";
        for($i=1;$i<=$max;$i++)$out.=sprintf("%010d 00000 n \n",$off[$i]);
        $out.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $out;
    }
}

/** Builds the summary PDF from the data array assembled in submit.php. */
function build_summary_pdf(array $d):string{
    $navy=[11,37,69];$teal=[0,128,140];$grey=[96,112,125];$ink=[30,40,50];
    $pdf=new SimplePdf();$left=48;$right=547;$y=0;
    $header=function() use($pdf,$navy,$d){
        $pdf->rect(0,0,SimplePdf::W,78,$navy);
        $pdf->text(48,36,'Digital Transformation Readiness Assessment',17,true,[255,255,255]);
        $pdf->text(48,58,'DQ Readiness Summary  |  '.$d['date'],10,false,[196,237,240]);
    };
    $header();$y=108;
    // Moves to a new page when fewer than $need points remain.
    $room=function(float $need) use($pdf,&$y,$header){if($y+$need>800){$pdf->addPage();$header();$y=108;}};
    $wrap=function(string $s,int $max):array{return explode("\n",wordwrap(str_replace(["\r\n","\r"],"\n",$s),$max,"\n",true));};
    $heading=function(string $s) use($pdf,&$y,$room,$teal,$left,$right){$room(40);$pdf->text($left,$y,$s,13,true,$teal);$y+=7;$pdf->rect($left,$y,$right-$left,1,$teal);$y+=18;};

    $heading('Respondent');
    foreach([['Name',$d['name']],['Designation / role',$d['role']],['Organization',$d['company']],['Website',$d['website']],['Email',$d['email']]] as [$k,$v]){
        $pdf->text($left,$y,$k,10,true,$grey);$pdf->text($left+130,$y,(string)$v,10,false,$ink);$y+=16;
    }
    $y+=10;

    $heading('Overall DQ score');
    $pdf->text($left,$y+14,(string)$d['overall'],34,true,$navy);
    $pdf->text($left+70,$y+14,'/ 100',12,false,$grey);
    $pdf->text($left+140,$y+14,'Readiness level: '.$d['level'],13,true,$teal);
    $y+=46;
    $pdf->text($left,$y,'Indicative self-assessment score, not an externally validated industry benchmark.',9,false,$grey);$y+=26;

    $heading('Dimension scores');
    foreach($d['dimensions'] as $name=>$score){
        $room(26);
        $pdf->text($left,$y,$name,10,true,$ink);
        $pdf->rect(250,$y-9,240,10,[225,232,238]);
        $pdf->rect(250,$y-9,2.4*max(0,min(100,$score)),10,$teal);
        $pdf->text(500,$y,$score.'/100',10,true,$ink);
        $y+=22;
    }
    $y+=10;

    $heading('Factor ratings (1 = Not started, 5 = Highly mature)');
    $lastCat='';
    foreach($d['ratings'] as $qid=>$rating){
        [$cat,$factor]=$d['factors'][$qid];
        if($cat!==$lastCat){$room(34);$y+=4;$pdf->text($left,$y,$cat,10,true,$navy);$y+=15;$lastCat=$cat;}
        $room(16);
        $pdf->text($left+12,$y,$factor,10,false,$ink);
        $pdf->text(380,$y,$rating.' / 5',10,true,$ink);
        $y+=15;
    }
    $y+=14;

    $heading('Your priorities');
    foreach([['1. Top priority',$d['priority']],['2. Second-highest priority',$d['priority2']],['3. Desired business outcome (next 12 months)',$d['outcome']]] as [$q,$a]){
        $room(50);
        $pdf->text($left,$y,$q,10,true,$grey);$y+=15;
        foreach($wrap($a,95) as $line){$room(15);$pdf->text($left,$y,$line,10,false,$ink);$y+=14;}
        $y+=10;
    }
    return $pdf->output();
}

/** Sends the summary PDF to the respondent (and the optional admin copy). Returns true on success. */
function send_summary_email(array $cfg,array $d):bool{
    $mail=new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host=(string)$cfg['host'];
    $mail->Port=(int)($cfg['port']??587);
    $mail->SMTPAuth=($cfg['username']??'')!=='';
    $mail->Username=(string)($cfg['username']??'');
    $mail->Password=(string)($cfg['password']??'');
    $enc=strtolower((string)($cfg['encryption']??'tls'));
    if($enc==='ssl')$mail->SMTPSecure=PHPMailer::ENCRYPTION_SMTPS;
    elseif($enc==='tls')$mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;
    else{$mail->SMTPSecure='';$mail->SMTPAutoTLS=false;}
    $mail->CharSet='UTF-8';
    $mail->Timeout=15;
    $from=(string)($cfg['from_email']??'')?:(string)$cfg['username'];
    $mail->setFrom($from,(string)($cfg['from_name']??'DQ Readiness Assessment'));
    $mail->addAddress($d['email'],$d['name']);
    if(!empty($cfg['admin_copy']))$mail->addBCC((string)$cfg['admin_copy']);
    $mail->Subject='Your Digital Transformation Readiness Summary';
    $first=explode(' ',trim($d['name']))[0];
    $mail->isHTML(true);
    $mail->Body='<p>Dear '.htmlspecialchars($first,ENT_QUOTES,'UTF-8').',</p>'
        .'<p>Thank you for completing the Digital Transformation Readiness Assessment. Your overall DQ score is <strong>'.(int)$d['overall'].'/100</strong> ('.htmlspecialchars($d['level'],ENT_QUOTES,'UTF-8').').</p>'
        .'<p>Your full summary is attached as a PDF.</p><p>Regards,<br>'.htmlspecialchars((string)($cfg['from_name']??''),ENT_QUOTES,'UTF-8').'</p>';
    $mail->AltBody="Dear $first,\n\nThank you for completing the Digital Transformation Readiness Assessment. Your overall DQ score is {$d['overall']}/100 ({$d['level']}).\nYour full summary is attached as a PDF.";
    $mail->addStringAttachment(build_summary_pdf($d),'DQ-Readiness-Summary.pdf','base64','application/pdf');
    return $mail->send();
}
