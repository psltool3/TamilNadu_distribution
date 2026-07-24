<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/SessionFunction.php');
require ('../util/Encryption.php');
require('../util/Logger.php');

if(!SessionCheck()){
    return;
}

require('Header.php');
$nonceValue = 'nonce_value';

if (!isset($_POST["username"], $_POST["password"], $_POST["newusername"], $_POST["newpassword"], $_POST["district"])) {
    echo "Error : Missing required fields";
    return;
}

// Get the username and password from the POST data
$person = new Login;
$person->setUsername($_POST["username"]);
$person->setPassword($_POST["password"]);

// Check if the session user matches the submitted username
if($_SESSION['user']!=$person->getUsername()){
    echo "User is logged in with a different username and password";
    return;
}

// Validate password length
if (strlen($_POST["newusername"]) < '4') {
    echo "Username must be at least 4 characters long";
    return;
}

$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

$decryptedNewPassword = $Encryption->decrypt($_POST["newpassword"], $nonceValue);
if (!preg_match('/^.{5,}$/', $decryptedNewPassword)) {
    echo "Error : Password must contain at least 5 characters.";
    return;
}

$newusername = htmlspecialchars($_POST["newusername"], ENT_QUOTES, 'UTF-8');

// Ensure the new username doesn't contain special characters (optional)
if (!preg_match('/^[a-zA-Z0-9_@]+$/', $newusername)) {
    echo "Username can only contain letters, numbers, underscores and @.";
    return;
}

// Query the database to get the stored hash for the username
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

// Check if the username exists and verify the password using password_verify

    if (password_verify($person->getPassword(), $row['password'])) {
        $person = new Login;
        $person->setUsername($_POST["newusername"]);
        $person->setPassword($decryptedNewPassword);
        $person->setRole($_POST["district"]);
        $uid = uniqid();
		
		$log_query = "select username  from login WHERE uid='$uid'";
		$log_result = mysqli_query($con,$log_query);
		if ($log_result && $row = $log_result->fetch_assoc()) {
			$log_name =  $row['username'];
		}

        // Hash the new password before inserting it into the database
        $hashedPassword = password_hash($person->getPassword(), PASSWORD_DEFAULT);

        // Check if the new username already exists
        $query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
        $result = mysqli_query($con, $query);
        $numrows = mysqli_num_rows($result);

        if($numrows == 1){
            echo "Error : Username already exists";
        } else {
            // Insert the new user with the hashed password
            $query1 = "INSERT INTO login (username, password, uid, role, verified) 
                       VALUES ('".$person->getUsername()."', '".$hashedPassword."', '$uid', '".strtolower($person->getRole())."', '1')";
            mysqli_query($con, $query1);
            mysqli_close($con);
			$filteredPost = $_POST;
			unset($filteredPost['username'], $filteredPost['password']);
			writeLog("User ->" ." User Add ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost). " | " . $person->getUsername());
            echo "<script>window.location.href = '../Userdata.php';</script>";
        }

    } else {
        
        echo "Error : Username or Password is incorrect";
        return;
    }

?>
<?php require('Fullui.php'); ?>
