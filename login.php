<!-- called when login button is pressed-->
<?php
    session_start();//start a user session

    //insert pass hashing here
    $hashPass = $plainPass;
    unset($plainPass);

//sets the user type as a session variable
    if(isset($_POST['swimmerLog'])) // if swimmer button
        $_SESSION['userType'] = 'swimmer';
    else if(isset($_POST['coachLog'])) // if coach button
        $_SESSION['userType'] = 'coach';
    else if(isset($_POST['adminLog'])) // if admin button
        $_SESSION['userType'] = 'admin';
    else
        echo "SOMETHING WENT HORRBLY WRONG!!\n"; //fail case...

//sets the users ID in the session variable
    $_SESSION['user'] = $userID;

    $output = mysqli_query($conn, passQuery($_SESSION['userType'], $_SESSION['user'], $hashPassword));
    if(!$outpt)//returns false of failure
        echo "SQL QUERY FAILED\n";

    $user = mysqli_fetch_assoc($output);
    if(mysqli_fetch_assoc($output))//if another row exists we don messed up
        echo "SQL QUERY RETURNED MULTIPLE ROWS \n";

    if($user){//user exists and so much be who they say they are?
        $_SESSION['logedIN'] = true; // they've logged in.
        //do we want to save anything else from their query?
        //maybe redirect to a home page?
    }

    // password query building function
    // we want an entire tuple of one person, based on their password & id
    // technically possible to have same id same password different gender but lets ignore that
    function passQuery($type, $id, $hashPass){

        $toReturn = "SELECT * FROM ";
        if($type == 'swimmer')
            $toReturn = $toReturn . "Swimmer WHERE swimmerID";
        else if ($type == 'coach')
            $toReturn = $toReturn . "Coaches WHERE coachID";
        else if ($type == 'admin')
            $toReturn = $toReturn . "Administrator WHERE adminID";

        $toReturn = $toReturn . " == " . $id . " && password == " . $hashPass;

        return $toReturn;
    }


?>
