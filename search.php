<?php
// https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php

    $mainSearch = $mysqli->prepare("SELECT * FROM Swam WHERE ?=swimmerID && ?=gender && ?=swimName && ?=meetName && ?=meetDate && ?<=time && ?>=time");
        //searches it all swimmerID, gender, swimName, meetName, meetDate, smaller time, larger time
    $mainSearch->bind_param("dsssff", $swimmerID, $gender, $swimName, $meetName, $meetDate, $smallTime, $bigTime);

// returns the entire sqli stream? or should we format the text in here as well?
function search($swimmerID, $gender, $swimName, $meetName, $meetDate, $smallTime, $bigTime){
    //if we never gave it a propper ID then it becomes a wildcard...
    if(!$swimmerID)
        $swimmerID = '*';
    if(!$gender)
        $gender = '*';
    if(!$swimName)
        $swimName = '*';
    if(!$meetName)
        $meetName = '*';
    if(!$meetDate)
        $meetDate = '*';
    // for the numbers we'll just set them to upper and lower bounds?
    if(!$smallTime)
        $smallTime = -1;
    if(!$bigTime)
        $bigTime = 999;


    //call sql prepared statement and return it
    $GLOBALS['mainSearch']->execute();

    $result = $GLOBALS['mainSeach']->get_result();

    return $result;
}



?>