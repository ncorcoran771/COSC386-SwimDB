/*
    updated database table
*/

CREATE TABLE Swimmer(
    name varchar(20),
    -- password varchar(64), 
    powerIndex int,
    gender char,
    swimmerID int,
    hometown varchar(20),
    team varchar(20)
    foreign key (team) references Team(teamName),
    primary key (swimmerID)
)

CREATE TABLE Swim(
    -- bestTime float,
    eventName varchar(20),
    meetName varchar(20),
    meetDate int, --mmddyyyy
    foreign key (meetName, meetDate) references Meet(meetName, meetDate),
    primary key (name, meetName, meetDate)
)

CREATE TABLE Meet (
    meetName varchar(40),
    location varchar(20),
    date int, --ddmmyyyy
    primary key (name, date)
)

CREATE TABLE Conference(
    name varchar(20),
    state varchar(2),
    primary key (name, state)
)

CREATE TABLE Team(
    teamName varchar(20),
    location varchar(20),
    confName varchar(20),
    confState varchar(2),
    -- coachID int, foreign key (coachID) references Coache(coachID),
    foreign key (confName, confState) references conferences(name, state),
    primary key (teamName)
)
