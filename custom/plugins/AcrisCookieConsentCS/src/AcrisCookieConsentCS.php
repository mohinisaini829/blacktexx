<?php declare(strict_types=1);

namespace Acris\CookieConsent;

use Acris\CookieConsent\Custom\CookieEntity;
use Acris\CookieConsent\DependencyInjection\CompilerPass\CookieConsentCompilerPass;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\Indexer\InheritanceIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\MessageQueue\IndexerMessageSender;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\Snippet\SnippetEntity;

class AcrisCookieConsentCS extends Plugin
{
    const DEFAULT_SESSION_COOKIE = "^session-.*";
    const DEFAULT_CSRF_COOKIE = "csrf.*";
    const DEFAULT_TIMEZONE_COOKIE = "timezone";
    const DEFAULT_REMEMBER_COOKIE = "cookie-preference|acris_cookie_acc";
    const DEFAULT_CACHE_COOKIES = 'sw-cache-hash|sw-currency|sw-states';
    const DEFAULT_REMEMBER_LANDING_REFERRER = "acris_cookie_landing_page|acris_cookie_referrer";
    const DEFAULT_REMEMBER_FIRST_ACTIVATED = "acris_cookie_first_activated";

    const DEFAULT_FUNCTIONAL_GROUP_IDENTIFICATION = "functional";
    const DEFAULT_MARKETING_GROUP_IDENTIFICATION = "marketing";
    const DEFAULT_TRACKING_GROUP_IDENTIFICATION = "tracking";
    const DEFAULT_PERSONAL_GROUP_IDENTIFICATION = "personal";
    const DEFAULT_RATING_GROUP_IDENTIFICATION = "rating";
    const DEFAULT_SERVICE_GROUP_IDENTIFICATION = "service";


    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CookieConsentCompilerPass());
    }

    public function update(UpdateContext $updateContext): void
    {
        if($updateContext->getPlugin()->isActive() === true) {
            if((version_compare($updateContext->getCurrentPluginVersion(), '2.0.0', '<=') && version_compare($updateContext->getUpdatePluginVersion(), '2.0.0', '>'))
                || (version_compare($updateContext->getCurrentPluginVersion(), '2.4.0', '<=') && version_compare($updateContext->getUpdatePluginVersion(), '2.4.0', '>'))) {
                $this->insertDefaultData($updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '2.8.0', '<') && version_compare($updateContext->getUpdatePluginVersion(), '2.8.0', '>='))) {
                $this->insertDefaultData($updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '2.8.7', '<') && version_compare($updateContext->getUpdatePluginVersion(), '2.8.7', '>='))) {
                $this->updateDefaultData($updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '3.1.0', '<') && version_compare($updateContext->getUpdatePluginVersion(), '3.1.0', '>='))) {
                $this->updateCookieById('_ga|_gid|_gat_.+|_dc_gtm_UA-.+|ga-disable-UA-.+|__utm(a|b|c|d|t|v|x|z)|_gat|_swag_ga_.*|_gac.*', '_ga|_gid|_gat_.+|_dc_gtm_UA-.+|ga-disable-UA-.+|__utm(a|b|c|d|t|v|x|z)|_gat|_swag_ga_.*|_gac.*|_ga.*', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '3.3.1', '<') && version_compare($updateContext->getUpdatePluginVersion(), '3.3.1', '>='))) {
                $this->updateCookieById('paypalplus_session_v2|PYPF|paypal-offers–.+', 'paypalplus_session_v2|PYPF|paypal-offers.+', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '3.3.5', '<') && version_compare($updateContext->getUpdatePluginVersion(), '3.3.5', '>='))) {
                $this->updateCookieById('amazon-pay-abtesting-apa-migration|amazon-pay-abtesting-new-widgets|amazon-pay-connectedAuth|apay-session-set|apay-status-v2|amazon_Login_accessToken|amazon_Login_state_cache|amazon-pay-cors-blocked-status|language|apayLoginState|ledgerCurrency', 'amazon-pay-abtesting-apa-migration|amazon-pay-abtesting-new-widgets|amazon-pay-connectedAuth|apay-session-set|apay-status-v2|amazon_Login_accessToken|amazon_Login_state_cache|amazon-pay-cors-blocked-status|language|apayLoginState|ledgerCurrency', $updateContext->getContext());
                $this->updateCookieById('session-.*', '^session-.*', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '4.3.4', '<') && version_compare($updateContext->getUpdatePluginVersion(), '4.3.4', '>='))) {
                $this->updateCookieById('_hjid|_hjIncludedInSample|_hjShownFeedbackMessage|_hjDoneTestersWidgets|_hjMinimizedPolls|_hjDonePolls|_hjClosedSurveyInvites|_hjTLDTest|_hjCachedUserAttributes|_hjSessionResumed|_hjCookieTest', '_hjid|_hjIncludedInSample|_hjShownFeedbackMessage|_hjDoneTestersWidgets|_hjMinimizedPolls|_hjDonePolls|_hjClosedSurveyInvites|_hjTLDTest|_hjCachedUserAttributes|_hjSessionResumed|_hjCookieTest|hjIncludedInPageviewSample|_hjSessionUser.*|_hjSession_.*|_hjAbsoluteSessionInProgress|_hjIncludedInSessionSample|_hjFirstSeen', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '6.0.1', '<') && version_compare($updateContext->getUpdatePluginVersion(), '6.0.1', '>='))) {
                $this->updateCookieById('_gcl_aw|_gcl_dc', '_gcl_aw|_gcl_dc|_gcl_gb', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '6.3.5', '<') && version_compare($updateContext->getUpdatePluginVersion(), '6.3.5', '>='))) {
                $this->updateCookieById('__tawkuuid|TawkConnectionTime', 'tawk.+|twk_.+|_tawk.+|__tawk.+|Tawk.*', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '7.0.12', '<') && version_compare($updateContext->getUpdatePluginVersion(), '7.0.12', '>='))) {
                $this->updateCookieById('_gcl_aw|_gcl_dc|_gcl_gb', '_gcl_aw|_gcl_dc|_gcl_gb|_gcl_gs', $updateContext->getContext());
            }
            if((version_compare($updateContext->getCurrentPluginVersion(), '8.0.5', '<') && version_compare($updateContext->getUpdatePluginVersion(), '8.0.5', '>='))) {
                $this->updateCookieById('__stripe_mid|__stripe_sid', '__stripe_mid|__stripe_sid|card', $updateContext->getContext());
            }
        }

        if((version_compare($updateContext->getCurrentPluginVersion(), '3.3.0', '<') && version_compare($updateContext->getUpdatePluginVersion(), '3.3.0', '>='))) {
            $this->updateExistingSaveCookieSnippets($updateContext->getContext());
        }

        if((version_compare($updateContext->getCurrentPluginVersion(), '7.0.22', '<') && version_compare($updateContext->getUpdatePluginVersion(), '7.0.22', '>='))) {
            $this->setCookieNotDefault(self::DEFAULT_REMEMBER_LANDING_REFERRER);
        }
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        if($updateContext->getPlugin()->isActive() === true) {
            if((version_compare($updateContext->getCurrentPluginVersion(), '6.3.0', '<') && version_compare($updateContext->getUpdatePluginVersion(), '6.3.0', '>='))) {
                $this->addGoogleConsentModeCookies($updateContext->getContext());
            }
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        if ($context->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `acris_cookie_translation`');
        $connection->executeStatement('DROP TABLE IF EXISTS `acris_cookie_group_translation`');
        $connection->executeStatement('DROP TABLE IF EXISTS `acris_cookie_sales_channel`');
        try {
            $connection->executeStatement('ALTER TABLE `sales_channel` DROP `cookies`');
        } catch (\Exception $e) { }
        $connection->executeStatement('DROP TABLE IF EXISTS `acris_cookie`');
        $connection->executeStatement('DROP TABLE IF EXISTS `acris_cookie_group`');
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->insertDefaultData($activateContext->getContext());
        $this->updateCookieById('_ga|_gid|_gat_.+|_dc_gtm_UA-.+|ga-disable-UA-.+|__utm(a|b|c|d|t|v|x|z)|_gat|_swag_ga_.*|_gac.*', '_ga|_gid|_gat_.+|_dc_gtm_UA-.+|ga-disable-UA-.+|__utm(a|b|c|d|t|v|x|z)|_gat|_swag_ga_.*|_gac.*|_ga.*', $activateContext->getContext());
        $this->updateCookieById('paypalplus_session_v2|PYPF|paypal-offers–.+', 'paypalplus_session_v2|PYPF|paypal-offers.+', $activateContext->getContext());
        $this->updateCookieById('amazon-pay-abtesting-apa-migration|amazon-pay-abtesting-new-widgets|amazon-pay-connectedAuth|apay-session-set|apay-status-v2|amazon_Login_accessToken|amazon_Login_state_cache|amazon-pay-cors-blocked-status|language|apayLoginState|ledgerCurrency', 'amazon-pay-abtesting-apa-migration|amazon-pay-abtesting-new-widgets|amazon-pay-connectedAuth|apay-session-set|apay-status-v2|amazon_Login_accessToken|amazon_Login_state_cache|amazon-pay-cors-blocked-status|language|apayLoginState|ledgerCurrency', $activateContext->getContext());
        $this->updateCookieById('session-.*', '^session-.*', $activateContext->getContext());
    }

    private function addGoogleConsentModeCookies(Context $context): void
    {
        $knownCookies = parse_ini_file(self::getPath() . "/Components/Resources/optionalKnownCookies.ini", true);
        $googleCookies = [];

        foreach ($knownCookies as $cookieId => $knownCookie) {
            if($knownCookie["provider"] === "Google" and array_key_exists("googleCookieConsentMode", $knownCookie)){
                $googleCookieConsentMode = [];

                if(str_contains($knownCookie["googleCookieConsentMode"], "|")){
                    $googleCookieConsentModes = explode("|", $knownCookie["googleCookieConsentMode"]);

                    foreach($googleCookieConsentModes as $cookieConsentMode){
                        $googleCookieConsentMode[] = $cookieConsentMode;
                    }
                } else {
                    $googleCookieConsentMode[] = $knownCookie["googleCookieConsentMode"];
                }

                $googleCookies[] = [
                    "cookieId" => $cookieId,
                    "googleCookieConsentMode" => $googleCookieConsentMode
                ];
            }
        }

        if(empty($googleCookies)) return;

        /** @var EntityRepository $cookieRepository */
        $cookieRepository = $this->container->get("acris_cookie.repository");

        $cookies = $cookieRepository->search(new Criteria(), $context)->getElements();

        if(empty($cookies)) return;

        $updatedCookies = [];

        /** @var CookieEntity $cookie */
        foreach($cookies as $cookie) {
            if($cookie->getProvider() !== "Google") continue;

            foreach($googleCookies as $googleCookie){
                if(in_array($cookie->getCookieId(), $googleCookie, true)){
                    $updatedCookies[] = [
                        "cookieId" => $cookie->getCookieId(),
                        "googleCookieConsentMode" => $googleCookie["googleCookieConsentMode"]
                    ];
                }
            }
        }

        if(empty($updatedCookies)) return;

        foreach($updatedCookies as $updatedCookie){
            $this->updateEntityIfExists($cookieRepository, $context, "cookieId", $updatedCookie);
        }
    }

    private function insertDefaultData(Context $context): void
    {
        /** @var EntityRepository $cookieGroupRepository */
        $cookieGroupRepository = $this->container->get('acris_cookie_group.repository');

        $cookieGroups = [
            [
                'identification' => self::DEFAULT_FUNCTIONAL_GROUP_IDENTIFICATION,
                'translations' => [
                    'en-GB' => [
                        'title' => "Functional",
                        'description' => "Functional cookies are absolutely necessary for the functionality of the web shop. These cookies assign a unique random ID to your browser so that your unhindered shopping experience can be guaranteed over several page views.",
                    ],
                    'de-DE' => [
                        'title' => "Funktionale",
                        'description' => "Funktionale Cookies sind für die Funktionalität des Webshops unbedingt erforderlich. Diese Cookies ordnen Ihrem Browser eine eindeutige zufällige ID zu damit Ihr ungehindertes Einkaufserlebnis über mehrere Seitenaufrufe hinweg gewährleistet werden kann.",
                    ],
                    [
                        'title' => "Functional",
                        'description' => "Functional cookies are absolutely necessary for the functionality of the web shop. These cookies assign a unique random ID to your browser so that your unhindered shopping experience can be guaranteed over several page views.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true
            ],[
                'identification' => self::DEFAULT_MARKETING_GROUP_IDENTIFICATION,
                'translations' => [
                    'en-GB' => [
                        'title' => "Marketing",
                        'description' => "Marketing cookies are used to display advertisements on the website in a targeted and individualized manner across multiple page views and browser sessions.",
                    ],
                    'de-DE' => [
                        'title' => "Marketing",
                        'description' => "Marketing Cookies dienen dazu Werbeanzeigen auf der Webseite zielgerichtet und individuell über mehrere Seitenaufrufe und Browsersitzungen zu schalten.",
                    ],
                    [
                        'title' => "Marketing",
                        'description' => "Marketing cookies are used to display advertisements on the website in a targeted and individualized manner across multiple page views and browser sessions.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => false
            ],[
                'identification' => self::DEFAULT_TRACKING_GROUP_IDENTIFICATION,
                'translations' => [
                    'en-GB' => [
                        'title' => "Tracking",
                        'description' => "Tracking cookies help the shop operator to collect and evaluate information about the behaviour of users on their website.",
                    ],
                    'de-DE' => [
                        'title' => "Tracking",
                        'description' => "Tracking Cookies helfen dem Shopbetreiber Informationen über das Verhalten von Nutzern auf ihrer Webseite zu sammeln und auszuwerten.",
                    ],
                    [
                        'title' => "Tracking",
                        'description' => "Tracking cookies help the shop operator to collect and evaluate information about the behaviour of users on their website.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => false
            ],[
                'identification' => self::DEFAULT_PERSONAL_GROUP_IDENTIFICATION,
                'translations' => [
                    'en-GB' => [
                        'title' => "Personalization",
                        'description' => "These cookies are used to collect and process information about the use of the website by users, in order to subsequently personalise advertising and/or content in other contexts.",
                    ],
                    'de-DE' => [
                        'title' => "Personalisierung",
                        'description' => "Diese Cookies werden genutzt zur Erhebung und Verarbeitung von Informationen über die Verwendung der Webseite von Nutzern, um anschließend Werbung und/oder Inhalte in anderen Zusammenhängen, in weiterer Folge zu personalisieren.",
                    ],
                    [
                        'title' => "Personalization",
                        'description' => "These cookies are used to collect and process information about the use of the website by users, in order to subsequently personalise advertising and/or content in other contexts.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => false
            ],[
                'identification' => self::DEFAULT_RATING_GROUP_IDENTIFICATION,
                'translations' => [
                    'en-GB' => [
                        'title' => "Rating",
                        'description' => "These cookies are used to collect information about the use of the website and to link to previously collected information. This information is used to evaluate, understand and report on the use of the website services.",
                    ],
                    'de-DE' => [
                        'title' => "Bewertung",
                        'description' => "Diese Cookies dienen zur Erhebung von Informationen über die Nutzung der Webseite und die Verknüpfung mit zuvor erhobenen Informationen. Diese Informationen werden verwendet die Nutzung der Dienste der Webseite zu bewerten, zu verstehen und darüber zu berichten.",
                    ],
                    [
                        'title' => "Rating",
                        'description' => "These cookies are used to collect information about the use of the website and to link to previously collected information. This information is used to evaluate, understand and report on the use of the website services.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => false
            ],[
                'identification' => self::DEFAULT_SERVICE_GROUP_IDENTIFICATION,
                'translations' => [
                    'en-GB' => [
                        'title' => "Service",
                        'description' => "Service cookies are used to provide the user with additional offers (e.g. live chats) on the website. Information obtained via these service cookies may also be processed for site analysis.",
                    ],
                    'de-DE' => [
                        'title' => "Service",
                        'description' => "Service Cookies werden genutzt um dem Nutzer zusätzliche Angebote (z.B. Live Chats) auf der Webseite zur Verfügung zu stellen. Informationen, die über diese Service Cookies gewonnen werden, können möglicherweise auch zur Seitenanalyse weiterverarbeitet werden.",
                    ],
                    [
                        'title' => "Service",
                        'description' => "Service cookies are used to provide the user with additional offers (e.g. live chats) on the website. Information obtained via these service cookies may also be processed for site analysis.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => false
            ]
        ];

        foreach ($cookieGroups as $cookieGroup) {
            $this->createEntityIfNotExists($cookieGroupRepository, $context, 'identification', $cookieGroup);
        }

        $defaultCookieGroupId = $cookieGroupRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('isDefault', true))->setLimit(1), $context)->firstId();

        /** @var EntityRepository $cookieRepository */
        $cookieRepository = $this->container->get('acris_cookie.repository');

        $cookies = [
            [
                'cookieId' => self::DEFAULT_SESSION_COOKIE,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "Session",
                        'description' => "The session cookie stores your shopping data over several page views and is therefore essential for your personal shopping experience.",
                    ],
                    'de-DE' => [
                        'title' => "Session",
                        'description' => "Das Session Cookie speichert Ihre Einkaufsdaten über mehrere Seitenaufrufe hinweg und ist somit unerlässlich für Ihr persönliches Einkaufserlebnis.",
                    ],
                    [
                        'title' => "Session",
                        'description' => "The session cookie stores your shopping data over several page views and is therefore essential for your personal shopping experience.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true,
                'cookieGroupId' => $defaultCookieGroupId
            ],[
                'cookieId' => self::DEFAULT_CSRF_COOKIE,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "CSRF token",
                        'description' => "The CSRF token cookie contributes to your security. It strengthens the security of forms against unwanted hacker attacks.",
                    ],
                    'de-DE' => [
                        'title' => "CSRF-Token",
                        'description' => "Das CSRF-Token Cookie trägt zu Ihrer Sicherheit bei. Es verstärkt die Absicherung bei Formularen gegen unerwünschte Hackangriffe.",
                    ],
                    [
                        'title' => "CSRF token",
                        'description' => "The CSRF token cookie contributes to your security. It strengthens the security of forms against unwanted hacker attacks.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true,
                'cookieGroupId' => $defaultCookieGroupId
            ],[
                'cookieId' => self::DEFAULT_TIMEZONE_COOKIE,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "Timezone",
                        'description' => "The cookie is used to provide the system with the user's current time zone.",
                    ],
                    'de-DE' => [
                        'title' => "Zeitzone",
                        'description' => "Das Cookie wird verwendet um dem System die aktuelle Zeitzone des Benutzers zur Verfügung zu stellen.",
                    ],
                    [
                        'title' => "Timezone",
                        'description' => "The cookie is used to provide the system with the user's current time zone.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true,
                'cookieGroupId' => $defaultCookieGroupId
            ],[
                'cookieId' => self::DEFAULT_REMEMBER_COOKIE,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "Cookie settings",
                        'description' => "The cookie is used to store the cookie settings of the site user over several browser sessions.",
                    ],
                    'de-DE' => [
                        'title' => "Cookie Einstellungen",
                        'description' => "Das Cookie wird verwendet um die Cookie Einstellungen des Seitenbenutzers über mehrere Browsersitzungen zu speichern.",
                    ],
                    [
                        'title' => "Cookie settings",
                        'description' => "The cookie is used to store the cookie settings of the site user over several browser sessions.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true,
                'cookieGroupId' => $defaultCookieGroupId
            ],[
                'cookieId' => self::DEFAULT_CACHE_COOKIES,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "Cache handling",
                        'description' => "The cookie is used to differentiate the cache for different scenarios and page users.",
                    ],
                    'de-DE' => [
                        'title' => "Cache Behandlung",
                        'description' => "Das Cookie wird eingesetzt um den Cache für unterschiedliche Szenarien und Seitenbenutzer zu differenzieren.",
                    ],
                    [
                        'title' => "Cache handling",
                        'description' => "The cookie is used to differentiate the cache for different scenarios and page users.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true,
                'cookieGroupId' => $defaultCookieGroupId
            ],[
                'cookieId' => self::DEFAULT_REMEMBER_LANDING_REFERRER,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "Information on origin",
                        'description' => "The cookie stores the referrer and the first page visited by the user for further use.",
                    ],
                    'de-DE' => [
                        'title' => "Herkunftsinformationen",
                        'description' => "Das Cookie speichert die Herkunftsseite und die zuerst besuchte Seite des Benutzers für eine weitere Verwendung.",
                    ],
                    [
                        'title' => "Information on origin",
                        'description' => "The cookie stores the referrer and the first page visited by the user for further use.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => false,
                'cookieGroupId' => $defaultCookieGroupId
            ],[
                'cookieId' => self::DEFAULT_REMEMBER_FIRST_ACTIVATED,
                'provider' => 'Shopware',
                'active' => true,
                'unknown' => false,
                'translations' => [
                    'en-GB' => [
                        'title' => "Activated cookies",
                        'description' => "Saves which cookies have already been accepted by the user for the first time.",
                    ],
                    'de-DE' => [
                        'title' => "Aktivierte Cookies",
                        'description' => "Speichert welche Cookies bereits vom Benutzer zum ersten Mal akzeptiert wurden.",
                    ],
                    [
                        'title' => "Activated cookies",
                        'description' => "Saves which cookies have already been accepted by the user for the first time.",
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ],
                'isDefault' => true,
                'cookieGroupId' => $defaultCookieGroupId
            ]
        ];

        foreach ($cookies as $cookie) {
            $this->createEntityIfNotExists($cookieRepository, $context, 'cookieId', $cookie);
        }
    }

    private function updateDefaultData(Context $context): void
    {
        /** @var EntityRepository $cookieGroupRepository */
        $cookieGroupRepository = $this->container->get('acris_cookie_group.repository');

        $cookieGroups = [
            [
                'identification' => self::DEFAULT_FUNCTIONAL_GROUP_IDENTIFICATION,
                'title' => "Functional",
                'description' => "Functional cookies are absolutely necessary for the functionality of the web shop. These cookies assign a unique random ID to your browser so that your unhindered shopping experience can be guaranteed over several page views."
            ],[
                'identification' => self::DEFAULT_MARKETING_GROUP_IDENTIFICATION,
                'title' => "Marketing",
                'description' => "Marketing cookies are used to display advertisements on the website in a targeted and individualized manner across multiple page views and browser sessions."
            ],[
                'identification' => self::DEFAULT_TRACKING_GROUP_IDENTIFICATION,
                'title' => "Tracking",
                'description' => "Tracking cookies help the shop operator to collect and evaluate information about the behaviour of users on their website."
            ],[
                'identification' => self::DEFAULT_PERSONAL_GROUP_IDENTIFICATION,
                'title' => "Personalization",
                'description' => "These cookies are used to collect and process information about the use of the website by users, in order to subsequently personalise advertising and/or content in other contexts."
            ],[
                'identification' => self::DEFAULT_RATING_GROUP_IDENTIFICATION,
                'title' => "Rating",
                'description' => "These cookies are used to collect information about the use of the website and to link to previously collected information. This information is used to evaluate, understand and report on the use of the website services."
            ],[
                'identification' => self::DEFAULT_SERVICE_GROUP_IDENTIFICATION,
                'title' => "Service",
                'description' => "Service cookies are used to provide the user with additional offers (e.g. live chats) on the website. Information obtained via these service cookies may also be processed for site analysis."
            ]
        ];

        foreach ($cookieGroups as $cookieGroup) {
            $this->updateEntityIfExists($cookieGroupRepository, $context, 'identification', $cookieGroup);
        }

        /** @var EntityRepository $cookieRepository */
        $cookieRepository = $this->container->get('acris_cookie.repository');

        $cookies = [
            [
                'cookieId' => self::DEFAULT_SESSION_COOKIE,
                'title' => "Session",
                'description' => "The session cookie stores your shopping data over several page views and is therefore essential for your personal shopping experience."
            ],[
                'cookieId' => self::DEFAULT_CSRF_COOKIE,
                'title' => "CSRF token",
                'description' => "The CSRF token cookie contributes to your security. It strengthens the security of forms against unwanted hacker attacks."
            ],[
                'cookieId' => self::DEFAULT_TIMEZONE_COOKIE,
                'title' => "Timezone",
                'description' => "The cookie is used to provide the system with the user's current time zone."
            ],[
                'cookieId' => self::DEFAULT_REMEMBER_COOKIE,
                'title' => "Cookie settings",
                'description' => "The cookie is used to store the cookie settings of the site user over several browser sessions."
            ],[
                'cookieId' => self::DEFAULT_CACHE_COOKIES,
                'title' => "Cache handling",
                'description' => "The cookie is used to differentiate the cache for different scenarios and page users."
            ],[
                'cookieId' => self::DEFAULT_REMEMBER_LANDING_REFERRER,
                'title' => "Information on origin",
                'description' => "The cookie stores the referrer and the first page visited by the user for further use."
            ],[
                'cookieId' => self::DEFAULT_REMEMBER_FIRST_ACTIVATED,
                'title' => "Activated cookies",
                'description' => "Saves which cookies have already been accepted by the user for the first time."
            ]
        ];

        foreach ($cookies as $cookie) {
            $this->updateEntityIfExists($cookieRepository, $context, 'cookieId', $cookie);
        }
    }

    /**
     * @param EntityRepository $entityRepository
     * @param Context $context
     * @param string $identifierField
     * @param array $groupData
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function createEntityIfNotExists(EntityRepository $entityRepository, Context $context, string $identifierField, array $groupData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new EqualsFilter($identifierField, $groupData[$identifierField])), $context);
        if($exists->getTotal() === 0) {
            $entityRepository->create([$groupData], $context);
        }
    }

    /**
     * @param EntityRepository $entityRepository
     * @param Context $context
     * @param string $identifierField
     * @param array $groupData
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function updateEntityIfExists(EntityRepository $entityRepository, Context $context, string $identifierField, array $groupData): void
    {
        $exists = $entityRepository->search((new Criteria())->addFilter(new EqualsFilter($identifierField, $groupData[$identifierField])), $context);
        if($exists->getTotal() > 0) {
            $entity = $exists->first();
            $groupData['id'] = $entity->getId();
            $entityRepository->update([$groupData], $context);
        }
    }

    private function updateCookieById($oldCookieId, $newCookieId, Context $context): void
    {
        $cookieRepository = $this->container->get('acris_cookie.repository');
        $cookieUuid = $cookieRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('cookieId', $oldCookieId)), $context)->firstId();
        if($cookieUuid) {
            $cookieRepository->update([[
                'id' => $cookieUuid,
                'cookieId' => $newCookieId
            ]], $context);
        }
    }

    private function updateExistingSaveCookieSnippets(Context $context): void
    {
        $snippetRepository = $this->container->get('snippet.repository');
        $snippetResult = $snippetRepository->search((new Criteria())->addFilter(new ContainsFilter('value', 'cookieConsentAcceptButton')), $context);
        /** @var SnippetEntity $snippet */
        foreach ($snippetResult->getElements() as $snippet) {
            if(strpos($snippet->getValue(), 'cookieConsentAcceptButton') !== false) {
                $snippet->setValue(str_replace('cookieConsentAcceptButton', 'ccAcceptButton', $snippet->getValue()));
                $snippetRepository->upsert([[
                    'id' => $snippet->getId(),
                    'value' => $snippet->getValue()
                ]], $context);
            }
        }
    }

    private function setCookieNotDefault(string $cookieId): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $connection->executeQuery('UPDATE acris_cookie SET is_default = 0 WHERE cookie_id = :cookieId', ['cookieId' => $cookieId]);
    }
}
