<?php

declare(strict_types=1);

namespace Contenir\Seo;

use Contenir\Contact\ContactSettings;
use Contenir\Seo\Head\ShareImage;
use Contenir\Seo\Internal\SettingsArray;
use Contenir\Seo\Organisation\Organisation;
use Contenir\Seo\Organisation\SocialProfiles;
use Contenir\Seo\Robots\RobotsDirective;
use Contenir\Seo\Robots\RobotsRules;
use Contenir\Seo\Sitemap\SitemapSettings;
use Contenir\Seo\Title\TitlePosition;
use Contenir\Seo\Title\TitleTemplate;
use Contenir\Seo\Verification\Token;
use Contenir\Seo\Verification\Verifier;
use Contenir\Seo\Visibility\Visibility;

use function trim;

/**
 * A site's SEO: how titles are built, the defaults pages fall back to,
 * whether this copy is hidden, verification tokens, the site's owner, its
 * sitemap and its robots.txt rules.
 *
 * @api
 */
final readonly class SeoConfig
{
    /**
     * @param array<value-of<Verifier>, string> $verification Tokens, by service
     * @param array<string, string>             $problems     What was wrong with each stored value left out, by setting key
     *
     * @mago-expect lint:excessive-parameter-list One parameter per part of the settings; all are optional.
     */
    public function __construct(
        public SiteUrl $site = new SiteUrl(),
        public TitleTemplate $title = new TitleTemplate(),
        public string $description = '',
        public ?ShareImage $shareImage = null,
        public RobotsDirective $robots = RobotsDirective::IndexFollow,
        public Visibility $visibility = Visibility::Show,
        public array $verification = [],
        public Organisation $organisation = new Organisation(),
        public SitemapSettings $sitemap = new SitemapSettings(),
        public RobotsRules $robotsRules = new RobotsRules(),
        public array $problems = [],
    ) {}

    /**
     * Only what a site had before the SEO add-on: its URL, its name and the
     * sitemap switch. Used when the add-on isn't enabled, so a site keeps
     * today's sitemap and robots.txt and nothing is hidden.
     *
     * @param array<array-key, mixed> $settings
     */
    public static function fromCoreSettings(array $settings): self
    {
        $values = new SettingsArray($settings);

        return new self(
            site: new SiteUrl($values->text(SettingKeys::SITE_URL)),
            title: new TitleTemplate(trim($values->text(SettingKeys::SITE_TITLE)), position: TitlePosition::None),
            sitemap: new SitemapSettings($values->flag(SettingKeys::SITEMAP_ENABLED, default: true)),
        );
    }

    /**
     * Reads the SEO values from a site's settings, as nested arrays
     * (['extensions' => ['seo' => ['site_name' => '…']]]). A verification
     * token or robots.txt rule that isn't valid is left out and its problem
     * kept.
     *
     * @param array<array-key, mixed> $settings
     */
    public static function fromSettings(array $settings): self
    {
        $values   = new SettingsArray($settings);
        $site     = new SiteUrl($values->text(SettingKeys::SITE_URL));
        $siteName = trim($values->text(SettingKeys::SITE_NAME));
        $siteName = '' === $siteName ? trim($values->text(SettingKeys::SITE_TITLE)) : $siteName;
        $problems = [];

        $verification = [];
        foreach (Verifier::cases() as $verifier) {
            $token   = Token::normalise($values->text($verifier->settingKey()));
            $problem = Token::problem($verifier, $token);
            if (null !== $problem) {
                $problems[$verifier->settingKey()] = $problem;
                continue;
            }

            if ('' !== $token) {
                $verification[$verifier->value] = $token;
            }
        }

        $rules        = $values->text(SettingKeys::ROBOTS_RULES);
        $ruleProblems = RobotsRules::problems($rules);
        if ([] !== $ruleProblems) {
            $problems[SettingKeys::ROBOTS_RULES] = $ruleProblems[0];
        }

        $separator = trim($values->text(SettingKeys::TITLE_SEPARATOR));
        $image     = $site->absolute($values->text(SettingKeys::SHARE_IMAGE));
        $alt       = trim($values->text(SettingKeys::SHARE_IMAGE_ALT));

        return new self(
            site: $site,
            title: new TitleTemplate(
                $siteName,
                '' === $separator ? TitleTemplate::SEPARATOR : $separator,
                TitlePosition::tryFrom($values->text(SettingKeys::TITLE_POSITION)) ?? TitlePosition::After,
                trim($values->text(SettingKeys::HOME_TITLE)),
            ),
            description: trim($values->text(SettingKeys::DESCRIPTION)),
            shareImage: null === $image ? null : new ShareImage($image, $alt),
            robots: RobotsDirective::parse($values->text(SettingKeys::ROBOTS)) ?? RobotsDirective::IndexFollow,
            visibility: Visibility::parse($values->text(SettingKeys::VISIBILITY)),
            verification: $verification,
            organisation: new Organisation(
                ContactSettings::fromSettings($settings),
                $site->absolute($values->text(SettingKeys::ORGANISATION_LOGO)),
                SocialProfiles::fromSettings($settings),
            ),
            sitemap: new SitemapSettings(
                $values->flag(SettingKeys::SITEMAP_ENABLED, default: true),
                SitemapSettings::workflows($values->text(SettingKeys::SITEMAP_EXCLUDE)),
            ),
            robotsRules: RobotsRules::parse($rules),
            problems: $problems,
        );
    }

    /**
     * The site's name, as titles and share previews give it.
     */
    public function siteName(): string
    {
        return $this->title->siteName;
    }

    /**
     * The config with another default share image: the same image with the
     * size a site measured, for example.
     */
    public function withShareImage(?ShareImage $image): self
    {
        return new self(
            $this->site,
            $this->title,
            $this->description,
            $image,
            $this->robots,
            $this->visibility,
            $this->verification,
            $this->organisation,
            $this->sitemap,
            $this->robotsRules,
            $this->problems,
        );
    }
}
