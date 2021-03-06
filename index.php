<?php
include 'config.php';

# ****************
# Varables
# ****************

$SCANNER=Get_Values('scanner');
$QUALITY=Get_Values('quality');
$SIZE=Get_Values('size');
$BRIGHT=Get_Values('bright');
$CONTRAST=Get_Values('contrast');
$MODE=Get_Values('mode');
$ORNT=Get_Values('ornt');
$DUPLEX=Get_Values('duplex');
$ORNT=(strlen($ORNT)==0?'vert':$ORNT);
$ROTATE=Get_Values('rotate');
$FILETYPE=Get_Values('filetype');
$LANG=Get_Values('lang');
$SCALE=Get_Values('scale');
$SAVEAS=Get_Values('saveas');
$SET_SAVE=Get_Values('set_save');
$M_WIDTH=Get_Values('loc_maxW');
$M_HEIGHT=Get_Values('loc_maxH');
$WIDTH=Get_Values('loc_width');
$HEIGHT=Get_Values('loc_height');
$X_1=Get_Values('loc_x1');
$Y_1=Get_Values('loc_y1');
#$X_2=Get_Values('loc_x2'); Un-used
#$Y_2=Get_Values('loc_y2'); Un-used
$SOURCE=Get_Values('source');
$SOURCE=(strlen($SOURCE)==0?'Inactive':$SOURCE);

$notes='Please read the <a href="index.php?page=About">release notes</a> for more information.';
$user=posix_getpwuid(posix_geteuid());
$user=$user['name'];
$here=$user.'@'.$_SERVER['SERVER_NAME'].':'.getcwd();
$debug='';

# ****************
# Functions
# ****************

function Get_Values($name){
	if(isset($_REQUEST[$name])){
		$name=$_REQUEST[$name];
		if(is_numeric($name))
			if(intval($name)==floatval($name))
				return intval($name);
			else
				return floatval($name);
		else if(strtolower($name)==='true')
			return true;
		else if(strtolower($name)==='false')
			return false;
		return $name;
	}
	else
		return null;
}

function shell($X){
	return escapeshellarg($X);
}

function Put_Values() { # Update values back to form (There is no redo for cropping)
	echo '<script type="text/javascript">config('.json_encode((object)array(
		'scanner'=>$GLOBALS['SCANNER'],
		'source'=>$GLOBALS['SOURCE'],
		'quality'=>$GLOBALS['QUALITY'],
		'duplex'=>$GLOBALS['DUPLEX'],
		'size'=>$GLOBALS['SIZE'],
		'ornt'=>$GLOBALS['ORNT'],
		'mode'=>$GLOBALS['MODE'],
		'bright'=>$GLOBALS['BRIGHT'],
		'contrast'=>$GLOBALS['CONTRAST'],
		'rotate'=>$GLOBALS['ROTATE'],
		'scale'=>$GLOBALS['SCALE'],
		'filetype'=>$GLOBALS['FILETYPE'],
		'lang'=>$GLOBALS['LANG'],
		'set_save'=>$GLOBALS['SET_SAVE']
	)).');</script>';
}

function Print_Message($TITLE,$MESSAGE,$ALIGN) { # Add a Message div after the page has loaded
	$TITLE=js(html($TITLE));
	$MESSAGE=js($MESSAGE);
	$ALIGN=js(html($ALIGN));
	include "res/inc/message.php";
}

function Update_Preview($l) { # Change the Preview Pane image via JavaScript
	echo '<script type="text/javascript">'.
		'getID("preview_img").childNodes[0].childNodes[0].src="'.js($l).'";'.
		'</script>';
}

function addRuler(){
	echo '<script type="text/javascript">addRuler();</script>';
}

function genIconLinks($config,$file,$isBulk){
	// The Last Scan button is unique to the scan page, it is in res/main.js and res/inc/scan.php
	if($config===null)
		$config=(object)array();
	$sURL=url(substr($file,5));
	$sJS=html(js(substr($file,5)));
	$URL=url($file);
	$JS=html(js($file));
	$icons=(object)array(
		'download'=>(object)array(
			'href'=>"download.php?file=$URL",
			'disable'=>isset($config->{'download'}),
			'tip'=>'Download'
		),
		'zip'=>(object)array(
			'href'=>"download.php?file=$URL&amp;compress",
			'disable'=>isset($config->{'zip'}),
			'tip'=>'Download Zip',
			'bulk'=>"bulkDownload(this,'zip')"
		)
		,
		'pdf'=>(object)array(
			'href'=>'#',
			'onclick'=>"return PDF_popup('$sJS');",
			'disable'=>isset($config->{'pdf'}),
			'tip'=>'Download PDF',
			'bulk'=>"PDF_popup(filesLst)"
		),
		'print'=>(object)array(
			'href'=>"print.php?file=$URL",
			'target'=>'_blank',
			'disable'=>isset($config->{'print'}),
			'tip'=>'Print',
			'bulk'=>"bulkPrint(this)"
		),
		'del'=>(object)array(
			'href'=>"index.php?page=Scans&amp;delete=Remove&amp;file=$sURL",
			'onclick'=>"return delScan('$sJS',true)",
			'disable'=>isset($config->{'del'}),
			'tip'=>'Delete',
			'bulk'=>"bulkDel()"
		),
		'edit'=>(object)array(
			'href'=>"index.php?page=Edit&amp;file=$sURL",
			'disable'=>isset($config->{'edit'}),
			'tip'=>'Edit'
		),
		'view'=>(object)array(
			'href'=>"index.php?page=View&amp;file=$URL",
			'disable'=>isset($config->{'view'}),
			'tip'=>'View',
			'bulk'=>"bulkView(this)"
		),
		'upload'=>(object)array(
			'href'=>'#',
			'onclick'=>"return upload('$JS');",
			'disable'=>isset($config->{'upload'}),
			'tip'=>'Upload to Imgur',
			'bulk'=>"bulkUpload()"
		),
		'email'=>(object)array(
			'href'=>'#',
			'onclick'=>"return emailManager('$JS');",
			'disable'=>isset($config->{'email'}),
			'tip'=>'Email',
			'bulk'=>"emailManager('Scan_Compilation')"
		)
	);
	if($GLOBALS['PAGE']=='Scan'){
		$click=false;
		if(isset($_COOKIE['lastScan'])&&!isset($config->{'recent'})){
			$cookie=json_decode($_COOKIE['lastScan']);
			if(file_exists("scans/".$cookie->{"raw"})&&file_exists("scans/".$cookie->{"preview"}))
				$click="return lastScan(".html(json_encode($cookie)).",this,'".html(js(genIconLinks((object)array('recent'=>0),$cookie->{'raw'},false)))."')";
			else
				setcookie('lastScan','',0);
		}
		$icons->{'recent'}=(object)array(
			'href'=>'#',
			'onclick'=>(is_bool($click)?'false':$click),
			'disable'=>is_bool($click),
			'tip'=>'Last Scan'
		);
	}
	$html='';
	foreach($icons as $icon => $link){
		if($link->{'disable'})
			$html.='<span class="tool icon '.$icon.'-off"><span class="tip">'.$link->{"tip"}.' (Disabled)</span></span>';
		else{
			$html.='<a class="tool icon '.$icon.'"';
			if($isBulk)
				$html.=" onclick=\"return ".$link->{"bulk"}."\"";
			else{
				foreach($link as $attr => $val){
					if($attr=='disable')
						break;
					$html.=" $attr=\"$val\"";
				}
			}
			$html.='><span class="tip">'.$link->{"tip"}.'</span></a>';
		}
	}
	return $html;
}

