<?
require_once("libs/class.php");
if(isset($_GET['action']) == "parse") {
  ProxyParser::Parse();
  $link = SDb::DBCon();
	$query= SDb::bd_read($link);
	echo "<table border=1px solid black>";
	foreach($query as $a){
		echo "<tr><td>".$a['port']."</td><td>".$a['kol']."</td><td>".$a['ip']."</td></tr>";
	}
	echo "</table>";
}
?>