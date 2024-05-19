<section>
    <h2><?php echo $args['title']; ?></h2>

    <div class="scrollable-container">
        <div class="item-loop scrollable-list">
            <?php
            foreach ($posts as $post) {
                get_template_part('template-parts/item');
            }
            wp_reset_query();
            ?>
        </div>
    </div>
</section>