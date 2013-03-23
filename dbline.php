<?php
/***
	File: dbline.php
	License: GPLv3
	Repo: https://github.com/oriadam/dbline	
	
	DBLine main file.
	DBLine is a quick web interface tool to MySQL DB for sql commands and queries.
	It supports all sql commands that your db allows you to.
	For a full list of features, and for feature requests, go to
	http://www.tablefield.com/dbline

	See dbline.config.php for setup details.

***/

/////////////////////////////////
//        Settings             //
// Look into dbline.config.php //
/////////////////////////////////
global $cfg;
require('dbline.config.php');

///////////////////////////////
//        INCLUDES           //
// Copied from oria.inc.php  //
///////////////////////////////
function sqlSetCollation($collation,$charset){
    if (!empty($collation)) {
      $res=mysql_query("SHOW VARIABLES LIKE '%collation\_%';");
      while ($row=mysql_fetch_row($res)){
          mysql_query("SET $row[0]='$collation';");
      }
    }
    if (!empty($charset)) {
      $res=mysql_query("SHOW VARIABLES LIKE '%character\_set\_%';");
      while ($row=mysql_fetch_row($res)){
          mysql_query("SET $row[0]='$charset';");
      }
      mysql_query("SET CHARACTER SET '$charset';");
    }
    return true;
}


///////////////////////////////
//          PROGRAM          //
///////////////////////////////
if ($cfg['session']) {
	session_start();
	if (!empty($_GET['clear']))	session_destroy();
}
?><HTML>
<head>
<?php
if ($cfg['xor']) {
	?>
	<script type="text/javascript">
		String.prototype.xor = function (key)
		{
			var res="";
			if (typeof(key)!='string' || key=='') return null;
			while(key.length<this.length) {
				key+=key;
			}
			for(i=0;i<this.length;++i)
			{
				res+=String.fromCharCode(key.charCodeAt(i)^this.charCodeAt(i));
			}
			return res;
		}
		
		String.prototype.safexor = function(keyhex)
		{
			var b='0123456789ABCDEF';
			var res="";
			if (typeof(keyhex)!='string' || keyhex=='') return null;
			while(keyhex.length<this.length*2)	keyhex+=keyhex;
			
			var z=this.length;
			for(var i=0;i<z;i++)
			{
				var cs=this.charCodeAt(i);
				var ck=(b.indexOf(keyhex.charAt(i*2))*16) + b.indexOf(keyhex.charAt((i*2)+1));
				var c=cs^ck;
				res+=b.charAt(Math.floor(c/16))+b.charAt(c%16);
			}
			return res;
		}
		
		String.prototype.safeunxor = function(keyhex)
		{
			var out='';
			while (keyhex.length<this.length) keyhex=keyhex+keyhex;
			var b='0123456789ABCDEF';
			for (var i=0;i<this.length;i=i+2) {
				var cs = (b.indexOf(this.charAt(i))*16) + b.indexOf(this.charAt(i+1));
				var ck = (b.indexOf(keyhex.charAt(i))*16) + b.indexOf(keyhex.charAt(i+1));
				out += String.fromCharCode(cs^ck);
			}
			return out;
		}
		
		String.prototype.enc = function (key)
		{
			return this.safexor(key);
			//return this.length+"|"+this.xor(key).encode64();
		}
		
	</script>
<?php
}

//decrypt
function dec($s,$k=null) {
	global $cfg;
	if (empty($k))	$k=$cfg['random'];
	if (empty($k)) die('no random');
	if (empty($s)) return $s;
	
	$out='';
	while (strlen($k)<strlen($s)) $k.=$k;
	$b='0123456789ABCDEF';
	for ($i=0;$i<strlen($s);$i+=2) {
		$cs = (strpos($b,$s{$i})*16) + strpos($b,$s{$i+1});
		$ck = (strpos($b,$k{$i})*16) + strpos($b,$k{$i+1});
		$out .= chr($cs^$ck);
	}
	return $out;
}


///////////// login script ///////////////
$loggedin = false;
// if there is no password, skip the login process
if (empty($cfg['passmd5'])) {
	$loggedin = true;
} else {

	if ($cfg['session']) {

		if ($cfg['xor']) {
			if (empty($_SESSION['rnd'])) {
				// generate random string of 200 chars presented as hexadecimal
				for ($i = 0, $z = strlen($a = '0123456789ABCDEF')-1, $s = '', $i = 0; $i < 200*2; $s .= $a{rand(0,$z)}, $i++);
				$_SESSION['rnd'] = $s;
			}
			$cfg['random']=$_SESSION['rnd'];
		}
		
		if (!empty($_SESSION['loginip']))
			if ($_SESSION['loginip'] == $_SERVER['REMOTE_ADDR'])
				$loggedin = true;

		if (!$loggedin) {
			if (!empty($_POST['p'])) {
				// unencrypt password
				$p = $_POST['p'];
				if ($cfg['xor'])
					$p = dec($p);
				// test password
				if (md5($p)==$cfg['passmd5']) {
					$_SESSION['loginip'] = $_SERVER['REMOTE_ADDR'];
					$loggedin = true;
				} else {
					sleep(mt_rand(1,5));
				}
			}
		}
	} else {//if $cfg['session']
		if (!empty($_POST['p'])) {
			// unencrypt password
			$p = $_POST['p'];
			if ($cfg['xor'])
				$p = dec($p);
			// test password
			if (md5($p)==$cfg['passmd5']) {
				$loggedin = true;
			} else {
				sleep(mt_rand(1,5));
			}
		}
	}//else $cfg['session']
}// else empty($cfg['passmd5'])
if ($cfg['xor'])
	echo '<script type="text/javascript">xorkey="'.$cfg['random'].'"</script>';

