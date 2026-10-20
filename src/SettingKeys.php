<?php

declare(strict_types=1);

namespace Contenir\Seo;

/**
 * Where a site's settings keep each SEO value, as dotted keys. Values the
 * CMS already had keep their keys (the site's name and URL, the sitemap
 * switch, the Google verification token), so moving to the add-on changes
 * nothing on a site; the rest sit under extensions.seo, the add-on's own
 * namespace. The social profiles are the Social add-on's keys, read as
 * plain data.
 *
 * @api
 */
final class SettingKeys
{
    public const string SITE_TITLE = 'site.title';

    public const string SITE_URL = 'site.url';

    public const string SITE_NAME = 'extensions.seo.site_name';

    public const string TITLE_SEPARATOR = 'extensions.seo.title_separator';

    public const string TITLE_POSITION = 'extensions.seo.title_position';

    public const string HOME_TITLE = 'extensions.seo.home_title';

    public const string DESCRIPTION = 'extensions.seo.description';

    public const string SHARE_IMAGE = 'extensions.seo.share_image';

    public const string SHARE_IMAGE_ALT = 'extensions.seo.share_image_alt';

    public const string ROBOTS = 'extensions.seo.robots';

    /**
     * Kept in the environment's local settings, never in a committed file.
     */
    public const string VISIBILITY = 'extensions.seo.visibility';

    public const string VERIFY_GOOGLE = 'google.site_verification';

    public const string VERIFY_BING = 'extensions.seo.verify_bing';

    public const string VERIFY_PINTEREST = 'extensions.seo.verify_pinterest';

    public const string VERIFY_META = 'extensions.seo.verify_meta';

    public const string ORGANISATION_NAME = 'extensions.seo.organisation_name';

    public const string ORGANISATION_TYPE = 'extensions.seo.organisation_type';

    public const string ORGANISATION_LOGO = 'extensions.seo.organisation_logo';

    public const string SITEMAP_ENABLED = 'site.sitemap.enabled';

    public const string SITEMAP_EXCLUDE = 'extensions.seo.sitemap_exclude';

    public const string ROBOTS_RULES = 'extensions.seo.robots_rules';

    /**
     * The Social add-on's profile URLs, by network. Twitter, Instagram and
     * Pinterest held handles before the Social add-on; SocialProfiles turns
     * a handle into its profile URL.
     */
    public const array SOCIAL_PROFILES = [
        'facebook'  => 'social.social_facebook',
        'instagram' => 'social.social_instagram',
        'linkedin'  => 'social.social_linkedin',
        'x'         => 'social.social_twitter',
        'youtube'   => 'social.social_youtube',
        'tiktok'    => 'social.social_tiktok',
        'pinterest' => 'social.social_pinterest',
        'threads'   => 'social.social_threads',
        'bluesky'   => 'social.social_bluesky',
        'mastodon'  => 'social.social_mastodon',
    ];
}
