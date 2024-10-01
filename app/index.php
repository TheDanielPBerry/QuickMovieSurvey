<?php
$answers = [
	'Liked',
	'Would Like to Watch',
	'Not Interested in Watching',
	'Disliked',
];
function GetDBContext() 
{
	$db = new SQLite3('../db/survey.db', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
	$db->enableExceptions();
	return $db;
}
function GetSurveyQuestions($db)
{
	$result = $db->query("SELECT id, title FROM movie;");
	if($result) {
		$return = [];
		while($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$return[$row['id']] = $row['title'];
		}
		return $return;
	}
}
function ValidateMovieRatings($ratings)
{
	global $answers;
	if(empty($ratings)) {
		return "No ratings submitted";
	}
	if(!is_array($ratings)) {
		return "Ratings input must be an array";
	}
	if(count($ratings) < 50) {
		return "Please rate at least 50 movies before submitting";
	}
	foreach($ratings as $movieId => $rating) {
		if(!is_numeric($movieId) || intval($movieId) < 0 || intval($movieId) > 101) {
			return "Invalid movie '$movieId' in ratings";
		}
		if(!is_numeric($rating) || !in_array($rating, array_keys($answers))) {
			return "Invalid rating '$rating' given for a movie";
		}
	}
	return false;
}
function ValidateEmail($email) {
	if(empty($email) || preg_match("/^\S+\@(\S+\.)+\S{2,4}$/", $email) === 0) {
		return "Please submit a valid email address";
	}
	return false;
}
function GetSurvey($db, $email)
{
	$stmt = $db->prepare("SELECT id, email FROM survey WHERE email=:email");
	$stmt->bindValue(':email', $email, SQLITE3_TEXT);
	if($result = $stmt->execute()) {
		return $result->fetchArray(SQLITE3_ASSOC);
	}
	return false;
}
function CreateSurvey($db, $email)
{
	$stmt = $db->prepare("INSERT INTO survey (email) VALUES (:email)");
	$stmt->bindParam(':email', $email);
	if($result = $stmt->execute()) {
		return GetSurvey($db, $email);
	}
	return false;
}
function DeleteRatings($db, $surveyId, $ratings)
{
	$errors = [];
	foreach($ratings as $movieId => $rating) {
		$sql = "DELETE FROM rating WHERE survey_id=:survey AND movie_id=:movie;";
		$stmt = $db->prepare($sql);
		if($stmt) {
			$stmt->bindValue(':survey', $surveyId);
			$stmt->bindValue(':movie', $movieId);
			if(!$result = $stmt->execute()) {
				$errors[] = "Could not overwrite previous answer for #$movieId";
			}
		} else {
			return ["Could not submit survey"];
		}
	}
	return $errors;
}

$errors = [];
$db = GetDBContext();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if($error = ValidateEmail($_POST['email'] ?? null)) {
		$errors[] = $error;
	} else {
		if($error = ValidateMovieRatings($_POST['movieRating'] ?? null)) {
			$errors[] = $error;
		} else {
			$ratings = $_POST['movieRating'];	//Already been validated
			$email = $_POST['email'];	//Already been validated
			$db = GetDBContext();
			if(!$survey = GetSurvey($db, $email)) {
				$survey = CreateSurvey($db, $email);
			}
			if(empty($survey)) {
				$errors[] = 'Could not create a new survey';
			} else {
				if($err = DeleteRatings($db, $survey['id'], $ratings)) {
					$errors = $err;
				} else {
					$values = [];
					$params = [];
					foreach($ratings as $movieId => $rating) {
						$values[] = "(:movie$movieId, :survey$movieId, :rating$movieId)";
						$params["movie$movieId"] = $movieId;
						$params["survey$movieId"] = $survey['id'];
						$params["rating$movieId"] = $rating;
					}
					$values = implode(',', $values);
					$sql = "INSERT INTO rating (movie_id, survey_id, value) VALUES $values;";

					$stmt = $db->prepare($sql);
					foreach($params as $paramId => $value) {
						$stmt->bindValue(":$paramId", $value, SQLITE3_INTEGER);
					}
					$result = $stmt->execute();
					if(!$result) {
						$errors[] = 'Could not save survey results. Please try again.';
					}
				}
			}
		}
	}
}

if(!empty($errors) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
	$movies = GetSurveyQuestions($db);
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Movie Preferences | CPSC 8740</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="icon" type="image/x-icon" href="/img/favicon.ico">
	<link rel="stylesheet" href="css/style.css" />
</head>
<body>
	<? if(empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST') { ?>
		<h2>
			Thank you for submitting your answers to the survey
		</h2>
	<? } else { ?>
		<h2>
			Please Fill Out the Email Field and at Least 50 Film Ratings
		</h2>
		<? foreach($errors as $err) { ?>
			<div class="error">
				* <?=$err ?>
			</div>
		<? } ?>
		<form action="/" method="POST">
			<div class="flex-container form-group">
				<label for="email">*Email: </label>
				<input type="email" id="email" name="email" placeholder="josephdirt@clemson.edu" value="<?=$_POST['email'] ?? '' ?>" />
			</div>
			<? foreach($movies as $movieIndex => $movie) { ?>
				<hr/>
				<div class="form-group">
					<h4>
						<?=$movieIndex ?>. <?=$movie ?>
					</h4>
					<div class="flex-container">
						<? foreach($answers as $answerIndex => $answer) { 
							$answerId = "$movieIndex-$answerIndex";
						?>
							<label for="ans-<?=$answerId ?>">
								<input type="radio" 
									name="movieRating[<?=$movieIndex ?>]"
									id="ans-<?=$answerId ?>"
									value="<?=$answerIndex ?>"
									<? if(isset($_POST['movieRating'][$movieIndex]) && $_POST['movieRating'][$movieIndex] == $answerIndex) { ?>
										checked
									<? } ?>
								/>
								<?=$answer ?>
							</label>
						<? } ?>
					</div>
				</div>
			<? } ?>

			<button type="submit">
				Submit Survey
			</button>
		</form>
	<? } ?>

</body>
</html>