function Update_Links($l,$p) { # Change the Preview Pane image links via JavaScript
	echo '<script type="text/javascript">'.
		'getID("preview_links").innerHTML="<h2>'.html($l).'</h2><p>'.
		js(genIconLinks($p=="Edit"?(object)array('edit'=>'disable'):null,$l,false)).
		'</p>";</script>';
}

function SaveFile($file,$content){// @ Suppresses any warnings
	$file=@fopen($file,'w+');
	@fwrite($file,$content);
	@fclose($file);
	if(is_bool($file))
		return $file;
	return true;
}

function checkFreeSpace($X){
	$pace=disk_free_space('scans')/1024/1024;
	if($pace<$X){// There is less than X MB of free space
		Print_Message("Warning: Low Disk Space","There is only ".number_format($pace)." MB of free space, please delete some scan(s) if any.<br/>Low disk space can cause really bad problems.",'center');
	}
	return $pace;
}

function fileSafe($l){
	if(strpos($l,"/")>-1)
		$l=substr($l,strrpos($l,"/")+1);
	return $l;
}

function validNum($arr){
	for($i=0,$m=count($arr);$i<$m;$i++){
		if(!is_numeric($arr[$i]))
			return false;
	}
	return true;
}

function exe($shell,$force){
	$output=str_replace("\\n","\n",shell_exec($shell.($force?' 2>&1':'')).($force?'':'The output of this command unfortunately has to be suppressed to prevent errors :(\nRun `sudo -u '.$GLOBALS['user']." $shell` for output info"));
	$GLOBALS['debug'].=$GLOBALS['here'].'$ '.$shell."\n".$output.(substr($output,-1)=="\n"?"":"\n");
	return $output;
}

function debugMsg($msg){// Good for printing a quick message during testing
	Print_Message("Debug Message",$msg,'center');
}

function findLangs(){
	$tess="/usr/share/tesseract-ocr/tessdata";// This is where tesseract stores it language files
	$langs="/usr/share/doc";// This is where documentation is stored
	if(is_dir($tess)){
		$langs=array();
		$tess=scandir($tess);
		for($i=2,$max=count($tess);$i<$max;$i++){
			$pos=strpos($tess[$i],'.');
			if($pos){
				$tess[$i]=substr($tess[$i],0,strpos($tess[$i],'.',$pos));
				if(!in_array($tess[$i],$langs)){
					array_push($langs,$tess[$i]);
				}
			}
		}
	}
	else if(is_dir($langs)){
		$langs=explode("\n",substr(exe("ls ".shell($langs)." | grep 'tesseract-ocr-' | sed 's/tesseract-ocr-//'",true),0,-1));
	}
	else{
		Print_Message("Tesseract Error:","Unable to find any installed language files or documentation.<br/>You can edit lines 145 and or 146 of <code>".getcwd()."/index.php</code> with the correct location for your system.","center");
		$langs=array();
	}
	return $langs;
}

function uuid2bus($d){// Bug #13
	$id=$d->{"UUID"};
	$d=$d->{"DEVICE"};
	$data=exe("lsusb -d ".shell($id)." # See Bug #13",true);
	if(strlen($data)==0)
		return $d;// Scanner must not be connected
	$bus=substr($data,strpos($data,"Bus ")+4,3);
	$dev=substr($data,strpos($data,"Device ")+7,3);
	$pos=strpos($d,"libusb:")+7;
	return substr($d,0,$pos)."$bus:$dev".substr($d,$pos+9);
}

function quit(){
	echo '<script type="text/javascript">Debug("'.js(html($GLOBALS['debug'])).js(html($GLOBALS['here']."$ ")).'",'.(isset($_COOKIE["debug"])?$_COOKIE["debug"]:'false').');';
	if($GLOBALS['CheckForUpdates']){
		$VER=$GLOBALS['VER'];
		$file="config/gitVersion.txt";
	 	$time=is_file($file)?filemtime($file):time()/2;
	 	if($time+3600*24<time())
			echo "updateCheck('$VER',null);";
		else{
			$file=file_get_contents($file);
			if(version_compare($file,$VER)==1)
				echo "updateCheck('$file',true);";
		}
	}
	die('</script></body></html>');
}

# ****************
# Generate that Fortune
# ****************

$dir="/usr/games";// This is where fortune, cowsay, and cowthink are installed to
if(file_exists("$dir/fortune") && $Fortune===true){
	if(!isset($_COOKIE["fortune"]))
		$_COOKIE["fortune"]=$Fortune;
	else
		$_COOKIE["fortune"]=$_COOKIE["fortune"]=='true';
	if($Fortune && $_COOKIE["fortune"]){
		if(file_exists("$dir/cowsay")&&file_exists("$dir/cowthink")){
			$cows=scandir("/usr/share/cowsay/cows/");// This is where cowsay's ACSII art is stored
			$type=Array('say','think');
			exe(escapeshellcmd("$dir/fortune")." | ".escapeshellcmd("$dir/cow".$type[rand(0,1)])." -f ".shell($cows[rand(2,count($cows)-1)]),true);
		}
		else{
			exe(escapeshellcmd("$dir/fortune"),true);
		}
	}
}
else{
	$Fortune=NULL;
}

# ****************
# Spit out that HTML!
# ****************

$PAGE=Get_Values('page');
$ACTION=Get_Values('action');

if($PAGE==NULL)
	$PAGE="Scan";

# ****************
# Verify Install (For anyone who installs from git and does not read the notes written in several places)
# ****************

if(!function_exists("json_decode")){
	$here=getcwd();
	$PAGE="Incomplete Installation";
	InsertHeader($PAGE);
	Print_Message("Missing Dependancy","<i>php5-json</i> does not appear to be installed, or you forgot to restart <i>apache2</i> after installing it.","center");
	Footer('');
	quit();
}

$dirs=Array('scans','config','config/parallel');
foreach($dirs as $dir){
	if(!is_dir($dir)){
		@mkdir($dir);
		if(is_dir($dir))
			continue;
		$here=getcwd();
		$PAGE="Incomplete Installation";
		InsertHeader($PAGE);
		Print_Message("Missing Directory","<i>$here/$dir</i> does not exist!<br/><code>$user</code> also needs to have write access to it<br>To fix run this in a terminal as root<br><code>mkdir $here/$dir && chown $user $here/$dir</code>","center");
		Footer('');
		quit();
	}
}

