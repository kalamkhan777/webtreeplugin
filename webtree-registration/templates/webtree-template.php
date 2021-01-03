<?php
/**
* Template Name: Webtree Template
*/
get_header();
?>
<div class="webtree-customers-wrapper">
    <?php
    //To show all active users
    echo do_shortcode('[wt-all-customers-front]');
    ?>    
</div>
<?php
get_footer();