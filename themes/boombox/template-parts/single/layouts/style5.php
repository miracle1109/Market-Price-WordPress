<?php
/**
 * Template part to render single "Style 5"
 * @since   2.5.0
 * @version 2.5.0
 * @var $helper Boombox_Single_Post_Template_Helper Template helper
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed' );
}

get_header();

$helper = Boombox_Template::init( 'post' );
$options = $helper->get_options();
$bb_have_posts = have_posts();
if( $bb_have_posts ) {
	the_post();
}

$media_el_style = '';
if( $options[ 'featured_image_src' ] ) {
	$media_el_style = ' style="background-image: url(' . $options[ 'featured_image_src' ] . ');"';
} ?>

<div class="single-container s-mt-sm">

	<?php do_action( 'boombox/before_template_content', 'single' ); ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( $options[ 'classes' ] ); ?> <?php boombox_single_article_structured_data(); ?>>
		<?php if( $bb_have_posts ) { ?>
		<div class="single-top-container s-mb-md">
			<?php
			// Post Breadcrumb
			if( $options[ 'elements' ][ 'breadcrumb' ] ) {
				boombox_get_template_part( 'template-parts/breadcrumb', '', array(
					'before' => '<nav class="s-post-breadcrumb container bb-breadcrumb mb-xs bb-mb-el clr-style1">',
					'after'  => '</nav>'
				) );
			} ?>

			<?php // Post Featured Media ?>
			<div class="s-post-featured-media container boxed" >
				<div class="featured-media-el"<?php echo $media_el_style; ?>>
					<?php
					// Post Image
					boombox_get_template_part( 'template-parts/single/components/media/thumbnail' );
					?>
				</div>
			</div>
		</div>
		<?php } ?>
		<div class="single-main-container container">
			<div class="bb-row">
				<div class="bb-col col-content">
					<div class="bb-row">
						<div class="bb-col col-site-main">
							<div class="site-main" role="main">
								
								<?php
								if( $bb_have_posts ) {

									// Post Main Content for Card View ?>
									<div class="s-post-main mb-md bb-mb-el bb-card-item">

										<?php

										boombox_the_advertisement( 'boombox-single-before-content', array(
											'class' => 'large bb-before-cnt-area',
											'in_the_loop' => true
										) );

										// Post Vendor Top Block
										if( $options[ 'sponsored_articles_location' ][ 'top' ] ) {
											boombox_get_template_part( 'template-parts/single/components/brand', '', array(
												'before' => '<aside class="bb-brand-block mb-sm bb-mb-el">',
												'after'  => '</aside>'
											) );
										}

										// Post Header ?>
										<header class="s-post-header entry-header bb-mb-el">
											<?php
											// Post Microdata
											boombox_get_template_part( 'template-parts/single/components/microdata', '', array(
												'microdata' => $options['microdata']
											) );

											// Post taxonomies
											if ( $options['elements']['categories'] || $options['elements']['badges'] ) {
												boombox_get_template_part( 'template-parts/single/components/taxonomy', '', array(
													'categories' => $options['elements']['categories'],
													'badges'     => $options['elements']['badges']
												) );
											}

											// Post title
											boombox_get_template_part( 'template-parts/single/components/title' );

											// Post sub title + Reading time
											boombox_get_template_part( 'template-parts/single/components/sub-title', '', array(
												'subtitle'          => $options['elements']['subtitle'],
												'reading_time'      => $options['reading_time'],
												'reading_time_size' => 'md'
											) );

											// Affiliate content
											boombox_get_template_part( 'template-parts/listings/content', 'affiliate', array(
												'class' => 's-post-affiliate bb-mb-el'
											) );

											// Separator
											boombox_get_template_part( 'template-parts/single/components/separator', '', array(
												'class' => 's-post-header-sep bb-mb-el'
											) ); ?>

										</header>

										<?php // Post Meta ?>
										<div class="s-post-meta-block bb-mb-el">
											<div class="post-meta-content row">
												<div class="d-table-center-sm">

													<?php
													// Post author mini card
													boombox_get_template_part( 'template-parts/single/components/mini-card', '', array(
														'author' => $options['elements']['author'],
														'avatar' => $options['elements']['author'],
														'date'   => $options['elements']['date'],
														'before' => '<div class="col-l d-table-cell col-md-6 col-sm-6 text-left-sm">',
														'after'  => '</div>',
													) );

													// Post metadata
													boombox_get_template_part( 'template-parts/single/components/metadata', '', array(
														'comments' => $options['elements']['comments_count'],
														'views'    => $options['elements']['views'],
														'before'   => '<div class="col-r d-table-cell col-md-6 col-sm-6 text-right-sm">',
														'after'    => '</div>',
													) ); ?>

												</div>
											</div>
										</div>

										<?php
										// Post Top Share Box
										if( $options[ 'share' ][ 'top' ] ) {
											boombox_get_template_part( 'template-parts/single/components/share', '', array(
												'heading' => false,
												'before'  => '<div class="s-post-share-box bb-post-share-box top bb-mb-el">',
												'after'   => '</div>'
											) );
										}

										// Post Media ( except image )
										boombox_get_template_part( 'template-parts/single/components/media/mix', '', array(
											'protect_content' => $options['protect_content'],
											'media'           => $options['featured_media'],
											'caption'         => $options['featured_caption'],
											'image_size'      => $options['image_size'],
										) );

										// Post Main Content
										boombox_get_template_part( 'template-parts/single/components/content', '', array(
											'protect_content'       => $options['protect_content'],
											'pagination_layout'     => $options['pagination_layout'],
											'has_secondary_sidebar' => $options['enable_secondary_sidebar']
										) );

										// Post Source
										boombox_get_template_part( 'template-parts/single/components/source', '', array(
											'protect_content' => $options['protect_content'],
										) );

										// Separator
										boombox_get_template_part( 'template-parts/single/components/separator', '', array(
											'class' => 'bb-mb-el'
										) );

										// Post Vendor Bottom Block
										if( $options[ 'sponsored_articles_location' ][ 'bottom' ] ) {
											boombox_get_template_part( 'template-parts/single/components/brand', '', array(
												'before' => '<aside class="bb-brand-block mb-sm bb-mb-el">',
												'after'  => '</aside>'
											) );
										}

										// Post Tags
										if( $options[ 'elements' ][ 'tags' ] ) {
											boombox_get_template_part( 'template-parts/single/components/post-tags' );
										}

										// Post Bottom Share Box
										if( $options[ 'share' ][ 'bottom' ] ) {
											boombox_get_template_part( 'template-parts/single/components/share', '', array(
												'heading' => true,
												'before'  => '<div class="s-post-share-box bb-post-share-box bottom mb-md bb-mb-el">',
												'after'   => '</div>'
											) );
										} ?>

									</div>
									<?php // -/end Post Main Content for Card View ?>

									<?php
									// Sortable sections
									boombox_get_template_part( 'template-parts/single/components/sortable' );
								} ?>

							</div>
						</div>
						
						<?php if ( $options[ 'enable_secondary_sidebar' ] ) {
							get_sidebar( 'secondary' );
						} ?>
					</div>
				</div>
				
				<?php if ( $options[ 'enable_primary_sidebar' ] ) {
					get_sidebar();
				} ?>
			</div>
		</div>
	</article>
	<?php do_action( 'boombox/after_template_content', 'single' ); ?>

</div>

<?php get_footer();