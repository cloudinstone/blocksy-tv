<section>
    <h2><?php echo $args['title']; ?></h2>

    <?php
    $douban_items = $args['douban_items'];

    // var_dump($douban_items);

    $douban_ids = array_map(function ($item) {
        return $item['id'];
    }, $douban_items);

    $transient = $args['transient'];

    $post_ids = get_transient($transient);

    if (empty($post_ids)) {
        $post_ids = wptv_get_items_by_douban_ids($douban_ids, [
            'posts_per_page' => 24
        ]);

        set_transient($transient, $post_ids, DAY_IN_SECONDS);
    }

    $posts = get_posts([
        'post_type' => 'wptv_video',
        'post__in' => $post_ids,
        'posts_per_page' => 24
    ]);

    // var_dump($douban_ids);
    $sort_by_douban_ids = true;
    if ($sort_by_douban_ids) {
        usort($posts, function ($a, $b) use ($douban_ids) {
            return array_search($a->douban_id, $douban_ids) - array_search($b->douban_id, $douban_ids);
        });
    }
    ?>

    <div class="scrollable-container">
        <div class="item-loop scrollable-list">
            <?php
            foreach ($posts as $post) {
                get_template_part('template-parts/item');
            }
            ?>
        </div>
    </div>
</section>