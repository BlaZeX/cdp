<?php

//prevent direct file access
if (phpversion() >= 5) {
    if (count(get_included_files()) == 1) {
        die();
    }
}
else {
    if (count(get_included_files()) == 0) {
        die();
    }
}
if (!defined('FILEACCESS')) {
    die();
}
if (isset($_POST['username']) && isset($_POST['password'])) {
    $users = json_decode(file_get_contents($config['path'] . '/db/db-users.json'), true);
    $acls  = json_decode(file_get_contents($config['path'] . '/db/db-acl.json'), true);
    foreach ($users as $user) {
        if ($user['username'] == $_POST['username']) {
            $userdetails = $user;
        }
    }
    if (is_array($userdetails) && md5($_POST['password']) == $userdetails['password']) {
        $_SESSION['user'] = $_POST['username'];
        $_SESSION['ip']   = $_SERVER['REMOTE_ADDR'];
        $_SESSION['time'] = time();
        foreach ($acls as $acl) {
            if ($acl['id'] == $user['acl']) {
                $_SESSION['acl'] = $acl['perms'];
            }
        }
        if (isset($userdetails['2fo']) && $userdetails['2fo']) {
            if (!isset($_POST['onekey'])) {
                $_POST['onekey'] = 0;
            }
            require($config['path'] . '/libs/googleauthenticator/GoogleAuthenticator.php');
            $ga = new PHPGangsta_GoogleAuthenticator();
            if (!$ga->verifyCode($userdetails['2fokey'], $_POST['onekey'], 2)) {
                header('Location: index.php?login=failed');
                die();
            }
        }
        logevent('User ' . $_SESSION['user'] . ' logged in', 'activity');
        header('Location: index.php');
        die();
    } else {
        header('Location: index.php?login=failed');
    }
} else {
    $smarty->display($config['path'].'/templates/header.tpl');
    $smarty->display($config['path'].'/templates/login.tpl');
    $smarty->display($config['path'].'/templates/footer.tpl');
}

?>