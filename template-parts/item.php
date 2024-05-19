<?php global $post; ?>

<div class="vod-item">
    <?php the_post_thumbnail(); ?>

    <div class="item-body">
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php
        if ($post->douban_score) {
            printf('<span class="score">%s</span>', $post->douban_score);
        }
        ?>
    </div>
</div>