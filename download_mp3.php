<?php
if (isset($_GET['hash'])) {
    $hash = $_GET['hash'];
	$file_path = "/var/www/html/tts.computer/data/{$hash}.mp3";
	
	header('Content-Type: audio/mp3');
	readfile($file_path);
}