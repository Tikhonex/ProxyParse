<?php
include_once('simple_html_dom.php');
	class SDbBase {
		static function DBCon(){
			$link=mysqli_connect(localhost, root, "", test_task)
				or die("Error: ".mysqli_error($link));
			if(!mysqli_set_charset($link,"utf8")){
				printf("Error: ".mysqli_error($link));
			} 
			return $link;
		}
	}
	class SDb extends SDbBase {
		static function bd_insert($link, $ip, $port, $speed, $type, $anonymity, $dateUpdate, $dateCreate) {
			$sql_check = "SELECT * FROM proxy_list WHERE ip = '" . $ip . "' AND port = " . $port;
			$check = mysqli_query($link, $sql_check);
			if (mysqli_num_rows($check) == 0) {
				$sql = "INSERT INTO proxy_list (`ip`, `port`, `speed`, `type`, `anonymity`, `dateUpdate`, `dateCreate`) VALUES ('%s', '%d', '%s', '%s', '%s', '%d', '%d')"; 
				$query=sprintf($sql,mysqli_real_escape_string($link,$ip),mysqli_real_escape_string($link,$port),mysqli_real_escape_string($link,$speed),mysqli_real_escape_string($link,$type),mysqli_real_escape_string($link,$anonymity),mysqli_real_escape_string($link,$dateUpdate),mysqli_real_escape_string($link,$dateCreate)); 
				$result=mysqli_query($link,$query);
				if(!$result) 
					die(mysqli_error($link)); 
			} else {
				SDb::bd_update($link, $ip, $port, $dateUpdate);
			}
		}
		static function bd_update($link, $ip, $port, $dateUpdate) {
			$sql = "UPDATE proxy_list SET dateUpdate = '" . $dateUpdate . "' WHERE ip = '" . $ip . "' AND port = " . $port;
				$result = mysqli_query($link, $sql);
				if(!$result) 
					die(mysqli_error($link)); 
		}
		static function bd_read($link) {
      $sql = "SELECT * FROM (SELECT `port`,COUNT(*) as `kol`, GROUP_CONCAT(`ip`SEPARATOR ', ') as `ip` FROM proxy_list GROUP BY `port` ORDER BY COUNT(*) DESC) as y WHERE `kol`<21";
			$result=mysqli_query($link,$sql);
			if(!$result) 
				die(mysqli_error($link));
			
			$n=mysqli_num_rows($result); 
			$list=array(); 
			for($i=0;$i<$n;$i++){ 
				$row=mysqli_fetch_assoc($result); 
				$list[]=$row; 
			} 
			return $list;
		}
	}
	class Curl{
		static function curlContent($url){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвращает строку
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // редирект
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.1) Gecko/2008070208');
			$out = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);
			curl_close($ch);
			return $out;
		}
	}
	
	class ProxyParser {
		static function unixtime_forman($string) {
			$date = new DateTime();
			if (preg_match("/мин/i", $string)) { //возвращаем минуты
			   $result = $date->getTimestamp() - preg_replace( '/[^0-9]/', '', $string) * 60;
			   return $result;
			} elseif (preg_match("/сек/i", $string)) { //возвращаем секунды
				(int)$result = $date->getTimestamp() - preg_replace( '/[^0-9]/', '', $string);
				return $result;
			}
		}
		static function Parse() {
			$link = SDb::DBCon();
			$date = new DateTime();
			for ($page = 0; $page < 2; $page++) {
				$url = "http://hideme.ru/proxy-list/?start=" . $page * 64 . "#list";
				$out = Curl::curlContent($url);
				$arr1 = [];
				$html = str_get_html($out);
				if (count($html->find('tr'))) {
					foreach ($html->find('tr') as $div) {
						if ($i < 100) {
							if ($div->find('td', 0) != null){
								SDb::bd_insert($link, strip_tags($div->find('td', 0)), strip_tags($div->find('td', 1)), iconv("Windows-1251", "UTF-8", strip_tags($div->find('td', 3))), iconv("Windows-1251", "UTF-8", strip_tags($div->find('td', 4))), iconv("Windows-1251", "UTF-8", strip_tags($div->find('td', 5))), $date->getTimestamp(), ProxyParser::unixtime_forman(iconv("Windows-1251", "UTF-8",strip_tags($div->find('td', 6)))));
								$i++;
							}
						}
					}
			
				}
				unset($arr1[0]);
			}
		}
	}
?>