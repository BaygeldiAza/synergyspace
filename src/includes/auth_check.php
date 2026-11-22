if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

//check whether the user is logged in
function requireLogin(){
    if(!isset($_SESSION[$user_id])){
        header("Location: /synergyspace/src/login.php");
        exit;
    }
}

//check whether user has a correct role
function requireRole($role){
    requireLogin();

    if($_SESSION["role"] !== $role){
        header("Location: /synergyspace/src/index.php");
        exit;
    }
}