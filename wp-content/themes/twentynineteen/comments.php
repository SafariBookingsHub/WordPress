<?php
    /**
     * The template for displaying comments
     *
     * This is the template that displays the area of the page that contains both the current comments
     * and the comment form.
     *
     * @link       https://developer.wordpress.org/themes/basics/template-hierarchy/
     *
     * @package    WordPress
     * @subpackage Twenty_Nineteen
     * @since      Twenty Nineteen 1.0
     */

    /*
     * If the current post is protected by a password and
     * the visitor has not yet entered the password we will
     * return early without loading the comments.
    */
    if(post_password_required())
    {
        return;
    }

    $discussion = twentynineteen_get_discussion_data();
?>

<div id="comments" class="<?php echo comments_open() ? 'comments-area' : 'comments-area comments-closed'; ?>">
    <div class="<?php echo $discussion->responses > 0 ? 'comments-title-wrap' : 'comments-title-wrap no-responses'; ?>">
        <h2 class="comments-title">
            <?php
                if(comments_open())
                {
                    if(have_comments())
                    {
                        _e('Join the Conversation', 'twentynineteen');
                    }
                    else
                    {
                        _e('Leave a comment', 'twentynineteen');
                    }
                }
                else
                {
                    if('1' === (string) $discussion->responses)
                    {
                        /* translators: %s: Post title. */
                        printf(_x('One reply on &ldquo;%s&rdquo;', 'comments title', 'twentynineteen'), get_the_title());
                    }
                    else
                    {
                        printf(/* translators: 1: Number of comments, 2: Post title. */ _nx('%1$s reply on &ldquo;%2$s&rdquo;', '%1$s replies on &ldquo;%2$s&rdquo;', $discussion->responses, 'comments title', 'twentynineteen'), number_format_i18n($discussion->responses), get_the_title());
                    }
                }
            ?>
        </h2><!-- .comments-title -->
        <?php
            // Only show discussion meta information when comments are open and available.
            if(have_comments() && comments_open())
            {
                get_template_part('template-parts/post/discussion', 'meta');
            }
        ?>
    </div><!-- .comments-title-wrap -->
    <?php
        if(have_comments()) :

            // Show comment form at top if showing newest comments at the top.
            if(comments_open())
            {
                twentynineteen_comment_form('desc');
            }

            ?>
            <ol class="comment-list">
                <?php
                    wp_list_comments([
                                         'walker' => new TwentyNineteen_Walker_Comment(),
                                         'avatar_size' => twentynineteen_get_avatar_size(),
                                         'short_ping' => true,
                                         'style' => 'ol',
                                     ]);
                ?>
            </ol><!-- .comment-list -->
            <?php

            // Show comment navigation.
            if(have_comments()) :
                $prev_icon = twentynineteen_get_icon_svg('chevron_left', 22);
                $next_icon = twentynineteen_get_icon_svg('chevron_right', 22);
                the_comments_navigation([
                                            'prev_text' => sprintf('%1$s <span class="nav-prev-text">%2$s</span>', $prev_icon, /* translators: Comments navigation link text. The secondary-text element is hidden on small screens. */ __('<span class="primary-text">Previous</span> <span class="secondary-text">Comments</span>', 'twentynineteen')),
                                            'next_text' => sprintf('<span class="nav-next-text">%1$s</span> %2$s', /* translators: Comments navigation link text. The secondary-text element is hidden on small screens. */ __('<span class="primary-text">Next</span> <span class="secondary-text">Comments</span>', 'twentynineteen'), $next_icon),
                                        ]);
            endif;

            // Show comment form at bottom if showing newest comments at the bottom.
            if(comments_open() && 'asc' === strtolower(get_option('comment_order', 'asc'))) :
                ?>
                <div class="comment-form-flex comment-form-wrapper">
                    <h2 class="comments-title"><?php _e('Leave a comment', 'twentynineteen'); ?></h2>
                    <?php twentynineteen_comment_form('asc'); ?>
                </div>
            <?php
            endif;

            // If comments are closed and there are comments, let's leave a little note, shall we?
            if(! comments_open()) :
                ?>
                <p class="no-comments">
                    <?php _e('Comments are closed.', 'twentynineteen'); ?>
                </p>
            <?php
            endif;

        else :

            // Show comment form.
            twentynineteen_comment_form(true);

        endif; // if have_comments();
    ?>
</div><!-- #comments -->
