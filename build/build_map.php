<?php
	include('catalog.php');
	include('catalog_names.php');
	include('catalog_vars.php');

	# load text mappings
	$lines = file('catalog_text.txt');
	$text = array();
	foreach ($lines as $line){
		$line = trim($line);
		if (strlen($line)){
			$bits = preg_split('!\s+!', $line, 2);
			$text[$bits[0]] = $bits[1];
		}
	}

	$out = array();
	$out_unis = array();

	foreach ($catalog as $row){

		$img_key = StrToLower(encode_points($row['unicode']));

		$shorts = $catalog_names[$img_key];
		if (is_array($shorts)){
			$short = $shorts[0];
		}else{
			$shorts = array();
			$short = null;
		}

		$vars = $catalog_vars[$img_key];
		if (!is_array($vars)) $vars = array();
		foreach ($vars as $k => $v) $vars[$k] = StrToUpper($v);

		$out[] = array(
			'name'		=> $row['char_name']['title'],
			'unified'	=> encode_points($row['unicode']),
			'variations'	=> $vars,
			'docomo'	=> encode_points($row['docomo'  ]['unicode']),
			'au'		=> encode_points($row['au'      ]['unicode']),
			'softbank'	=> encode_points($row['softbank']['unicode']),
			'google'	=> encode_points($row['google'  ]['unicode']),
			'image'		=> $img_key.'.png',
			'sheet_x'	=> 0,
			'sheet_y'	=> 0,
			'short_name'	=> $short,
			'short_names'	=> $shorts,
			'text'		=> $text[$short],
		);

		$out_unis[$img_key] = 1;
	}


	#
	# were there any codepoints we have an image for, but no data for?
	#

	echo "Finding extra emoji from UCD: ";

	foreach ($catalog_names as $uid => $names){
		if ($uid == '_') continue;

		if (!$out_unis[$uid]){
			echo  '.';
			$out[] = build_character_data($uid, $names);
		}
	}
	echo " DONE\n";


	function build_character_data($img_key, $short_names){

		global $text;

		$uni = StrToUpper($img_key);

		$line = shell_exec("grep -e ^{$uni}\\; UnicodeData.txt");
		list($junk, $name) = explode(';', $line);

		$row = array(
			'name'		=> $name,
			'unified'	=> $uni,
			'docomo'	=> '',
			'au'		=> '',
			'softbank'	=> '',
			'google'	=> '',
			'image'		=> $img_key.'.png',
			'sheet_x'	=> 0,
			'sheet_y'	=> 0,
			'short_name'	=> $short_names[0],
			'short_names'	=> $short_names,
			'text'		=> $text[$short],		
		);

		return $row;
	}


	#
	# sort everything into a nice order
	#

	foreach ($out as $k => $v){
		$out[$k]['sort'] = str_pad($v['unified'], 20, '0', STR_PAD_LEFT);
	}

	usort($out, 'sort_rows');

	foreach ($out as $k => $v){
		unset($out[$k]['sort']);
	}

	function sort_rows($a, $b){
		return strcmp($a['sort'], $b['sort']);
	}


	#
	# assign positions
	#

	$y = 0;
	$x = 0;
	$num = ceil(sqrt(count($out)));

	foreach ($out as $k => $v){
		$out[$k]['sheet_x'] = $x;
		$out[$k]['sheet_y'] = $y;
		$y++;
		if ($y == $num){
			$x++;
			$y = 0;
		}
	}


	#
	# write map
	#

	echo "Writing map: ";

	$fh = fopen('../emoji.json', 'w');
	fwrite($fh, json_encode($out));
	fclose($fh);

	echo "DONE\n";


	function encode_points($points){
		$bits = array();
		if (is_array($points)){
			foreach ($points as $p){
				$bits[] = sprintf('%04X', $p);
			}
		}
		return implode('-', $bits);
	}
