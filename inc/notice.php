<?php require_once('inc/session.php'); ?>
<?php if (isset($_SESSION['NOTICE'])) : ?>
<!-- Notice -->
<script type="text/javascript">
    $(document).ready(function() {
        $.snackbar({content: "<?php echo $_SESSION['NOTICE']; ?>", timeout: 5000, htmlAllowed: true});
    });
</script>
<?php endif; ?>
<?php
    /* Remove notice after we've displayed it. */
    unset($_SESSION['NOTICE']);
?>
