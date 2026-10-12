<?php
get_header();
echo '<main class="dj-section"><div class="dj-wrap"><h1>محتوا</h1>';
if (have_posts()) : while (have_posts()) : the_post();
the_title('<h2>','</h2>'); the_content();
endwhile; endif;
echo '</div></main>';
get_footer();
