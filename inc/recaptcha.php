<?php
    require_once('inc/config.php');

    /*
        Function that will take the data as input and send it to Google's reCAPTCHA
        validation server and determine if the given data is valid.
        This response will be either TRUE or FALSE.

        This function requires a reCAPTCHA secret key as provided by Google for your site.
        For the sake of simplicity, this key is defined in our configuration file.
    */
    function reCAPTCHA($g) {
        $url = 'https://www.google.com/recaptcha/api/siteverify'; //Google server
        $data = 'secret=' . RECAPTCHA_SECRET . '&response=' . $g . '&remoteip=' . $_SERVER['REMOTE_ADDR']; //Data

        /* Create our cURL request */
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1); //POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //Add args (POST data)
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //Follow to end
        curl_setopt($ch, CURLOPT_HEADER, 0); //No Header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Return after done

        /* Get reponse from POST */
        $reponse = curl_exec($ch);
        $json = json_decode($reponse, true); //Decode from JSON

        /* Return response value */
        return $json['success'];
    }
?>
