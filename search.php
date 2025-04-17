<?php
// https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php

    $mainSearch = $mysqli->prepare("SELECT * FROM Swam WHERE ?=swimmerID && ?=gender && ?=swimName && ?=meetName && ?=meetDate && ?<=time && ?>=time");
        //searches it all swimmerID, gender, swimName, meetName, meetDate, smaller time, larger time
// returns the entire sqli stream? or should we format the text in here as well?
function search{


}



?>