<?php

use WPTV\Helper;

$episode = $args;


?>

<div class="episode-item">
    <?php
    if ($episode->still_path) {
        $image_url = Helper::get_tmdb_image_url($episode->still_path);
    } else {
        $image_url = 'https://www.themoviedb.org/assets/2/v4/glyphicons/basic/glyphicons-basic-38-picture-grey-c2ebdbb057f2a7614185931650f8cee23fa137b93812ccb132b9df511df1cfac.svg';
    }

    ?>
    <div class="ct-media-container" style="aspect-ratio:16/9;">
        <img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr($episode->name); ?>">
    </div>

    <h4><i><?php echo $episode->episode_number; ?></i>. <?php echo preg_replace('/第\s+(\d+)\s+集/', '第$1集', $episode->name); ?></h4>

    <div clss="meta">
        <span class="runtime"><?php echo $episode->runtime; ?></span>
        <span class="air-date"><?php echo $episode->air_date; ?></span>
    </div>

    <p><?php echo $episode->overview; ?></p>
</div>