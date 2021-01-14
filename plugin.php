<?php
/**
 * Plugin Name: SimpleTOC - Table of Contents Block
 * Plugin URI: https://github.com/mtoensing/simpletoc
 * Description: Adds a basic "Table of Contents" Gutenberg block.
 * Version: 2.5
 * Author: MarcDK
 * Author URI: marc.tv
 * Text Domain: simpletoc
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace SimpleTOC;

defined('ABSPATH') || exit;

/**
  * Initalise frontend and backend and register block
**/
add_action('init', __NAMESPACE__ . '\\init');
add_action('init', __NAMESPACE__ . '\\register_block');

function init() {

    wp_register_script(
    'simpletoc-js',
    plugins_url('build/index.js', __FILE__),
    [ 'wp-i18n', 'wp-blocks', 'wp-editor', 'wp-element', 'wp-server-side-render'],
    filemtime(plugin_dir_path(__FILE__) . 'build/index.js')
    );

    wp_register_style(
    'simpletoc-editor',
    plugins_url('editor.css', __FILE__),
    array( 'wp-edit-blocks' ),
    filemtime(plugin_dir_path(__FILE__) . 'editor.css')
    );

		wp_set_script_translations('simpletoc-js', 'simpletoc');

}

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 */

function register_block() {
    if (! function_exists('register_block_type')) {
        // Gutenberg is not active.
        return;
    }

    register_block_type('simpletoc/toc', [
    'editor_script' => 'simpletoc-js',
    'editor_style' => 'simpletoc-editor',
        'attributes' => array(
        'plugin' => array(
            'default' => array(
                'post_id' => 123,
                'key' => 'group_duq8f62hf',
                'title' => 'My Block',
            ),
            '_builtIn' => true,
        ),
        'updated' => array(
            'type' => 'number',
            'default' => 0,
            '_builtIn' => true,
        ),
        'others' => array(
            'type' => 'string',
        )
    ),
    'render_callback' => __NAMESPACE__ . '\\render_callback'
   ]);
}

function render_callback($attributes, $content) {
    //add only if block is used in this post.
    add_filter('render_block', __NAMESPACE__ . '\\filter_block', 10, 2);

    $post = get_post();
    $blocks = parse_blocks($post->post_content);

    if (empty($blocks)) {
				$html = '<h2 class="simpletoc-title">' . __('Table of Contents', 'simpletoc') . '</h2>';
        $html .= '<div class="components-notice is-warning"><strong>' . __('No blocks found.', 'simpletoc')  . ' </strong><span>' . __('Save or update post first.', 'simpletoc') . '</span></div>';
				return $html;
    }

    $headings = array_values(array_filter($blocks, function ($block) {
        return $block['blockName'] === 'core/heading';
    }));

    if (empty($headings)) {
				$html = '<h2 class="simpletoc-title">' . __('Table of Contents', 'simpletoc') . '</h2>';
        $html .= '<div class="components-notice is-warning"><strong>' . __('No headings found.', 'simpletoc') . ' </strong><span>' . __('Save or update post first.', 'simpletoc') . '</span></div>';
				return $html;
		}

    $heading_contents = array_column($headings, 'innerHTML');

    foreach ($heading_contents as  $key => & $heading) {
        $heading = trim($heading);
    }

    $output = generateToc($heading_contents);
    return $output;
}

function simpletoc_sanitize_string($string){
  $string_without_accents = remove_accents($string);
  $sanitized_string = sanitize_title_with_dashes($string_without_accents);
  return $sanitized_string;
}

function filter_block($block_content, $block) {
    if ($block['blockName'] !== 'core/heading') {
        return $block_content;
    }

    //$block_content = strip_tags($block_content, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
    preg_match('/\\n<(h[2-4](?:.*))>(.*)<\/(h[2-4])>\\n/', $block_content, $matches);
    $link = simpletoc_sanitize_string($matches[2]);
    $start = preg_replace('#\s(id|class)="[^"]+"#', '', $matches[1]);
    return "\n<{$start} id='{$link}'>" . $matches[2] . "</{$matches[3]}>\n";
}

function generateToc($matches) {
    /*  code from https://github.com/shazahm1/Easy-Table-of-Contents */
    $list ='';
    $current_depth      = 7;
    $numbered_items     = array();

    // find the minimum heading to establish our baseline
    //for ( $i = 0; $i < count( $matches ); $i ++ ) {
    foreach ($matches as $i => $match) {
        if ($current_depth > $matches[ $i ][2]) {
            $current_depth = (int) $matches[ $i ][2];
        }
    }

    $numbered_items[ $current_depth ] = 0;

    foreach ($matches as $i => $match) {

        $level = $matches[ $i ][2];
        $count = $i + 1;

        if ($current_depth == (int) $matches[ $i ][2]) {
            $list .= '<li>';
        }

        // start lists
        if ($current_depth != (int) $matches[ $i ][2]) {
            for ($current_depth; $current_depth < (int) $matches[ $i ][2]; $current_depth++) {
                $numbered_items[ $current_depth + 1 ] = 0;
                $list .= '<ul><li>';
            }
        }

        $title = strip_tags($match);
        $link = simpletoc_sanitize_string( $title );
        $list .= '<a href="#' . $link . '">' . $title . '</a>';

        // end lists
        if ($i != count($matches) - 1) {
            if ($current_depth > (int) $matches[ $i + 1 ][2]) {
                for ($current_depth; $current_depth > (int) $matches[ $i + 1 ][2]; $current_depth--) {
                    $list .= '</li></ul>';
                    $numbered_items[ $current_depth ] = 0;
                }
            }

            if ($current_depth == (int) @$matches[ $i + 1 ][2]) {
                $list .= '</li>';
            }
        // last heading
        } else {

          // traverse heading in reverse from bottom to top
          for (end($matches); ($currentKey=key($matches))!==null; prev($matches)){

            // make sure it is not the first heading
            if( $currentKey != 0 ) {
                $current_depth = $matches[ $currentKey ][2];
                $prevdepth = $matches[ $currentKey - 1 ][2];

                // is current heading level higher than previous?
                if( $current_depth > $prevdepth ) {
                  $list .= '</li></ul>';
                }
            }

          }

        }
    }
    $html = '<h2 class="simpletoc-title">' . __('Table of Contents', 'simpletoc') . '</h2>';
    $html .= '<ul class="simpletoc">' . $list . "</li></ul>";
    return $html;
}

/**
 * Filter to add plugin to the TOC list Support for Rank Math Seo Plugin.
 *
 * @param array TOC plugins.
 */
add_filter( 'rank_math/researches/toc_plugins', function( $toc_plugins ) {
       $toc_plugins['simpletoc/plugin.php'] = 'simpletoc';
    return $toc_plugins;
   }
  );
