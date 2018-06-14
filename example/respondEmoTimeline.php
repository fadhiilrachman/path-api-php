<?php

// This is example for auto respond timeline with emoticon
// use at your own risk!

require_once 'vendor\autoload.php';
<<<<<<< HEAD
use \FadhiilRachman\Path;
=======
use \FadhiilRachman\Path As Path;
>>>>>>> 4ed79e8283e4b9cca92d18efd1cf7f973a3483b1

$email='YOUR_PATH_EMAIL';
$password='YOUR_PATH_PASSWORD';
$limit=50;
$type=mt_rand(1,5);

$path = new \FadhiilRachman\Path\Path($email, $password);

try {
	$relogin=false;
	if (isset($_GET['relogin'])) {
		$relogin=true;
	}
	$login = $path->login($relogin);
	if($login) {
		$feedHome = $path->feedHome($limit, false)["moments"];
		if($feedHome=='') {
			$path->login(true);
		}
		$userId = $path->user_id;
		for ($i=0; $i < count($feedHome); $i++) {
			$data=$feedHome[$i];
			$moment_id=$data["id"];
			if($moment_id) {
				$dir = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR;
				if (!file_exists( 'data' )) {
					mkdir($dir , 0777);
				}
				$log=$dir . $userId . '_respondEmoTimeline.log';
				$log_data=file_get_contents($log);
				$log_data=explode("\n", $log_data);
				if(!in_array($moment_id, $log_data)) {
					/*
						"awake" || "profile_photo" || "friend"
					*/
					if($data["ambient"]["subtype"] == "asleep") {
						$path->momentAddEmotion($moment_id, 'sheep', 'sheep');
					} else {
						$addEmo=$path->momentAddEmotion($moment_id, $type);
						if($addEmo) {
						}
					}
						echo "[SUCCESS] " . $moment_id . "\n";
						file_put_contents($log, $moment_id . "\n", FILE_APPEND);
				} else {
					echo "[ALREADY] " . $moment_id . "\n";
				}
			}
		}
	}
} catch(PathException $e) {
	// if error, display the message and code
	echo 'Error message: ' . $e->getMessage() . "\n";
	echo 'Error code: ' . $e->getCode();
}
