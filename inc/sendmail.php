<?php
    /*
        Function that will send an email to a recipient and handle all backend
        headers, formatting, etc. for the end-user automatically. (see: mail())
    */
    function sendmail($to, $subject, $message, $from, $name) {
        /* Create a unique email token and message ID. */
        $token = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
        $message_id = "<" . uniqid() . ".$token@" . $_SERVER['SERVER_NAME'] . ">";

        /* Setup email headers so they meet RFC qualifitications. (Required for yahoo, hotmail, etc.) */
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset=utf8";
        $headers[] = "Message-id: $message_id";
        $headers[] = "From: '$name' <$from>";
        $headers[] = "Reply-To: '$name' <$from>";
        $headers[] = "Date: " . date(DATE_RFC2822);
        $headers[] = "Return-Path: <$from>";
        $headers[] = "X-Priority: 3";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        /* Send email to recipient via sendmail and change who the email is "from." */
        mail($to, $subject, $message, implode("\r\n", $headers), "-f $from");
    }
?>
