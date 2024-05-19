<?php

namespace WPTVTheme;

class VodItemHelper {
    public static function get_remark_html($post) {
        $post = get_post($post);

        $remark = '';

        $total = (int)get_post_meta($post->ID, 'episode_total', true);
        $serial = (int)get_post_meta($post->ID, 'episode_serial', true);

        if ($total && $serial) {
            if ($total == $serial) {
                $remark = '已完结';
            } else {
                $remark = sprintf('更新至第%d集', $serial);
            }
        }

        if (!$remark) {
            $remark = get_post_meta($post->ID, 'remark', true);
        }

        $remark_html = $remark ? '<mark class="remark">' . $remark . '</mark>' : '';

        return $remark_html;
    }
}
