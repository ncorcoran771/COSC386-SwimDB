-- User and admin are essentially rainbow tables for storing and checking against
-- previously hashed passwords

CREATE TABLE Admin (
    name varchar(20),
    adminID int,
    password varchar(64) -- Should be hashed
);

-- Upon creation of this, we could have the user enter their specific id from the site
-- Otherwise, we just use some other value
CREATE TABLE User (
    name varchar(20),
    userID int, --could be a swimmer ID
    password varchar(64) -- Should be hashed

);

CREATE TABLE Conference (
    name varchar(20),
    state varchar(2),
    primary key (name, state)
);

CREATE TABLE Team (
    teamName varchar(20),
    location varchar(20),
    confName varchar(20),
    confState varchar(2),
    foreign key (confName, confState) references Conference(name, state),
    primary key (teamName)
);

CREATE TABLE Swimmer (
    name varchar(20),
    powerIndex int,
    gender char,
    swimmerID int,
    hometown varchar(20),
    team varchar(20),
    foreign key (team) references Team(teamName),
    primary key (swimmerID)
);

CREATE TABLE Meet (
    meetName varchar(40),
    location varchar(20),
    date date,
    primary key (meetName, date)
);

CREATE TABLE Swim (
    eventName varchar(20),
    meetName varchar(20),
    meetDate date,
    swimmerID int,
    time float NOT NULL, -- #seconds --> php/js --> readable time on frontend
    foreign key (meetName, meetDate) references Meet(meetName, date),
    foreign key (swimmerID) references Swimmer(swimmerID),
    primary key (swimmerID, eventName, meetName, meetDate)
);
