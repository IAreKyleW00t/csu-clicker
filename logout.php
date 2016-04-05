<?php
    require_once 'inc/session.php';
    
    /* Completely clear out the SESSION variable by assigning it to an
        empty array. */
    $_SESSION = array();
    
    /* Completely remove all SESSION-related cookies if any have
        been set.*/
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    
    /* Destroy the SESSION and all data related to it (from above code.) */
    session_destroy();
    
    /* Redirect to index once we're done. */
    header('Location: /');
    exit;
?>