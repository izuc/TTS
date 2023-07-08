<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, If-Modified-Since");

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

include('vendor/autoload.php');

function parse_phonemes($lines) {
    $phonemes = [];
    foreach($lines as $i => $line) {
        if(substr($line, 0, 9) === 'Phoneme: ') {
            // Extract the phoneme
            preg_match("/Phoneme: (.*) global start time:/", $line, $matches);
            $phoneme = $matches[1];

            // Extract the global start time
            preg_match("/global start time: (.*) ms, global end time:/", $line, $matches);
            $start_time = $matches[1];

            // Extract the global end time
            preg_match("/global end time: (.*) ms/", $line, $matches);
            $end_time = $matches[1];

            // Add the phoneme and its start and end times to the array
            $phonemes[] = [$phoneme, $start_time, $end_time];
        }
    }
    return $phonemes;
}

if (isset($_GET['text'])) {
    $text = urldecode($_GET['text']);
    $text = preg_replace("/[^a-zA-Z0-9\s\\.\\,]/", "", $text);
    $hash = md5($text);
	
	$file_name = "$hash.txt";
	$folder_path = "/var/www/html/tts.computer/data";
	
    $text_file = fopen($folder_path.'/'.$file_name, "wb");
    fwrite($text_file, $text);
    fclose($text_file);
	
	if (!(file_exists('/var/www/html/tts.computer/data/'.$hash.'.wav') && file_exists('/var/www/html/tts.computer/data/'.$hash.'.log') && file_exists('/var/www/html/tts.computer/data/'.$hash.'.mp3'))) {
		shell_exec('HOME=/var/www/home /var/www/mimic3/.venv/bin/mimic3 --debug --voice en_US/vctk_low --length-scale 2.0 < /var/www/html/tts.computer/data/'.$hash.'.txt > /var/www/html/tts.computer/data/'.$hash.'.wav 2> /var/www/html/tts.computer/data/'.$hash.'.log');
		
		shell_exec("/usr/bin/ffmpeg -i /var/www/html/tts.computer/data/${hash}.wav -vn -ar 44100 -ac 2 -b:a 192k /var/www/html/tts.computer/data/${hash}.mp3;");
	}
	
	$output = file_get_contents('/var/www/html/tts.computer/data/'.$hash.'.log');
	
    $lines = explode("\n", $output);
	
	$id = NULL;
	$lines = explode("\n", $output);
	foreach ($lines as $line) {
		if (strpos($line, "Phonemes:") !== false) {
			// Extract the ID from the line
			preg_match("/Phonemes: (.*)/", $line, $matches);
			$id = basename($matches[1], ".txt"); // Extract the filename without extension
			break;
		}
	}
	
    $folder_path = "/var/www/html/tts.computer/data";
    $anno_file = "$folder_path/$hash.anno";
    
    $file = fopen($anno_file, 'w');
	
	$phonemes_data = array();
	$total_duration = 0;
	
	$phoneme_file = "/var/www/html/tts.computer/phonemes/$id.txt";
	$phoneme_file_contents = file_get_contents($phoneme_file);
	$phoneme_data = explode("\n", $phoneme_file_contents);
	$parse_phonemes = parse_phonemes($phoneme_data);
	

	foreach($parse_phonemes as $phoneme_info) {
		list($phoneme, $start_time, $end_time) = $phoneme_info;

		$duration = $end_time - $start_time;

		$total_duration += $duration; 

		$phonemes_data[] = ['phoneme' => $phoneme, 'start' => $start_time, 'end' => $end_time, 'duration' => $duration];
	}

    $getID3 = new getID3;
    $file_path = "{$folder_path}/{$hash}.mp3";
    $id3_data = $getID3->analyze($file_path);
    $playtime = isset($id3_data['playtime_seconds']) ? (int)($id3_data['playtime_seconds'] * 1000) : 0;
    $duration_ratio = $playtime / (($total_duration > 0) ? $total_duration : 1);
	$phonemes = array();
	
    foreach($phonemes_data as $phoneme_info) {
        $start_time = $phoneme_info['start'] * $duration_ratio;
        $end_time = $phoneme_info['end'] * $duration_ratio;

        fwrite($file, "phn $start_time $end_time {$phoneme_info['phoneme']}\n");
        array_push($phonemes, array($start_time, $end_time, $phoneme_info['phoneme']));
    }

    fclose($file);
	
	$lips = array(
		'i' => 3,
		'y' => 3,
		'ɨ' => 4,
		'ʉ' => 4,
		'ɯ' => 5,
		'u' => 4,
		'ɪ' => 2,
		'ʏ' => 3,
		'ʊ' => 4,
		'e' => 2,
		'ø' => 2,
		'ɘ' => 3,
		'ɵ' => 4,
		'ɤ' => 5,
		'o' => 5,
		'ɛ' => 2,
		'œ' => 2,
		'ɜ' => 4,
		'ɞ' => 4,
		'ʌ' => 4,
		'ɔ' => 5,
		'æ' => 2,
		'ɐ' => 6,
		'a' => 7,
		'ɶ' => 7,
		'ɑ' => 7,
		'ɒ' => 7,
		'm' => 1,
		'ɱ' => 1,
		'n' => 1,
		'ɳ' => 1,
		'ŋ' => 4,
		'ɴ' => 5,
		'p' => 1,
		'b' => 1,
		't' => 2,
		'd' => 2,
		'ʈ' => 2,
		'ɖ' => 2,
		'c' => 2,
		'ɟ' => 2,
		'k' => 5,
		'ɡ' => 5,
		'g' => 5,
		'q' => 6,
		'ɢ' => 6,
		'ʡ' => 7,
		'ʔ' => 5,
		'p͡f' => 1,
		'b͡v' => 1,
		't̪͡s' => 2,
		't͡s' => 2,
		'd͡z' => 2,
		't͡ʃ' => 10,
		'd͡ʒ' => 10,
		'ʈ͡ʂ' => 10,
		'ɖ͡ʐ' => 10,
		't͡ɕ' => 10,
		'd͡ʑ' => 10,
		'k͡x' => 8,
		'ɸ' => 2,
		'β' => 2,
		'f' => 2,
		'v' => 2,
		'θ' => 2,
		'ð' => 2,
		's' => 2,
		'z' => 2,
		'ʃ' => 3,
		'ʒ' => 3,
		'ʂ' => 10,
		'ʐ' => 10,
		'ç' => 3,
		'x' => 8,
		'ɣ' => 7,
		'χ' => 8,
		'ʁ' => 8,
		'ħ' => 7,
		'h' => 7,
		'ɦ' => 7,
		'w' => 2,
		'ʋ' => 2,
		'ɹ' => 10,
		'ɻ' => 10,
		'j' => 2,
		'ɰ' => 5,
		'ⱱ' => 2,
		'ɾ' => 10,
		'ɽ' => 10,
		'ʙ' => 10,
		'r' => 10,
		'ʀ' => 10,
		'l' => 10,
		'ɫ' => 10,
		'ɭ' => 10,
		'ʎ' => 10,
		'ʟ' => 10,
		'ə' => 5,
		'ɚ' => 5,
		'ɝ' => 5,
		'ɹ̩' => 10,
		'#' => 0,
	);

	$words = array();
	$sound_count = 0;
	
	//0 - closed lips normal
	//1 - mouth open slightly.
	//2 - mouth open more showing teeth and tongue.
	//3 - mouth open higher than 2 showing teeth and tongue.
	//4 - mouth open larger than 3 showing teeth and tongue higher.
	//5 - mouth open the same as 4 showing teeth and tongue lower.
	//6 - mouth bottom lip closing, same top lip as 5, showing teeth and tongue lower.
	//7 - mouth bottom lip closing more, same top lip as 6, showing top teeth and no tongue.
	//8 - mouth lips are closed.
	//9 - mouth lips slightly open.
	//10 - mouth lips closed together, like a kiss.
	
	$type = 0;
	foreach ($phonemes as $i=>$phoneme) {
		$start = $phoneme[0]; 
		$end = $phoneme[1];
		
		if (isset($phonemes[$i - 1])) {
			$start = $phonemes[$i - 1][1];
		}

		$duration = $end - $start;
		
		$sounds = array();
		
		if (isset($phoneme[2])) {
			$phonemeKey = $phoneme[2];
			
			if ($phonemeKey !== null) {

				$value = isset($lips[$phonemeKey]) ? $lips[$phonemeKey] : 0;
				
				if ($phonemeKey != '^' && $phonemeKey != '_' && $phonemeKey != '$') {
					array_push($sounds, array(
						  'phoneme' => $phonemeKey, 
						  'shape' => $value, 
						  'start' => $start, 
						  'end' => $end,
						  'duration' => $duration,
					));
				}
				$sound_count += count($sounds);
				array_push($words, $sounds);
			}
		}
	}
	
	$i = 0;
	$total_time = $sound_count;
	if ($total_time < ($playtime)) {
		$difference = ($playtime - $total_time);
		if (isset($words[$i -1])) {
			$previous_array = $words[$i -1];
			for ($j = 1; $j < $difference; $j++) {
				array_push($previous_array, end($words[$i -1]));
			}
			$words[$i -1] = $previous_array;
		}
	}

	$frame = 0;
	
	$lip_string = array();

	foreach ($words as $word) {
		foreach ($word as $sound) {
			$lip_string[$frame] = $sound;
			$frame++;
		}
	}
	
	$response = [
		'comment' => array('lip_string' => $lip_string, 'audio_duration' => ($playtime / 1000)),
		'audio' => $hash
	];

	header('Content-Type: application/json');
	echo json_encode($response);

	exit();
}
?>