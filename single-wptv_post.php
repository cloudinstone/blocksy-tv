<?php

/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Blocksy
 */

use function PHPSTORM_META\map;

get_header();

wp_defer_term_counting(true);

$taxonomy_name = 'wptv_actor';
$terms = get_terms(array(
    'taxonomy' => $taxonomy_name,
    'hide_empty' => false,
    'number' => 1000,
    'orderby' => 'count',
    'order' => 'ASC'
    // 'fields' => 'ids'
));

if (empty($terms)) {
    var_dump('没有了');
    return;
}

$min_count = 10;

foreach ($terms as $term) {

    if ($term->count < $min_count) {
        var_dump($term->name);
        wp_delete_term($term->term_id, $taxonomy_name);
    } else {
        var_dump($term->count . 'count已经大于' . $min_count);
        die;
        break;
    }
}
wp_defer_term_counting(false);




?>

<script>
    window.location.reload();
</script>

<?php

function fix_append_provider() {

    $query = new WP_Query([
        'post_type' => 'wptv_post',
        'posts_per_page' => 100,
        'meta_query' => [
            [
                'key' => 'source_urls',
                'value' => 'ikzybf.com',
                'compare' => 'LIKE'
            ]
        ],
        'tax_query' => [
            [
                'taxonomy' => 'wptv_provider',
                'field' => 'slug',
                'operator' => 'NOT IN',
                'terms' => 'ikunzy'
            ]
        ],
        'fields' => 'ids'
    ]);
    $posts = $query->posts;

    echo '还有' . $query->found_posts . '条待处理';

    if (empty($posts)) {
        echo '没有文章了';
        die;
    }

    $provider = get_term_by('slug', 'ikunzy', 'wptv_provider');
    // var_dump($provider);

    foreach ($posts as $post_id) {
        // $t = get_post_meta($post_id, 'source_urls', true);
        wp_set_post_terms($post_id, [$provider->term_id], 'wptv_provider', true);

        // var_dump($t);
    }
?>

    <script>
        window.location.reload();
    </script>
<?php

    die;
}

$provider_id = (int)get_query_var('provider');

$episode = (int)get_query_var('episode');


$sources = wptv_vod_get_source_urls($post->ID);

$first = reset($sources);

// var_dump($first);

$provider_id = $first['provider_id'];

$episode = 1;



?>

<div class="ct-container">

    <?php while (have_posts()) : the_post();
        global $post;


        // var_dump($sources);

        $src = $sources[$provider_id]['src_set'][$episode - 1];

        // var_dump($src);

        // $src['url'] = 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        // echo do_shortcode('[video src="' . $src['url'] . '"]');
    ?>


        <div class="play-container">
            <div class="player-area">
                <div class="player">
                    <video id="video" src="<?php echo $src['url']; ?>" controls></video>

                </div>
            </div>



            <div class="info-area">
                <?php

                $tabs = [
                    'sources' => __('选集', 'wptv'),
                    'details' => __('详情', 'wptv'),
                    'related' => __('相关', 'wptv'),
                    'comments' => __('评论', 'wptv'),
                ];
                ?>
                <md-tabs class="content-tabs">
                    <?php
                    foreach ($tabs as $id => $label) {
                        echo '<md-primary-tab id="' . $id . '-tab" aria-controls="' . $id . '-panel">' . $label . '</md-primary-tab>';
                    }
                    ?>
                </md-tabs>

                <div class="content-panel" id="sources-panel" role="tabpanel" aria-labelledby="sources-tab">
                    <?php
                    wptv_vod_source_urls($post->ID);
                    ?>
                </div>

                <div class="content-panel" id="details-panel" role="tabpanel" aria-labelledby="details-tab" hidden>

                    <div class="thumbnail">
                        <?php the_post_thumbnail(); ?>
                    </div>

                    <?php the_terms($post->ID, 'wptv_category'); ?>
                    <h1 class="entry-title">
                        <?php the_title(); ?>
                        <?php echo get_post_meta($post->ID, 'aka_names', true); ?>
                        <?php echo get_post_meta($post->ID, 'version', true); ?>
                        <?php echo get_post_meta($post->ID, 'remarks', true); ?>
                    </h1>

                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>

                    <div class="entry-attrs">
                        <?php
                        wptv_vod_attr_row('wptv_director', __('Director', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_writer', __('Writer', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_actor', __('Actor', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_area', __('Area', 'wptv'), 'term');
                        wptv_vod_attr_row('wptv_lang', __('Language', 'wptv'), 'term');


                        wptv_vod_attr_row('pubdate', __('Publish Date', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('duration', __('Duration', 'wptv'), 'post_meta');
                        wptv_vod_attr_row('douban_id', __('Douban ID', 'wptv'), 'post_meta');

                        wptv_vod_attr_row('wptv_tag', __('Tags', 'wptv'), 'post_meta');


                        echo get_the_term_list($post->ID, 'wptv_year', __('Year', 'wptv'));

                        echo '</br>';

                        $douban_id = $post->douban_id;
                        if ($douban_id) {
                            printf('<a href="%s">%s</a>', 'https://movie.douban.com/subject/' . $douban_id . '/', __('豆瓣'));
                        }

                        echo $post->douban_score;



                        ?>
                    </div>
                </div>

                <div class="content-panel" id="related-panel" role="tabpanel" aria-labelledby="related-tab" hidden>
                    related
                </div>

                <div class="content-panel" id="comments-panel" role="tabpanel" aria-labelledby="comments-tab" hidden>
                    <?php

                    echo 'comments';
                    ?>
                </div>
            </div>
        </div>

    <?php endwhile; ?>

</div>

<?php get_footer();
