<?php
$Fpdf_loc="/usr/share/php/fpdf.php";
function debug($cmd,$output){
	$here=posix_getpwuid(posix_geteuid());
	$here=$here['name'].'@'.$_SERVER['SERVER_NAME'].':'.getcwd();
	return "$here\$ $cmd\n$output";
}
function ext2mime($ext){
	switch($ext){
		case "png": return "image/png";
		case "jpg": return "image/jpg";
		case "tiff": return "image/tiff";
		case "txt": return "text/plain";
		case "pdf": return "application/pdf";
		case "bz2": return "application/x-bzip";
		case "lzma": return "application/x-tar";
		case "zip": return "application/zip";
	}
}
function returnFile($in,$out,$ext){
	header("Pragma: public");
	header("Content-type: ".ext2mime($ext));
	header('Content-Disposition: '.($ext=='pdf'?'inline':'attachment').'; filename="'.addslashes($out).'"');
	if(is_file($in)){
		header('Content-Length: '.filesize($in));
		readfile($in);
	}
	else{
		header('Content-Length: '.strlen($in));
		echo $in;
	}
}
if(isset($_GET['file'])){
	if(strpos($_GET['file'], "/")>-1)
		$_GET['file']=substr($_GET['file'],strrpos($_GET['file'],"/")+1);
}
if(isset($_GET['downloadServer'])){
	$file="/tmp/scanner-".md5(time().rand()).".tar.lzma";
	$cmd="tar cfa '$file' ".'--exclude="scans/*" --exclude="config/*.*" --exclude="config/parallel/*" ./';
	$output=shell_exec("$cmd 2>&1");
	if(is_file($file)){
		returnFile($file,'PHP-Scanner-Server-'.$_GET['ver'].'.tar.lzma','lzma');
		@unlink($file);
	}
	else
		returnFile(debug($cmd,$output),'Error.txt','txt');
}
else if((isset($_GET['type'])?$_GET['type']:'')=='pdf'&&!isset($_GET['raw'])){
	if(!is_file($Fpdf_loc))
		die(returnFile("I have no idea where fpdf is installed to, I just know it is not at '$Fpdf_loc'\nEdit Line 2 of '".$_SERVER["SCRIPT_FILENAME"].
			"' with the correct info\nTry running this command to find it:\nlocate fpdf.php",'Error.txt','txt'));
	$Pwidth=215.9;// Letter Paper Width in millimeters
	$Pheight=279.4;// Letter Paper Height in millimeters
	if(isset($_GET['size'])){
		$size=explode('-',$_GET['size']);
		$Pwidth=is_numeric($size[0])?$size[0]:$Pwidth;
		$Pheight=is_numeric($size[1])?$size[1]:$Pheight;
	}
	$fontSize=16;
	$width=$Pwidth;
	$height=$Pheight;
	require($Fpdf_loc);
	$pdf=new FPDF('P','mm',array($width,$height));
	$full=isset($_GET['full']);
	$marginLeft=$full?0:$width/21.59;
	$marginTop=$full?0:$height/13.97;
	$pdf->SetLeftMargin($marginLeft);
	$pdf->SetRightMargin($marginLeft);
	$pdf->SetTopMargin($marginTop/2);
	$pdf->SetAutoPageBreak(true, $marginTop);
	$pages=0;
	$files=json_decode($_GET['json']);
	if($files==null)
		$files=array();
	foreach($files as $key => $val){
		$file=$key;
		if(is_numeric(strpos($file, "/")))
			$file=substr($file,strrpos($file,"/")+1);
		$file="Scan_$file";
		if(!is_file("scans/$file"))
			continue;
		$ext=substr($file,strrpos($file,'.')+1);
		$width=$Pwidth;
		$height=$Pheight;
		$pdf->AddPage();
		$pages+=1;
		if($full){
			if($ext=='txt'){
				$pdf->SetFont('Arial','',$fontSize*0.75*($width/215.9));
				$pdf->MultiCell(0,5,file_get_contents("scans/$file"),0,"L",false);
			}
			else{
				$image=explode("x",shell_exec("identify -format '%wx%h' ".escapeshellarg("scans/$file")));
				if($height/$width<=$image[1]/$image[0])
					$width=0;
				else
					$height=0;
				$pdf->Image("scans/$file",$marginLeft,$marginTop,$width,$height);
			}
		}
		else{
			$pdf->SetFont('Arial','B',$fontSize*($width/215.9));
			$pdf->MultiCell(0,$fontSize*($width/215.9),$file,0,"C",false);
			if($ext=='txt'){
				$pdf->SetFont('Arial','',$fontSize*0.75*($width/215.9));
				$pdf->MultiCell(0,5*($width/215.9),file_get_contents("scans/$file"),0,"L",false);
			}
			else{
				$image=explode("x",shell_exec("identify -format '%wx%h' ".escapeshellarg("scans/$file")));
				$width=$width-($marginLeft*2);
				$height=$height-$marginTop*2-$fontSize*0.75*($Pwidth/215.9)/2;
				if($height/$width<=$image[1]/$image[0])
					$width=0;
				else
					$height=0;
				$pdf->Image('scans/'.$file,$marginLeft,$marginTop/2+$fontSize*($Pwidth/215.9),$width,$height);
			}
		}
	}
	if($pages>0){
		$file=$pages>1?"Compilation.pdf":substr($file,0,strlen($ext)*-1)."pdf";
		$pdf->Output($file,'I');
	}
	else{
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',$fontSize);
		$pdf->MultiCell(0,$fontSize,'None of thoes files exist :/',0,"C",false);
		$pdf->Output("Error.pdf",'I');
	}
}
else if(isset($_GET['json'])){
	$files=json_decode($_GET['json']);
	if($files==null)
		$files=array();
	$FILES='';
	$type=isset($_GET['type'])?$_GET['type']:false;
	$ct=0;
	foreach($files as $key => $val){
		$file=$key;
		if(is_numeric(strpos($file, "/")))
			$file=substr($file,strrpos($file,"/")+1);
		$file="Scan_$file";
		if(is_file("scans/$file")){
			$FILES.=escapeshellarg("scans/$file").' ';
			$ct+=1;
		}
	}
	if(strlen($FILES)>0 && is_string($type)){
		$type=$_GET['type'];
		if($type=='pdf'){
			$name=$ct==1?substr($file,0,strrpos($file,'.')).'.pdf':'Compilation.pdf';
			$file='/tmp/'.md5(time().rand()).'.pdf';
			$type='pdf';
			$cmd="convert $FILES+repage '$file'";
			$output=shell_exec("$cmd 2>&1");// -page Letter -gravity center
		}
		else if($type=='zip'){
			$file='/tmp/'.md5(time().rand()).'.zip';
			$type='zip';
			$name='Compilation.zip';
			$cmd="zip '$file' $FILES";
			$output=shell_exec("$cmd 2>&1");
		}
		else{
			$type='txt';
			$name='Error.txt';
			$file="Does not support '$type' files";
		}
		if(is_file($file)){
			if(filesize($file)>0)
				returnFile($file,$name,$type);
			else
				returnFile(debug($cmd,$output),'Error.txt','txt');
			@unlink($file);
		}
		else if(isset($output))
			returnFile(debug($cmd,$output),'Error.txt','txt');
		else
			returnFile($file,$name,$type);
	}
	else
		returnFile("No legit file names provided",'404_Error.txt','txt');
}
else if(isset($_GET['file'])){
	if(file_exists("scans/".$_GET['file'])){
		if(isset($_GET['compress'])){
			$file='/tmp/download-'.md5(time().rand()).'.zip';
			$cmd="cd 'scans' && zip '$file' ".escapeshellarg($_GET['file']);
			$output=shell_exec("$cmd 2>&1");
			if(is_file($file)){
				returnFile($file,substr($_GET['file'],0,strrpos($_GET['file'],'.')).'.zip','zip');
				@unlink($file);
			}
			else
				returnFile(debug($cmd,$output),'Error.txt','txt');
		}
		else{
			$ext=substr($_GET['file'],strrpos($_GET['file'],".")+1);
			returnFile("scans/".$_GET['file'],$_GET['file'],$ext);
		}
	}
	else
		returnFile("The file ".$_GET['file']." was not found in the scans folder.",'404.txt','txt');
}
else if(isset($_GET['update'])){
	$file=@file_get_contents("https://raw.github.com/GM-Script-Writer-62850/PHP-Scanner-Server/master/README");
	if($file){
		$file=substr($file,strpos($file,'For Version: ')+13);
		$file=substr($file,0,strpos($file,PHP_EOL));
		$vs=version_compare($file,$_GET['update']);// -1 = older, 0 = same, 1 = newer
		echo "{\"state\":\"$vs\",\"version\":\"$file\"}";
		$f=@fopen("config/gitVersion.txt",'w+');
		@fwrite($f,$file);
		@fclose($f);
	}
	else
		echo '{"state":-2,"vs":null}';
}
else{
	ob_start();
	echo "GET=";
	var_dump($_GET);
	echo "POST=";
	var_dump($_POST);
	$text=ob_get_clean();
	returnFile("You: Hey download.php I want a download.\nMe: Ok here you go!\nYou: Ha ha, very funny that is not what I meant\nMe: Well maybe if you told me what you want I could give it to you\n\nDEBUG:\n$text","Reply.txt",'txt');
}
?>
