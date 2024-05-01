<?php global $post; ?>

<div class="vod-item">
    <?php the_post_thumbnail(); ?>

    <div class="item-body">
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php
        $douban_id =  get_post_meta($post->ID, 'douban_id', true);
        if ($douban_id) {
            printf('<a href="%s">%s</a>', 'https://movie.douban.com/subject/' . $douban_id . '/', __('豆瓣', 'wptv') . $douban_id);
        }
        echo   '/' . get_post_meta($post->ID, 'douban_score', true);
        ?>

        <div>
            <?php echo get_post_meta($post->ID, 'pubdate', true); ?>
        </div>

    </div>
</div>