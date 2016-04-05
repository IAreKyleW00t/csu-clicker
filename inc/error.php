<?php require_once('inc/session.php'); ?>
<?php if (isset($_SESSION['ERROR'])) : ?>
<!-- Error -->
<script>
    $(document).ready(function() {
        $.snackbar({content: '<?php echo "<b>ERROR:</b> " . $_SESSION['ERROR']; ?>', timeout: 5000, htmlAllowed: true});
    });
</script>
<?php endif; ?>
<?php
    /* Remove error after we've displayed it. */
    unset($_SESSION['ERROR']);
?>
