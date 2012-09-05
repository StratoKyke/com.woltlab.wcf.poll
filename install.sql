DROP TABLE IF EXISTS wcf1_poll;
CREATE TABLE wcf1_poll (
	pollID INT(10) NOT NULL PRIMARY KEY,
	objectTypeID INT(10) NOT NULL,
	objectID INT(10) NOT NULL DEFAULT 0,
	question VARCHAR(255) DEFAULT '',
	time INT(10) NOT NULL DEFAULT 0,
	endTime INT(10) NOT NULL DEFAULT 0,
	isChangeable TINYINT(1) NOT NULL DEFAULT 0,
	isPublic TINYINT(1) NOT NULL DEFAULT 0,
	sortByVotes TINYINT(1) NOT NULL DEFAULT 0,
	resultsRequireVote TINYINT(1) NOT NULL DEFAULT 0,
	maxVotes INT(10) NOT NULL DEFAULT 1,
);

DROP TABLE IF EXISTS wcf1_poll_option;
CREATE TABLE wcf1_poll_option (
	optionID INT(10) NOT NULL PRIMARY KEY,
	pollID INT(10) NOT NULL,
	optionValue VARCHAR(255) NOT NULL DEFAULT '',
	votes INT(10) NOT NULL DEFAULT 0,
	showOrder INT(10) NOT NULL DEFAULT 0
);

DROP TABLE IF EXISTS wcf1_poll_option_vote;
CREATE TABLE wcf1_poll_option_vote (
	optionID INT(10) NOT NULL DEFAULT 0,
	userID INT(10) NULL,
	ipAddress VARCHAR(39) NOT NULL DEFAULT ''
);

ALTER TABLE wcf1_poll ADD FOREIGN KEY objectTypeID REFERENCES wcf1_object_type (objectTypeID) ON DELETE CASCADE;

ALTER TABLE wcf1_poll_option ADD FOREIGN KEY pollID REFERENCES wcf1_poll (pollID) ON DELETE CASCADE;

ALTER TABLE wcf1_poll_option_vote ADD FOREIGN KEY optionID REFERENCES wcf1_poll_option (optionID) ON DELETE CASCADE;
ALTER TABLE wcf1_poll_option_vote ADD FOREGIN KEY userID REFERENCES wcf1_user (userID) ON DELETE CASCADE;