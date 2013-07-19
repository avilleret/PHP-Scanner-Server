<?php
	if(!isset($langs)){
		$langs=findLangs();
	}
	$LANGS=json_decode(file_get_contents('res/langs.json'));
	for($i=0,$stp=count($langs);$i<$stp;$i++){
		$lang=$langs[$i];
		$Lang=html(!isset($LANGS->{$lang})?$lang:$LANGS->{$lang});
		echo "<option value=\"$lang\"".($lang=='eng'?' selected="selected" ':'').">$Lang</option>";
	}
?>