# ****************
# Login Page
# ****************
if($RequireLogin&&!$Auth||$PAGE=='Login'){
	$PAGE='Login';
	InsertHeader('Authenticate Required');
	include('res/inc/login.php');
	Footer('');
}
# ****************
# All Scans Page
# ****************
else if($PAGE=="Scans"){
	InsertHeader("Scanned Images");

	# Delete selected scanned image
	$DELETE=Get_Values('delete');

	if($DELETE=="Remove"){
		$FILE=fileSafe(Get_Values('file'));
		if($FILE==null){
			$files=scandir('scans');
			foreach($files as $file){
				if($file=='.'||$file=='..')
					continue;
				unlink("scans/$file");
			}
		}
		else{
			$FILE2=substr($FILE,0,strrpos($FILE,".")+1);
			@unlink("scans/Preview_".$FILE2."jpg");
			@unlink("scans/Scan_$FILE");
			Print_Message("File Deleted","The file <code>".html($FILE)."</code> has been removed.",'center');
		}
	}
	include('res/inc/scans.php');
	checkFreeSpace($FreeSpaceWarn);
	Footer('');
}
# ****************
# Config Page
# ****************
else if($PAGE=="Config"){
	InsertHeader("Configure");
	if($ACTION=="Delete-Setting"){ # Delete saved scan settings
		$val=Get_Values('value');
		if($val==null){
			if(file_exists("config/settings.json")){
				if(@unlink("config/settings.json"))
					Print_Message("Deleted","All saved scan settings have been removed!","center");
				else
					Print_Message("Error","Unable to delete <code>".getcwd()."config/settings.json</code>","center");
			}
			else
				Print_Message("Unable to remove saved scanner settings:","There are no settings to remove, therefore that action can not be completed","center");
		}
		else{
			$file=json_decode(file_get_contents("config/settings.json"));
			unset($file->{$val});
			if(!SaveFile("config/settings.json",json_encode($file)))
				Print_Message("Permission Error:","<code>$user</code> does not have permission to write files to the <code>".getcwd()."/config</code> folder.<br/>$notes",'center');
			else
				Print_Message("Deleted","<code>".html($val)."</code> has been deleted!","center");
		}
	}
	else if($ACTION=="Detect-Paper"){
		$paper=explode("\n",exe("paperconf -aNhwm",true));
		unset($paper[count($paper)-1]);// Delete empty value
		sort($paper);// Lets sort this while we have the chance
		$PAPER=json_decode('{}');
		for($i=0,$s=count($paper);$i<$s;$i++){
			$sheet=explode(" ",$paper[$i]);
			if($sheet[3]<$sheet[1]){
				$tmp=$sheet[3];
				$sheet[3]=$sheet[1];
				$sheet[1]=$tmp;
			}
			$PAPER->{$sheet[0]}=array("height" => $sheet[3], "width" => $sheet[1]);
		}

		if(SaveFile("config/paper.json",json_encode($PAPER))){
			Print_Message("Paper:","$s different paper sizes were detected and are now usable.<br/>The number varies from scanner to scanner",'center');
		}
		else{
			Print_Message("Paper:","$s different paper sizes were detected.<br/>However, <code>$user</code> does not have permission to write files to the <code>".html(getcwd()).'/config</code> folder.','center');
		}
	}
	else if($ACTION=="Delete-Paper"){
		if(@unlink("config/paper.json"))
			Print_Message("Paper:","Paper configuration has been deleted","center");
		else
			Print_Message("Paper:","Failed to delete paper configuration","center");
	}

	if(file_exists("config/settings.json"))
		$file=json_decode(file_get_contents("config/settings.json"));
	else
		$file=json_decode('[]');
	include "res/inc/config.php";

	Footer('');

	if($ACTION=="Search-For-Scanners"){ # Find avalible scanners on the system
		$OP=json_decode(
			"[".substr(
				exe('scanimage -f "{\\"ID\\":%i,\\"INUSE\\":0,\\"DEVICE\\":\\"%d\\",\\"NAME\\":\\"%v %m %t\\"},"',true),
				0,
				-1
			)."]"
		);
		$ct=count($OP);
		$scan=scandir('config/parallel');
		for($i=0,$max=count($scan);$i<$max;$i++){
			if($scan[$i]=="."||$scan[$i]=="..")
				continue;
			$OP[$ct]=json_decode(file_get_contents("config/parallel/".$scan[$i]));
			$OP[$ct]->{'ID'}=$ct;
			$OP[$ct]->{'INUSE'}=0;
			$ct++;
		}
		$FakeCt=0;
		if($ExtraScanners){
			$sample=scandir('res/scanhelp');
			unset($sample[0]);unset($sample[1]);// Delete ./ and ../ from the list
			foreach($sample as $key => $val){
				$help=file_get_contents('res/scanhelp/'.$val);
				$help=substr($help,strpos($help,'Options specific to device `')+28);
				$help=substr($help,0,strpos($help,"':"));
				$OP[$ct]=(object)array("ID" => $ct, "INUSE" => 0, "DEVICE" => $help, "NAME" => $val);
				$ct++;
				$FakeCt++;
			}
		}
		for($i=0;$i<$ct;$i++){// Get scanner specific data
			if($i<$ct-$FakeCt)
				$help=exe("scanimage --help -d ".shell($OP[$i]->{"DEVICE"}),true);
			else
				$help=file_get_contents('res/scanhelp/'.$OP[$i]->{"NAME"});
			// Get Source
			$sources=substr($help,strpos($help,'--source ')+9);
			$defSource=substr($sources,strpos($sources,' [')+2);
			$defSource=substr($defSource,0,strpos($defSource,']'));
			$OP[$i]->{"SOURCE"}=strtolower($defSource)=='inactive'?'Inactive':substr($sources,0,strpos($sources,' ['));
			$sources=explode('|',$OP[$i]->{"SOURCE"});

			foreach($sources as $key => $val){
				if($val=='Inactive'||$val==$defSource)
					$help2=$help;
				else{
					if($i<$ct-$FakeCt)
						$help2=exe("scanimage --help -d ".shell($OP[$i]->{"DEVICE"})." --source ".shell($val),true);
					else{
						$help2=file_get_contents('res/scanhelp/'.$OP[$i]->{"NAME"});
						exe("echo ".shell("scanimage --help -d 'SIMULATED_$i-$key' --source '$val'"),true);
					}
				}
				if(!is_bool(strpos($help2,' (core dumped)')))
					Print_Message("Warning: scanimage crashed",html($OP[$i]->{"NAME"})." may not be configured properly.<br/>Check the Debug Console for details.",'center');
				// Get DPI
				$res=substr($help2,strpos($help2,'--resolution ')+13);
				$res=substr($res,0,strpos($res,'dpi'));
				if(is_int(strpos($res,".."))){// Range of sizes of not it is a list (I want list form)
					$res=explode('..',$res);
					$arr=Array();
					array_push($arr,$res[0]);
					for($x=intval(ceil(($res[0]+1)/100).'00');$x<=$res[1];$x+=100){
						array_push($arr,$x);
					}
					$res=implode("|",$arr);
				}
				else if(is_int(strpos($res,"auto||"))){
					$res='auto'.substr($res,5);
				}
				$OP[$i]->{"DPI-$val"}=$res;
				// Get duplex availability
				$duplex=strpos($help2,'--duplex[=(yes|no)] [');// Looking for this: --duplex[=(yes|no)] [inactive]
				if(!is_bool($duplex)){
					$duplex=substr($help2,$duplex+21);
					$duplex=substr($duplex,0,strpos($duplex,']'));
					$duplex=strtolower($duplex)!=='inactive';
					// TODO: add support for --adf-mode Simplex|Duplex [inactive]
				}
				else{
					$duplex=strpos($help2,'--adf-mode ');
					if(!is_bool($duplex)){
						$duplex=substr($help2,$duplex+11);
						$duplexOpts=substr($duplex,0,strpos($duplex,' ['));
						$duplex=substr($duplex,strpos($duplex,' [')+2);
						$duplex=substr($duplex,0,strpos($duplex,']'));
						$duplex=strtolower($duplex)!=='inactive'?$duplexOpts:false;
					}
				}
				$OP[$i]->{"DUPLEX-$val"}=$duplex;
				// Get color modes
				$modes=substr($help2,strpos($help2,'--mode ')+7);
				$OP[$i]->{"MODE-$val"}=substr($modes,0,strpos($modes,' ['));
				// Get bay width
				$width=substr($help2,strpos($help2,' -x ')+4);
				$width=substr($width,0,strpos($width,'mm'));
				$OP[$i]->{"WIDTH-$val"}=floatval(substr($width,strpos($width,'..')+2));
				// Get bay height
				$height=substr($help2,strpos($help2,' -y ')+4);
				$height=substr($height,0,strpos($height,'mm'));
				$OP[$i]->{"HEIGHT-$val"}=floatval(substr($height,strpos($height,'..')+2));
				/*if(!is_bool(strpos($OP[$i]->{"DEVICE"},"Deskjet_2050_J510_series"))){// Dirty hack to make scanner work on this model (sane bug?)
					$OP[$i]->{"HEIGHT-$val"}=297.01068878173;# that is as close as php will go without rounding true size is 297.01068878173825282^9
				}*/
				if($val=='Inactive')
					break;
			}

			// Get device vendor ID and product ID (Bug #13)
			$dev=strpos($OP[$i]->{"DEVICE"},"libusb:");
			if(is_bool($dev))
				$OP[$i]->{"UUID"}=NULL;
			else if(substr($OP[$i]->{"DEVICE"},0,4)=='net:'){
				$OP[$i]->{"UUID"}=NULL;
				Print_Message('Warning','You have a networked scanner that uses <code>libusb</code>, the device string for this scanner can change over time.<br/>'.
					'If you connect <code>'.html($OP[$i]->{"NAME"}).'</code> to <code>'.$_SERVER['SERVER_NAME'].'</code> this string can be auto updated so you will not '.
					'have to rescan for scanners after a change.<br/>Things such as reboots and disconnecting the the scanner can change the device string.','center');
			}
			else{
				$dev=substr($OP[$i]->{"DEVICE"},$dev+7,7);
				$dev=exe("lsusb -s ".shell($dev),true);
				$OP[$i]->{"UUID"}=substr($dev,strpos($dev,"ID ")+3,9);
			}
			// Lamp on/off
			//$OP[$i]->{"LAMP"}=!is_bool(strpos($help,'--lamp-switch[=(yes|no)]'))&&!is_bool(strpos($help,'--lamp-off-at-exit[=(yes|no)]'));
		}
		$save=SaveFile("config/scanners.json",json_encode($OP));
		$CANNERS='<table border="1" align="center"><tbody><tr><th>Name</th><th>Device</th></tr>';
		for($i=0;$i<$ct;$i++){
			$CANNERS.='<tr><td>'.html($OP[$i]->{"NAME"}).'</td><td>'.html($OP[$i]->{"DEVICE"}).'</td></tr>';
		}
		$CANNERS.='<tr><td colspan="2" align="center">Missing a scanner? Make sure the scanner is plugged in and turned on.<br/>You may have to use the <a href="index.php?page=Access%20Enabler">Access Enabler</a>.<br/><a href="index.php?page=Parallel-Form">[Click here for parallel-port scanners]</a>'.
			($save?'':'</td></tr><tr><td colspan="2" style="color:red;font-weight:bold;text-align:center;">Bad news: <code>'.$user.'</code> does not have permission to write files to the <code>'.html(getcwd()).'/config</code> folder.<br/><code>sudo chown '.$user.' '.html(getcwd()).'/config</code>').
			'</td></tr>';
		$CANNERS.='</tbod></table>';
		if($ct>1){
			$CANNERS.='<small>It looks like you have more than one scanner. You can change the default scanner on the <a href="index.php?page=Device%20Notes">Scanner List</a> page if you want.</small>';
		}
		if(count($OP)==0)
			Print_Message("No Scanners Found","There were no scanners found on this server. Make sure the scanners are plugged in and turned on. The scanner must also be supported by SANE.<br/>".
				"<a href=\"index.php?page=Parallel-Form\">[Click here for parallel-port scanners]</a><br/>".
				"If it is supported by sane and still does not showup (usb) or does not work (parallel) you may need to use the <a href=\"index.php?page=Access%20Enabler\">Access Enabler</a>".
				(in_array('lp',explode(' ',str_replace("\n",' ',exe("groups ".shell($user),true))))?'':"<br/>It appears $user is not in the lp group did you read the <a href=\"index.php?page=About\">Installation Notes</a>?"),'center');
		else
			Print_Message("Scanners Found:",$CANNERS,'center');
	}
}
# ****************
# Parallel Port Scanner Configuration
# ****************
else if($PAGE=="Parallel-Form"){
	InsertHeader("Parallel Port Scanner Setup");
	$file=fileSafe(Get_Values('file'));
	$name=Get_Values('name');
	$device=Get_Values('device');
	if($file!=null){
		unlink("config/parallel/$file");
	}
	else if($name!=null&&$device!=null){
		$can=scandir('config/parallel');
		$int=0;
		while(in_array($int.'.json',$can))
			$int++;
		$save=SaveFile('config/parallel/'.$int.'.json',json_encode(array("NAME"=>$name,"DEVICE"=>$device)));
	}
	$scan=scandir('config/parallel');
	include "res/inc/parallel.php";
	Footer('');
	if($name!=null&&$device!=null&&$file==null){
		if(!$save)
			Print_Message("Permissions Error:","<code>$user</code> does not have permission to write files to <code>".html(getcwd())."/config/parallel</code><br/>".
				"<code>sudo chown $user ".html(getcwd())."/config/parallel</code>",'center');
	}
}
# ****************
# Release Notes
# ****************
else if($PAGE=="About"){
	InsertHeader("Release Notes");
	include "res/inc/about.php";
	Footer('');
}
# ***************
# PHP Info
# ***************
else if($PAGE=="PHP Information"){
        InsertHeader($PAGE);
        echo '<div class="box box-full"><h2>'.$PAGE.'</h2><iframe id="phpinfo" src="res/phpinfo.php" style="display:block;border:none;width:100%;height:500px;"></iframe><script type="text/javascript">';
	include "res/writeScripts/phpinfo.js";
	echo '</script></div>';
        Footer('');
}
# ***************
# Paper Manager
# ***************
else if($PAGE=="Paper Manager"){
	InsertHeader("Paper Manager");
	include "res/inc/paper.php";
	Footer('');
}
# ****************
# Access Enabler
# ****************
else if($PAGE=="Access Enabler"){
	InsertHeader("Release Notes");
	include "res/inc/enabler.php";
	Footer('');
}
# ****************
# Scanner Info
# ****************
else if($PAGE=="Device Notes"){
	$id=Get_Values('id');
	if($id!==null){// Set default scanner
		$id=intval($id);
		if(is_int($id)&&file_exists("config/scanners.json")){
			$CANNERS=json_decode(file_get_contents('config/scanners.json'));
			$s=count($CANNERS);
			if($s>$id){
				for($i=0;$i<$s;$i++){
					if(isset($CANNERS[$i]->{"SELECTED"}))
						unset($CANNERS[$i]->{"SELECTED"});
				}
				$CANNERS[$id]->{"SELECTED"}=1;
				SaveFile("config/scanners.json",json_encode($CANNERS));
			}
		}
	}
	if(isset($ACTION)){// Scanner Help
		InsertHeader("Device Info");
		// Bug #13 START
		$CANNERS=json_decode(file_get_contents('config/scanners.json'));
		foreach($CANNERS as $key){
			if($key->{"DEVICE"}==$ACTION){
				if(!is_null($key->{"UUID"})){
					$ACTION=uuid2bus($key);
				}
				break;
			}
		}
		// Bug #13 END
		$SOURCE=Get_Values('source');
		if(is_null($SOURCE))
			$SOURCE='';
		else
			$SOURCE=' --source '.shell($SOURCE);
		$help=exe("scanimage --help -d ".shell($ACTION).$SOURCE,true);
		echo "<div class=\"box box-full\"><h2>$ACTION</h2><pre>".$help."</pre></div>";
	}
	else{// List Scanners
		InsertHeader("Device List");
		if(!isset($CANNERS)){
			if(file_exists("config/scanners.json"))
				$CANNERS=json_decode(file_get_contents("config/scanners.json"));
			else
				$CANNERS=json_decode('[]');
		}
		else{
			Print_Message("New Default Scanner:",$CANNERS[$id]->{"DEVICE"},'center');
		}
		echo "<div class=\"box box-full\"><h2>Installed Device List</h2>".'<a style="margin-left:5px;" href="index.php?page=Config&amp;action=Search-For-Scanners" onclick="printMsg(\'Searching For Scanners\',\'Please Wait...\',\'center\',0);">Scan for Devices</a>'."<ul>";
		for($i=0,$max=count($CANNERS);$i<$max;$i++){
			$name=html($CANNERS[$i]->{"NAME"});
			$DEVICE=html($CANNERS[$i]->{"DEVICE"});
			$device=url($CANNERS[$i]->{"DEVICE"});
			$res='';
			$sources=explode('|',$CANNERS[$i]->{"SOURCE"});
			echo "<li>$name ".(isset($CANNERS[$i]->{"SELECTED"})?'':"[<a href=\"index.php?page=Device%20Notes&amp;id=$i\">Set as default scanner</a>]").
				"<ul><li><a onclick=\"printMsg('Loading','Please Wait...','center',0);\" href=\"index.php?page=Device%20Notes&amp;action=$device\"><code>$DEVICE</code></a></li>";
			for($x=0,$ct=count($sources);$x<$ct;$x++){
				$val=html($sources[$x]);
				$WIDTH=round($CANNERS[$i]->{"WIDTH-$val"}/25.4,2);
				$HEIGHT=round($CANNERS[$i]->{"HEIGHT-$val"}/25.4,2);
				$MODES=count(explode('|',$CANNERS[$i]->{"MODE-$val"}));
				$DPI=explode('|',$CANNERS[$i]->{"DPI-$val"});
				echo ($val=='Inactive'?'<li>This scanner supports<ul>':"<li>The '<a onclick=\"printMsg('Loading','Please Wait...','center',0);\" href=\"index.php?page=Device%20Notes&amp;action=$device&amp;source=$val\">$val</a>' source supports<ul>").
					"<li>A bay width of <span class=\"tool\">$WIDTH\"<span class=\"tip\">".$CANNERS[$i]->{"WIDTH-$val"}." mm</span></span></li>".
					"<li>A bay height of <span class=\"tool\">$HEIGHT\"<span class=\"tip\">".$CANNERS[$i]->{"HEIGHT-$val"}." mm</span></span></li>".
					'<li>A scanner resolution of '.$DPI[$DPI[0]=='auto'?1:0].' DPI to '.number_format($DPI[count($DPI)-1]).' DPI</li>'.
					'<li>'.($CANNERS[$i]->{"DUPLEX-$val"}?'D':'No d').'uplex (double sided) scanning</li>'.
					"<li>$MODES color mode".($MODES==1?'':'s')."</li>".
					'</ul></li>';
			}
			echo '</ul></li>';
		}
		echo '</ul></div>';
	}
	Footer('');
}
# ****************
# View Page
# ****************
else if($PAGE=="View"){
	InsertHeader("View File");
	$file=Get_Values('file');
	if(is_string($file)){
		$files=json_decode("{\"$file\":1}");
		$prefix='';
	}
	else{
		$files=json_decode(Get_Values('json'));
		$prefix='Scan_';
	}
	foreach($files as $file => $val){
		$file=fileSafe($prefix.$file);
		include "res/inc/view.php";
	}
	echo '<script type="text/javascript">disableIcons();</script>';
	Footer('');
}
# ***************
# Edit Page
# ***************
else if($PAGE=="Edit"){
	InsertHeader("Edit Image");
	$file=fileSafe(Get_Values('file'));
	if($file!=null){
		if(substr($file,-3)=="txt")
			include "res/inc/edit-text.php";
		else{
			if(Get_Values('edit')!=null){
				if(file_exists("scans/Scan_$file")){
					$langs=findLangs();
					if(!validNum(Array($WIDTH,$HEIGHT,$X_1,$Y_1,$BRIGHT,$CONTRAST,$SCALE,$ROTATE))||
					  ($FILETYPE!=="txt"&&$FILETYPE!=="png"&&$FILETYPE!=="tiff"&&$FILETYPE!=="jpg")||
					  !in_array($LANG,$langs)){
						Print_Message("No, you can not do that","Input data is invalid and most likely an attempt to run malicious code on the server <i>denied</i>",'center');
						Footer('');
						quit();
					}
					$tmpFileRaw="/tmp/Scan_$file";
					$fileRaw="scans/Scan_$file";
					if(!@copy($fileRaw,$tmpFileRaw)){
						Print_Message("Permission Error","Unable to create <code>$tmpFileRaw</code>",'center');
						quit();
					}
					$tmpFile=shell($tmpFileRaw);
					$file=shell($fileRaw);
					if($MODE!='color'&&$MODE!=null){
						if($MODE=='gray')
							exe("convert $tmpFile -colorspace Gray $tmpFile",true);
						else
							exe("convert $tmpFile -monochrome $tmpFile",true);
					}
					if($BRIGHT!="0"||$CONTRAST!="0"){
						exe("convert $tmpFile -brightness-contrast $BRIGHT".'x'."$CONTRAST $tmpFile",true);
					}
					if($WIDTH!="0"&&$HEIGHT!="0"&&$WIDTH!=null&&$HEIGHT!=null){
						$TRUE=explode("x",exe("identify -format '%wx%h' $file",true));
						$TRUE_W=$TRUE[0];
						$TRUE_H=$TRUE[1];
						$WIDTH=round($WIDTH/$M_WIDTH*$TRUE_W);
						$HEIGHT=round($HEIGHT/$M_HEIGHT*$TRUE_H);
						$X_1=round($X_1/$M_WIDTH*$TRUE_W);
						$Y_1=round($Y_1/$M_HEIGHT*$TRUE_H);
						exe("convert $tmpFile +repage -crop '$WIDTH x $HEIGHT + $X_1 + $Y_1' $tmpFile",true);
					}

					if($SCALE!="100"){
						exe("convert $tmpFile -scale '$SCALE%' $tmpFile",true);
					}
					if($ROTATE!="0"){
						exe("convert $tmpFile -rotate $ROTATE $tmpFile",true);
					}
					exe("convert $tmpFile -alpha off $tmpFile",true);
					$file=substr($fileRaw,11);
					$edit=strpos($file,'-edit-');
					$name=(is_bool($edit)?substr($file,0,-4):substr($file,0,$edit));
					$ext=substr($file,strrpos($file,'.')+1);
					$int=1;
					while(file_exists("scans/Preview_$name-edit-$int.jpg")){
						$int++;
					}
					$file="scans/Scan_$name-edit-$int.$ext";//scan
					$name=str_replace("scans/Scan_","scans/Preview_",$file);//preview
					if($FILETYPE==substr($file,strrpos($file,'.')+1)){
						@rename($tmpFileRaw,$file);// Incorrect access denied message is generated
						if(file_exists($tmpFileRaw)&&!file_exists($file)){// Just in-case it becomes accurate
							copy($tmpFileRaw,$file);
							unlink($tmpFileRaw);
						}
					}
					else if($FILETYPE!='txt'){
						$file=substr($file,0,strrpos($file,'.')+1).$FILETYPE;
						exe("convert $tmpFile ".shell($file),true);
					}
					else{
						$t=time();
						$S_FILENAMET=substr($file,0,strrpos($file,'.'));
						exe("convert $tmpFile -fx '(r+g+b)/3' ".shell("/tmp/edit_scan_file$t.tif"),true);
						exe("tesseract ".shell("/tmp/edit_scan_file$t.tif").' '.shell($S_FILENAMET)." -l ".shell($LANG),true);
						unlink("/tmp/edit_scan_file$t.tif");
						if(!file_exists("$S_FILENAMET.txt"))//In case tesseract fails
							SaveFile("$S_FILENAMET.txt","");
					}
					$FILE=substr($name,0,strrpos($name,'.')+1).'jpg';//Preview
					if($FILETYPE!='txt'){
						exe("convert ".shell($file)." -scale '450x471' ".shell($FILE),true);
						$file=substr($file,11);
					}
					else{
						exe("convert $tmpFile -scale '450x471' ".shell($FILE),true);
						unlink($tmpFileRaw);
						$file=substr($file,11,strrpos($file,'.')-10).'txt';
					}
				}
			}
			if(file_exists("scans/Scan_$file")){
				if(substr($file,-3)=="txt")
					include "res/inc/edit-text.php";
				else
					include "res/inc/edit.php";
			}
			else{
				Print_Message("404 Not Found","It appears that <code>$file</code> has been deleted.",'center');
			}
		}
	}
	else{
		if(count(scandir("scans"))==2){
			Print_Message("No Images","All files have been removed. There are no scanned images to display.",'center');
		}
		else{
			Print_Message("No File Specified","Please select a file to edit",'center');
			$FILES=explode("\n",substr(exe("cd 'scans'; ls 'Preview'*",true),0,-1));
			for($i=0,$max=count($FILES);$i<$max;$i++){
				$FILE=substr($FILES[$i],7,-3);
				$FILE=substr(exe("cd 'scans'; ls ".shell("Scan$FILE").'*',true),5);//Should only have one file listed
				$IMAGE=$FILES[$i];
				include "res/inc/editscans.php";
			}
		}
	}
	checkFreeSpace($FreeSpaceWarn);
	Footer('');
}
# ****************
# Scanner Page
# ****************
else{
	InsertHeader("Scan Image");
	$CANNERS=json_decode(file_exists("config/scanners.json")?file_get_contents("config/scanners.json"):'[]');
	if(strlen($SAVEAS)>0||$ACTION=="Scan Image"){
		$langs=findLangs();
		if(!validNum(Array($SCANNER,$BRIGHT,$CONTRAST,$SCALE,$ROTATE))||!in_array($LANG,$langs)||!in_array($QUALITY,explode("|",$CANNERS[$SCANNER]->{"DPI-$SOURCE"}))){//security check
			Print_Message("No, you can not do that","Input data is invalid and most likely an attempt to run malicious code on the server <i>denied</i>",'center');
			Footer('');
			quit();
		}
	}
	if(strlen($SAVEAS)>0){ # Save settings to conf file
		if(strlen($SET_SAVE)>0){
			$ACTION="Save Set";
			$setting=array("scanner" => $SCANNER, "source" => $SOURCE, "duplex" => $DUPLEX, "quality" => $QUALITY, "size" => $SIZE ,"ornt" => $ORNT, "mode" => "$MODE", "bright" => $BRIGHT, "contrast" => $CONTRAST, "rotate" => $ROTATE, "scale" => $SCALE, "filetype" => $FILETYPE, "lang" => $LANG);
			if(file_exists("config/settings.json")){
				$file=json_decode(file_get_contents("config/settings.json"));
				$file->{$SET_SAVE}=$setting;
				SaveFile("config/settings.json",json_encode($file));
			}
			else{
				if(!SaveFile("config/settings.json",json_encode(array($SET_SAVE => $setting)))){
					Print_Message("Permission Error:","<code>$user</code> does not have permission to write files to the <code>".getcwd()."/config</code> folder.<br/>$notes",'center');
				}
			}
		}
	}

	if(count($CANNERS)==0){ # Add scanners to scanner list
		Print_Message("No Scanners Found",'There aren\'t any scanners setup yet! Go to the <a href="index.php?page=Config">config page</a> to setup scanners.','center');
	}
	else{
		if(file_exists('config/settings.json'))
			$file=file_get_contents('config/settings.json');
		else
			$file='{}';
		include "res/inc/scan.php";
	}

	if($ACTION=="Scan Image"){# Check to see if scanner is in use
		$SCAN_IN_USE=$CANNERS[$SCANNER]->{"INUSE"};
		if($SCAN_IN_USE==1){
			Print_Message("Scanner in Use","The scanner you are trying to use is currently in use. Please try again later...",'center');
			$ACTION="Do Not Scan";
		}
		else if($WIDTH===0&&$HEIGHT===0&&$ROTATE===0)
			addRuler();
	}
	else if(strlen($ACTION)==0)
		echo '<script type="text/javascript">addRuler();scanReset();</script>';

	if(strlen($ACTION)>0) # Only update values back to form if they aren't empty
		Put_Values();
	Footer('');

	if($ACTION=="Scan Image"){ # Scan Image!
		if(is_nan($SCANNER)){
			Print_Message("Error:","<code>$SCANNER</code> is not a number, you must be trying to attack the server",'center');
			quit();
		}
		$CANDIR="/tmp/scandir$SCANNER";

		if(is_dir($CANDIR)){ # Make sure we can save the scan
			$trash=scandir($CANDIR);
			unset($trash[0]);unset($trash[1]);// Delete ./ and ../ from the list
			foreach($trash as $key)
				@unlink($key);
			rmdir($CANDIR);
			if(is_dir($CANDIR)){
				Print_Message("Permission Error:","<code>$user</code> does not have permission to delete <code>$CANDIR</code>.<br/>".
					"This can be easly fixed by running the following command at the Scanner Server.<br/><code>rm -r $CANDIR</code><br/>".
					"Once you have done that you can press F5 (Refresh) to try again with your prevously entered settings.",'center');
				quit();
			}
		}

		if(!@mkdir("$CANDIR")){
			Print_Message('Error',"Unable to create directory $CANDIR.<br>Why does <code>$user</code> not have permission?",'center');
			quit();
		}

		$sizes=explode('-',$SIZE);
		if((!validNum(Array($SCANNER,$WIDTH,$HEIGHT,$X_1,$Y_1,$BRIGHT,$CONTRAST,$SCALE,$ROTATE)))||
		   (count($sizes)!=2&&$SIZE!=='full')||
		   (!in_array($MODE,explode('|',$CANNERS[$SCANNER]->{"MODE-$SOURCE"})))||
		   (!in_array($SOURCE,explode('|',$CANNERS[$SCANNER]->{"SOURCE"})))||
		   (!in_array($DUPLEX,is_bool($CANNERS[$SCANNER]->{"DUPLEX-$SOURCE"})?array(true,false):explode('|',$CANNERS[$SCANNER]->{"DUPLEX-$SOURCE"})))||
		   ($FILETYPE!=="txt"&&$FILETYPE!=="png"&&$FILETYPE!=="tiff"&&$FILETYPE!=="jpg")){
			Print_Message("No, you can not do that","Input data is invalid and most likely an attempt to run malicious code on the server. <i>Denied</i>",'center');
			quit();
		}
		else if((!is_numeric($sizes[0])||!is_numeric($sizes[1]))&&$SIZE!=='full'){
			Print_Message("No, you can not do that","Input data is invalid and most likely an attempt to run malicious code on the server. <i>Denied</i>",'center');
			quit();
		}

		# Scanner in Use
		$CANNERS[$SCANNER]->{"INUSE"}=1;
		if(!SaveFile("config/scanners.json",json_encode($CANNERS))){
			Print_Message("Permission Error:","<code>$user</code> does not have permission to write files to the <code>".getcwd()."/config</code> folder.<br/>$notes",'center');
			quit();
		}
		$X=0;
		$Y=0;
		# Get Device
		$DEVICE=shell($CANNERS[$SCANNER]->{"DEVICE"});

		$scanner_w=$CANNERS[$SCANNER]->{"WIDTH-$SOURCE"};
		$scanner_h=$CANNERS[$SCANNER]->{"HEIGHT-$SOURCE"};

		$lastORNT=Get_Values('ornt0');
		if($lastORNT!=$ORNT&&$lastORNT!=null&&$SIZE!="full"){
			$WIDTH=0;
			$HEIGHT=0;
		}
		# Set size & orientation of scan
		if($WIDTH!==0&&$HEIGHT!==0){// Selected scan
			if($SIZE=="full"){
				$TRUE_W=$scanner_w;
				$TRUE_H=$scanner_h;
			}
			else{
				if($ORNT=="vert"){
					$TRUE_W=$sizes[0];
					$TRUE_H=$sizes[1];
				}
				else{
					$TRUE_W=$sizes[1];
					$TRUE_H=$sizes[0];
				}
			}
			$WIDTH=$WIDTH/$M_WIDTH*$TRUE_W;
			$HEIGHT=$HEIGHT/$M_HEIGHT*$TRUE_H;
			$X=$X_1/$M_WIDTH*$TRUE_W;
			$Y=$Y_1/$M_HEIGHT*$TRUE_H;
			$SIZE_X=$WIDTH;
			$SIZE_Y=$HEIGHT;
		}
		else if($SIZE=="full"){// full scan
			$SIZE_X=$scanner_w;
			$SIZE_Y=$scanner_h;
		}
		else if($sizes[0]<=$scanner_w&&$sizes[1]<=$scanner_h&&$sizes[1]<=$scanner_w&&$sizes[0]<=$scanner_h){// fits both ways
			if($ORNT!="vert"){
				$SIZE_X=$sizes[0];
				$SIZE_Y=$sizes[1];
			}
			else{
				$SIZE_X=$sizes[0];
				$SIZE_Y=$sizes[1];
			}
		}
		else if($sizes[0]<=$scanner_w&&$sizes[1]<=$scanner_h){//fits tall way
			$SIZE_X=$sizes[0];
			$SIZE_Y=$sizes[1];
		}
		else if($sizes[1]<=$scanner_w&&$sizes[0]<=$scanner_h){//fits wide way
			$SIZE_X=$sizes[1];
			$SIZE_Y=$sizes[0];
		}
		else{
			Print_Message("Sorry...","The scan page should not have offered this page size as it does not fit in your scanner.<br/>That paper will not fit in the scanner running a full scan.".
				"<br/>Scanner width is $scanner_w mm<br/>Scanner height is $scanner_h mm".
				"<br/>Paper width is ".$sizes[0]." mm<br/>Paper height is ".$sizes[1]." mm",'center');
			$SIZE="-x $scanner_w -y $scanner_h";
			$SIZE_X=$scanner_w;
			$SIZE_Y=$scanner_h;
		}
		$LAMP='';
		/*if($CANNERS[$SCANNER]->{'LAMP'}===true){
			$LAMP='--lamp-switch=yes --lamp-off-at-exit=yes ';
		}*/

		if(!is_bool($CANNERS[$SCANNER]->{"DUPLEX-$SOURCE"})){
			$DUPLEX="--adf-mode $DUPLEX";
		}
		else if($CANNERS[$SCANNER]->{"DUPLEX-$SOURCE"}===true){
			if($DUPLEX==true)
				$DUPLEX='--duplex=yes ';
			else
				$DUPLEX='--duplex=no ';
		}
		else
			$DUPLEX='';
		$OURCE=($SOURCE=='Inactive')?'':"--source ".shell($SOURCE)." ";
		if(!is_null($CANNERS[$SCANNER]->{"UUID"})){// Bug #13
			$DEVICE2=uuid2bus($CANNERS[$SCANNER]);
			$CANNERS[$SCANNER]->{"DEVICE"}=$DEVICE2;
			$DEVICE=shell($DEVICE2);
		}
		$cmd="scanimage -d $DEVICE -l $X -t $Y -x $SIZE_X -y $SIZE_Y $DUPLEX--resolution $QUALITY $OURCE--mode ".shell($MODE)." $LAMP--format=pnm";
		if($SOURCE=='ADF'||$SOURCE=='Automatic Document Feeder') # Multi-page scan
			exe("cd $CANDIR;$cmd --batch",true);// Be careful with this, doing this without a ADF feeder will result in scanning the flatbed over and over, include --batch-count=3 for testing
		else # Single page scan
			exe("$cmd > ".shell("$CANDIR/scan_file$SCANNER.pnm"),false);

		if(file_exists("$CANDIR/scan_file$SCANNER.pnm")){
			if(Get_Values('size')=='full'&&filesize("$CANDIR/scan_file$SCANNER.pnm")==0){
				exe("echo 'Scan Failed...'",true);
				exe("echo 'Maybe this scanner does not report it size correctly, maybe the default scan size will work it may or may not be a full scan.'",true);
				exe("echo 'If it is not a full scan you are welcome to manually edit your $here/config/scanners.json file with the correct size.'",true);
				@unlink("$CANDIR/scan_file$SCANNER.pnm");
				exe("echo 'Attempting to scan without forcing full scan'");
				exe("scanimage -d $DEVICE --resolution $QUALITY --mode ".shell($MODE)." $LAMP--format=ppm > ".shell("$CANDIR/scan_file$SCANNER.pnm"),false);
			}
		}

		if(count($CANNERS)>1&&isset($DEVICE2)){
			$CANNERS=json_decode(file_get_contents("config/scanners.json"));
			$CANNERS[$SCANNER]->{"DEVICE"}=$DEVICE2;// See bug 13
		}
		$CANNERS[$SCANNER]->{"INUSE"}=0;
		SaveFile("config/scanners.json",json_encode($CANNERS));

		$startTime=time();
		$files=scandir($CANDIR);
		$GMT=0;
		if(strlen($TimeZone)>0){
			date_default_timezone_set($TimeZone);
		}
		else if(ini_get('date.timezone')==='' && version_compare(phpversion(), '5.1', '>=')){
			date_default_timezone_set('UTC');
			$GMT=intval(exe('date +%z',true))*36;
			exe('echo "Warning, Guessing Time Zone:\n\tGuessed as GMT '.($GMT/60/60).'.\n\tdate.timezone is not set in your /etc/php5/apache2/php.ini file.\n\tIt is probally set on line 880.\n\tThere is also a override in '.getcwd().'/config.php on line 11."',true);
		}
		for($i=2,$ct=count($files);$i<$ct;$i++){
			$SCAN=shell("$CANDIR/".$files[$i]);

			# Dated Filename for scan image & preview image
			$FILENAME=date("M_j_Y~G-i-s",filemtime("$CANDIR/".$files[$i])+$GMT);
			$S_FILENAME="Scan_$SCANNER"."_"."$FILENAME.$FILETYPE";
			$P_FILENAME="Preview_$SCANNER"."_"."$FILENAME.jpg";

			# Adjust Brightness
			if($BRIGHT!="0"||$CONTRAST!="0"){
				exe("convert $SCAN -brightness-contrast '$BRIGHT".'x'."$CONTRAST' $SCAN",true);
			}

			# Rotate Image
			if($ROTATE!="0"){
				exe("convert $SCAN -rotate '$ROTATE' $SCAN",true);
			}

			# Scale Image
			if($SCALE!="100"){
				exe("convert $SCAN -scale '$SCALE%' $SCAN",true);
			}

			# Generate Preview Image
			//~exe("convert $SCAN -scale '450x471' ".shell("scans/$P_FILENAME"),true);
			exe("cp $SCAN ".shell("scans/$P_FILENAME"),true);

			# Convert scan to file type
			if($FILETYPE=="txt"){
				$S_FILENAMET=substr($S_FILENAME,0,strrpos($S_FILENAME,'.'));
				exe("convert $SCAN -fx '(r+g+b)/3' ".shell("/tmp/_scan_file$SCANNER.tif"),true);
				exe("tesseract ".shell("/tmp/_scan_file$SCANNER.tif").' '.shell("scans/$S_FILENAMET")." -l ".shell($LANG),true);
				unlink("/tmp/_scan_file$SCANNER.tif");
				if(!file_exists("scans/$S_FILENAMET.txt"))//in case tesseract fails
					SaveFile("scans/$S_FILENAMET.txt","");
			}
			else{
				//~exe("convert $SCAN -alpha off ".shell("scans/$S_FILENAME"),true);
				exe("cp $SCAN ".shell("scans/$S_FILENAME"),true);
			}
			@unlink("$CANDIR/".$files[$i]);
		}
		@rmdir($CANDIR);
		$endTime=time();

		# Remove Crop Option / set lastScan
		if(($WIDTH!==0&&$HEIGHT!==0)||$ROTATE!==0)
			$strip=true;
		else{
			setcookie('lastScan',json_encode(Array(
				"raw"=>$S_FILENAME,"preview"=>$P_FILENAME,"fields"=>Array(
					"scanner"=>$SCANNER,"quality"=>$QUALITY,"duplex"=>$DUPLEX,
					"size"=>$SIZE,"ornt"=>$ORNT,"mode"=>$MODE,"bright"=>$BRIGHT,
					"contrast"=>$CONTRAST,/*"rotate"=>$ROTATE,*/"scale"=>$SCALE,//No need for rotate it will be 0 every time
					"filetype"=>$FILETYPE,"lang"=>$LANG,"set_save"=>$SET_SAVE
				)
			)),time()+86400,substr($_SERVER['PHP_SELF'],0,strlen(end(explode('/',$_SERVER['PHP_SELF'])))*-1),$_SERVER['SERVER_NAME']);
		}
		$ORNT=($ORNT==''?'vert':$ORNT);
		echo "<script type=\"text/javascript\">var ornt=document.createElement('input');ornt.name='ornt0';ornt.value='$ORNT';ornt.type='hidden';document.scanning.appendChild(ornt);".
			($ROTATE!="0"?"var p=document.createElement('p');p.innerHTML='<small>Changing orientation will void select region.</small>';getID('opt').appendChild(p);":'').
			"$(document).ready(function(){document.scanning.scanner.disabled=true;".(isset($strip)?"stripSelect();":'')."});</script>";
		# Check if image is empty and post error, otherwise post image to page
		if(!file_exists("scans/$P_FILENAME")){
			Print_Message("Could not scan",'<p style="text-align:left;margin:0;">This is can be cauesed by one or more of the following:</p>'.
				'<ul><li>The scanner is not on.</li><li>The scanner is not connected to the computer.</li>'.
				'<li>You need to run the <a href="index.php?page=Access%20Enabler">Access Enabler</a>.</li>'.
				(file_exists("/tmp/scan_file$SCANNER.pnm")?"<li>Removing <code>/tmp/scan_file$SCANNER.pnm</code> may help.</li>":'').
				'<li><code>'.$user.'</code> does not have permission to write files to the <code>'.getcwd().'/scans</code> folder.</li>'.
				'<li>You may have to <a href="index.php?page=Config">re-configure</a> the scanner.</li></ul>'.$notes,'left');
		}
		else{
			Update_Links($S_FILENAME,$PAGE);
			Update_Preview("scans/$P_FILENAME");
		}
		echo '<script type="text/javascript">if(document.scanning.scanner.childNodes.length>1)document.scanning.reset.disabled=true;</script>';
		if($ct>3)
			Print_Message("Info",'Multiple scans made, only displaying last one, go to <a href="index.php?page=Scans&amp;filter=3&amp;T2='.($startTime-1).'&T1='.($endTime+1).'">Scanned Files</a> for the rest','center');
	}
	checkFreeSpace($FreeSpaceWarn);
}
quit();
?>
