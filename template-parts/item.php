<div class="vod-item">
    <?php the_post_thumbnail(); ?>
    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php
    echo $post->douban_score;
    ?>
</div>