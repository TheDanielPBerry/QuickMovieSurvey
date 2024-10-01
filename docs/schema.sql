-- movie definition

CREATE TABLE movie (
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	title TEXT NOT NULL
);


-- survey definition

CREATE TABLE survey (
	id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	email TEXT NOT NULL
);

CREATE UNIQUE INDEX survey_email_IDX ON survey (email);


-- rating definition

CREATE TABLE rating (
	survey_id INTEGER NOT NULL,
	movie_id INTEGER NOT NULL,
	value INTEGER NOT NULL,
	CONSTRAINT rating_movie_FK FOREIGN KEY (movie_id) REFERENCES movie(id),
	CONSTRAINT rating_survey_FK FOREIGN KEY (survey_id) REFERENCES survey(id)
);
