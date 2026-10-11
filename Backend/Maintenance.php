<?php // (c) 2022-present The Riecoin Developers (https://riecoin.xyz/StellaPool/)

require_once('Stella.php');
$stella = new Stella($confPath);

if (isset($argv[1])) {
	$command = $argv[1];
	if ($command === 'initDatabase') {
		echo "Initializing Database...\n";
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS Users(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				registrationTime BIGINT UNSIGNED NOT NULL,
				lastLogin BIGINT UNSIGNED,
				lastSeen BIGINT UNSIGNED,
				username VARCHAR(20) NOT NULL UNIQUE);');
			echo "Created Users Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Users table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS RiecoinAddresses(
				address VARCHAR(90) PRIMARY KEY,
				userId BIGINT UNSIGNED NOT NULL,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE);');
			echo "Created the Riecoin Addresses Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Riecoin Addresses table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS Sessions(
				tokenHash BINARY(32) PRIMARY KEY,
				userId BIGINT UNSIGNED NOT NULL,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
				expiration BIGINT UNSIGNED NOT NULL);');
			echo "Created the Sessions Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Sessions table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS Tokens(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				hash BINARY(32) NOT NULL UNIQUE,
				userId BIGINT UNSIGNED NOT NULL,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
				expiration BIGINT UNSIGNED NOT NULL,
				powcRemaining DOUBLE NOT NULL,
				name VARCHAR(32) NOT NULL);');
			echo "Created the Tokens Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Tokens table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS Rounds(
				id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				heightStart INT UNSIGNED NOT NULL,
				heightEnd INT UNSIGNED,
				timeStart BIGINT UNSIGNED NOT NULL,
				timeEnd BIGINT UNSIGNED,
				blockHash CHAR(64),
				state SMALLINT UNSIGNED NOT NULL,
				finder VARCHAR(90));');
			echo "Created the Rounds Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Rounds table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS RoundScores(
				roundId INT UNSIGNED NOT NULL,
				FOREIGN KEY (roundId) REFERENCES Rounds(id) ON DELETE CASCADE,
				miner VARCHAR(90),
				points DOUBLE NOT NULL);');
			echo "Created the Round Scores Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Round Scores table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS SharesRecent(
				time BIGINT UNSIGNED NOT NULL,
				finder VARCHAR(90) NOT NULL,
				points DOUBLE NOT NULL);');
			echo "Created the Recent Shares Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Recent Shares table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS RecentStats(
				time BIGINT UNSIGNED NOT NULL,
				tokenHash BINARY(32) NOT NULL,
				valid BIGINT UNSIGNED NOT NULL,
				invalid BIGINT UNSIGNED NOT NULL,
				points DOUBLE NOT NULL);');
			echo "Created the Recent Stats Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Recent Stats table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS RiecoinMovements(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				time BIGINT UNSIGNED NOT NULL,
				value DOUBLE NOT NULL,
				userId BIGINT UNSIGNED,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
				address VARCHAR(90),
				comment VARCHAR(255) NOT NULL);');
			echo "Created the Riecoin Movements Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Riecoin Movements table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS PoWc(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				expiration BIGINT UNSIGNED NOT NULL,
				amount DOUBLE NOT NULL,
				userId BIGINT UNSIGNED NOT NULL,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE);');
			echo "Created the PoWc Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the PoWc table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS PoWcMovements(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				time BIGINT UNSIGNED NOT NULL,
				value DOUBLE NOT NULL,
				userId BIGINT UNSIGNED NOT NULL,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
				comment VARCHAR(255) NOT NULL);');
			echo "Created the PoWc Movements Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the PoWc Movements table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS Withdrawals(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				time BIGINT UNSIGNED NOT NULL,
				amount DOUBLE NOT NULL,
				userId BIGINT UNSIGNED,
				FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
				address VARCHAR(90) NOT NULL,
				state SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				txid CHAR(64),
				comment VARCHAR(255) NOT NULL);');
			echo "Created the Withdrawals Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Withdrawals table: ' . $e->getMessage();
			return;
		}
		
		try {
			$stella->database->query('CREATE TABLE IF NOT EXISTS Events(
				id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				time BIGINT UNSIGNED NOT NULL,
				ip VARCHAR(45) NOT NULL,
				userId BIGINT UNSIGNED,
				type VARCHAR(64) NOT NULL,
				details TEXT NOT NULL);');
			echo "Created the Event Log Table.\n";
		}
		catch (Exception $e) {
			echo 'Could not create the Event Log table: ' . $e->getMessage();
			return;
		}
		
		echo "Done Initializing Database.\n";
	}
	else if ($command === 'doMaintenance') {
		$now = time();
		$transactionHistoryCutoff = $now - movementsDuration;
		
		try {
			$stella->database->query('DELETE FROM Sessions WHERE expiration <= ' . $now);
			$stella->database->query('DELETE FROM Tokens WHERE expiration <= ' . $now);
			$stella->database->query('DELETE FROM PoWc WHERE expiration <= ' . $now);
		}
		catch (Exception $e) {
			echo "\nCould not delete Expired Sessions, Tokens or PoWc: " . $e->getMessage() . "\n";
		}
		
		if (roundScoresPruning > 0) {
			try {
				$currentRoundId = $stella->getCurrentRoundId();
				$stella->database->query('DELETE FROM RoundScores WHERE roundId + ' . intval(roundScoresPruning) . ' <= ' . intval($currentRoundId));
			}
			catch (Exception $e) {
				echo "\nCould not delete Old Round Scores: " . $e->getMessage() . "\n";
			}
		}
		
		try {
			$stella->database->beginTransaction();
			$stella->database->query("INSERT INTO RiecoinMovements(time, value, userId, address, comment) SELECT " . $transactionHistoryCutoff . " AS time, SUM(value) AS value, userId, NULL AS address, 'Balance at this point in time.' AS comment FROM RiecoinMovements WHERE time < " . $transactionHistoryCutoff . " AND userId IS NOT NULL GROUP BY userId");
			$stella->database->query("DELETE FROM RiecoinMovements WHERE time < " . $transactionHistoryCutoff);
			$stella->database->commit();
		}
		catch (Exception $e) {
			$stella->database->rollBack();
			echo "Could not delete Old Transactions: " . $e->getMessage() . "\n";
		}
		
		try {
			$stella->database->query("DELETE FROM PoWcMovements WHERE time < " . $transactionHistoryCutoff);
			$stella->database->query("DELETE FROM Withdrawals WHERE time < " . $transactionHistoryCutoff);
			$stella->database->query("DELETE FROM Events WHERE time < " . $now - 31556952);
		}
		catch (Exception $e) {
			echo "Could not delete Old PoWcMovements or Withdrawals: " . $e->getMessage() . "\n";
		}
	}
	else {
		echo 'Unknown Command!';
	}
}

?>
