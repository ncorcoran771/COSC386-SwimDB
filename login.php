<!-- called when login button is pressed-->
<?php
    session_start();//start a user session

    //insert pass hashing here
    $hashPass = $plainPass;
    unset($plainPass);

//sets the user type as a session variable
    if(isset(_POST['swimmerLog'])) // if swimmer button
        $_SESSION['userType'] = 'swimmer';
    else if(isset(_POST['coachLog'])) // if coach button
        $_SESSION['userType'] = 'coach';
    else if(isset(_POST['adminLog'])) // if admin button
        $_SESSION['userType'] = 'admin';
    else
        echo "SOMETHING WENT HORRBLY WRONG!!"; //fail case...

//sets the users ID in the session variable
    $_SESSION['user'] = $userID;

    $query = passQuery($_SESSION['userType'], $_SESSION['userID'], $hashPassword);

    $user = mysqli_query($conn, $query);//we'd want to save some part of this as the users session variable?

    //verifying the user goes here. I aint doin that right now
    if(/*user is verified*/){

        //redirect to their specific page?

        $_SESSION['logedIN'] = 1;
    }





    //password query building function
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