if (!$loggedin) {
	$onsubmit = "";
	if ($cfg['xor']) {
		$onsubmit .=" document.frm.p.type='hidden'; document.frm.p.value = document.frm.p.value.toString().enc(xorkey); ";
	}
	?>
	</head><body>
	<form name="frm" method="POST" onsubmit="<?php echo $onsubmit; ?>" >
		Password: <input name="p" type="password" size="20" maxlength="50">
		<input type="submit" value="go">
	</form>
	<?php
	exit;
} else { // if !$loggedin

	// connect to db
	if (!mysql_connect($cfg['dbhost'],$cfg['dbuser'],$cfg['dbpass'])) die('Error - db connect failed');
	if (!mysql_select_db($cfg['dbname'])) die('Error - db select failed');
	if (!$cfg['freetext'] && !count($cfg['preset'])) die('Nothing to do - please add preset queries, or enable free-text queries on dbline.config.php');

	$query = '';  // query to execute
	$error = '';  // error message
	$showCounter = !empty($_POST['showCounter']);

	if ($cfg['freetext'] && !empty($_POST['txt'])) {
		$query = $_POST['txt'];
		if ($cfg['xor'])
			$query = dec($query);
	}

	if (!empty($_POST['q'])) {
		if (array_key_exists($_POST['q'],$cfg['preset'])) {
			$query = $cfg['preset'][$_POST['q']];
		} else {
			$error = 'Preset query not found';
		}
	}

?>
<style type="text/css">
	tr.h { background:#efefef; }
	th   { white-space:nowrap; }
	.tr0 { background:#fefefe; }
	.tr1 { background:#ffffff; }
	td.c { font-family:Courier New; width:2%; }
	.txt { display:block; }
	form { display:block; }
	.err { display:block; color:#000; background-color:#faa; border:1px solid #500; }
	.ok  { display:block; color:#000; background-color:#afa; border:1px solid #050; }
	pre  { display:block; text-align:left; }
</style>
</head>
<body>
<?php
	$onsubmit = "";
	if ($cfg['xor']) {
		$onsubmit .=" document.frm.txt.value = document.frm.thetext.value.toString().enc(xorkey); ";
	} else {
		$onsubmit .=" document.frm.txt.value = document.frm.thetext.value; ";
	}
?>
<form name="frm" method="POST" onsubmit="<?php echo $onsubmit; ?>" >
<center>
<input type="hidden" name="txt">
<?php
	// define stripos when missing
	if (!function_exists('stripos')){
		function stripos($haystack,$needle,$offset=0){
			return strpos(strtolower($haystack),strtolower($needle),$offset);
		}
	}

	// free text
	if ($cfg['freetext']) {
		?><textarea class="txt" name="thetext" cols="3" style="width:90%"><?php echo $query; ?></textarea><?php
	}
	
	// title repeat
	if (!isset($_POST['titlerepeat']) || !is_numeric($_POST['titlerepeat'])) {
		$titlerepeat = 1; // default = once
	} else {
		$titlerepeat = 1*$_POST['titlerepeat'];
	}
	$titleoptions = array(0,1,2,10,20,50,100);
	echo ' Title repeat <select name="titlerepeat" size="1">';
	foreach ($titleoptions as $option) {
		echo "<option ".($option==$titlerepeat?' selected checked ':'').">$option</option>";
	}
	echo '</select>';

	// output format
	$formatoptions = array( 
		'html' => 'HTML Table',
		'csv' => 'CSV Comma separated values (,)',
		'tsv' => 'Tab separated values (      )',
		'ssv' => 'Semicolon separated values (;)',
		'psv' => 'Pipe separated values (|)',
		' sv' => 'Space separated values ( )');
	// TBD: xml, wml
	if (empty($_POST['format'])) {
		$format = 'html'; // default = html
	} else {
		$format = $_POST['format'];
		if (!array_key_exists($format,$formatoptions)) {
			$error = 'Unknown format!';
			$format = 'html';
		}
	}
	echo ' Format <select name="format" size="1">';
	foreach ($formatoptions as $option=>$text) {
		echo "<option value=\"$option\" ".($option==$format?' selected checked ':'').">$text</option>";
	}
	echo '</select>';
	
	// preset queries
	if (count($cfg['preset'])) {
		echo ' <SELECT name="q"><option value="" selected>--preset commands--</option>';
		foreach ($cfg['preset'] as $k=>$v) {
			$k=htmlentities($k);
			$v=htmlentities($v);
			echo "<option title=\"$v\">$k</option>";
		}
		echo '</SELECT>';
	}
	
	// show counter field column #
	echo '<label><input type="checkbox" name="showCounter" '.($showCounter?'checked':'').' >Row counter</label>';

	// on no-session mode, require entering the password per every post
	if (!$cfg['session'])
		echo '<input type="password" name="p" value="'.$_POST['p'].'">';

	// submit button
	echo '<input type="submit">';
	
	echo '</form>';

	// make sure a 'where' is included when necessary
	if ($cfg['where'] && !empty($query)
	&& ((stripos(trim($query),'select')===0 && stripos(trim($query),'from')>0) || stripos(trim($query),'update')===0 || stripos(trim($query),'delete')===0) 
	&& (stripos($query,'where')===false) ) {
		$error = 'Please add a WHERE clause to your query';
		$query = '';
	}

	// error message
	if (!empty($error))
		echo "<div class=\"err\">$error</div>";

	if (!empty($query)) {
		$sql = $query;
		if ($cfg['echo']) {
			$sqltxt = str_replace('<','&lt;',$sql);
			echo "<div class=\"code\">$sqltxt</div>";
		}
		$rows = mysql_query($sql);
		$sqlerr = mysql_error();
		if ($rows===false) {
			$count = 0;
			$error = 'query failed';
			$result = false;
		} else if ($rows===true) {
			$count = mysql_affected_rows();
			$result = false;
		} else {
			$count = mysql_num_rows($rows);
			$result = true;
		}
		if ($sqlerr) {
			$sqlerr = str_replace('<','&lt;',$sqlerr);
			echo "<div class=\"err\">$sqlerr</div>";
		} else {
			echo "<div class=\"ok\">$count rows</div>";
		}
?></center>
<?php
		// show result
		if ($result) {
			if ($format == 'html') {
				$i = 0;
				echo '<table>';
				
				// set title row in $th
				$th = '<tr class="h">';
				if ($showCounter) 
					$th.='<th>#</th>';
				while ($i < mysql_num_fields($rows)) {
					$meta = mysql_fetch_field($rows, $i);
					$th.='<th>'.$meta->name.'</th>';
					$i++;
				}
				$th.="</tr>\n";

				// display title row (unless title-repeat is set to 0)
				if ($titlerepeat>0 && $titlerepeat!=2)
					echo $th;

				// show all rows
				$count=0;
				while($row=mysql_fetch_assoc($rows)) {
					$count++;
					// repeat title row
					if ($titlerepeat==2 || ($titlerepeat>1 && $count%$titlerepeat==0))
						echo $th;
					// row
					echo '<tr class="tr'.($count%2).'">';
					// show counter field column
					if ($showCounter)
						echo '<td class="c">'.$count.'</td>';
					// show the result row
					foreach($row as $key=>$value)
						echo '<td>'.htmlentities($value,ENT_COMPAT,'UTF-8').'</td>';
					echo "</tr>\n";
				}
				echo "</table>\n";
			}// if $format=='html'

			if ($format == 'csv' || $format == 'ssv' || $format == 'tsv' || $format == 'psv' || $format == ' sv') {
				// set separator character
				if ($format == 'csv') $sep = ',';
				if ($format == 'ssv') $sep = ';';
				if ($format == 'tsv') $sep = "\t";
				if ($format == 'psv') $sep = '|';
				if ($format == ' sv') $sep = ' ';
				// set newline character
				$nl = "\n";
				// set quote character
				$q = '"';
				// set escaped quote character
				$qq = '""';
				// always quote the values?
				$alwaysq = false;

				echo '<pre>';
				
				// set title row in $th
				$th = '';
				if ($showCounter)
					$th.='#';
				$fields = 0;
				while ($fields < mysql_num_fields($rows)) {
					if ($th!='') $th.=$sep;
					$meta = mysql_fetch_field($rows, $fields);
					$th.= $meta->name;
					$fields++;
				}
				$th .= $nl;
				// display title row (unless title-repeat is set to 0)
				if ($titlerepeat>0)
					echo $th;

				// show all rows
				$count=0;
				while($row=mysql_fetch_assoc($rows)) {
					$count++;
					// show counter field column
					if ($showCounter)
						echo $count.$sep;
					// show the result row
					$col=0; // current column counter
					foreach($row as $key=>$value) {
						if ($alwaysq || strpos($value,$nl)!==false || strpos($value,$sep)!==false || strpos($value,$q)!==false)
							echo $q.str_replace($q,$qq,$value).$q;
						else
							echo $value;
						// don't show separator at the end of the line
						$col++;
						if ($col<$fields)
							echo $sep;
					}
					echo $nl;
				}
				echo '</pre>';
			}// if $format=='csv' || ...
		}//if $count
	}//if !empty($query)
} // else !$loggedin
