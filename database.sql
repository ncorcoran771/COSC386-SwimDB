CREATE TABLE Meet (
    name varchar(20),
    location varchar(20),
    date int,
    primary key (name, date)
)
CREATE TABLE Conference(
    name varchar(20),
    state varchar(2),
    primary key (name, state)
)
CREATE TABLE Team(
    teamName varchar(20),
    city varchar(20),
    conName varchar(20), conState varchar(2),
    foreign key (conName, conState) references conferences(name, state),
    primary key (teamName)
)
CREATE TABLE Swim(
    bestTime float,
    name varchar(20),
    meetName varchar(20), meetDate int,
    foreign key (meetName, meetDate) references Meet(meetName, meetDate),
    primary key (name, meetName, meetDate)
)
CREATE TABLE Swimmer(
    name varchar(20),
    totalPoints int,
    gender varchar(10),
    swimmerID int,
    hometown varchar(10),
    swimsFor varchar(20)
    foreign key (swimsFor) references Team(teamName),
    primary key (swimmerID, gender)
)
CREATE TABLE Swam(
    personalBest boolean,
    time float,
    swimName varchar(20),
    swimmerID int, gender varchar(10),
    swimName varchar(20), meetName varchar(20), meetDate int,
    foreign key (swimmerID, gender) references Swimmer(swimmerID, gender),
    foreign key (swimName, meetName, meetDate) references Swim(name, meetName, meetDate),
    primary key (swimmerID, gender, swimName, meetName, meetDate)
)
CREATE TABLE competedIn(
    totalPoints int,
    meetName varchar(20), meetDate int,
    swimmerID int, gender varchar(10)
    foreign key (meetName, meetDate) references Meet(name, date),
    foreign key (swimmerID, gender) references Swimmer(swimmerID, gender),
    primary key (swimmerID, gender, meetName, meetDate)
)