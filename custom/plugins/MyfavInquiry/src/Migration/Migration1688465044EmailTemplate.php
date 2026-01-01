<?php declare(strict_types=1);

namespace Myfav\Inquiry\Migration;

use DateTime;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Migration1688465044EmailTemplate
 */
class Migration1688465044EmailTemplate extends MigrationStep
{
    private string $emailTechnicalName = "myfav_inquiry_request";

    /**
     * getCreationTimestamp
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1688465044;
    }

    /**
     * update
     *
     * @param  mixed $connection
     * @return void
     */
    public function update(Connection $connection): void
    {
        $mailTemplateTypeId = $this->createMailTemplateType($connection);
        $this->createMailTemplate($connection, $mailTemplateTypeId);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    /**
     * createMailTemplateType
     *
     * @param  mixed $connection
     * @return string
     */
    private function createMailTemplateType(Connection $connection): string
    {
        $mailTemplateTypeId = Uuid::randomHex();

        $enGbLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deDeLangId = $this->getLanguageIdByLocale($connection, 'de-DE');

        $englishName = 'New custom inquiry';
        $germanName = 'Neue individuelle Anfrage';

        $connection->executeStatement("
            INSERT IGNORE INTO `mail_template_type`
                (id, technical_name, available_entities, created_at)
            VALUES
                (:id, :technicalName, :availableEntities, :createdAt)
        ",[
            'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'technicalName' => $this->emailTechnicalName,
            'availableEntities' => json_encode(['inquiry' => 'inquiry']),
            'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        if (!empty($enGbLangId)) {
            $connection->executeStatement("
            INSERT IGNORE INTO `mail_template_type_translation`
                (mail_template_type_id, language_id, name, created_at)
            VALUES
                (:mailTemplateTypeId, :languageId, :name, :createdAt)
            ",[
                'mailTemplateTypeId' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'languageId' => $enGbLangId,
                'name' => $englishName,
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if (!empty($deDeLangId)) {
            $connection->executeStatement("
            INSERT IGNORE INTO `mail_template_type_translation`
                (mail_template_type_id, language_id, name, created_at)
            VALUES
                (:mailTemplateTypeId, :languageId, :name, :createdAt)
            ",[
                'mailTemplateTypeId' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'languageId' => $deDeLangId,
                'name' => $germanName,
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        return $mailTemplateTypeId;
    }
    
    /**
     * getLanguageIdByLocale
     *
     * @param  mixed $connection
     * @param  mixed $locale
     * @return string
     */
    private function getLanguageIdByLocale(Connection $connection, string $locale): ?string
    {
        $sql = <<<SQL
        SELECT `language`.`id`
        FROM `language`
        INNER JOIN `locale` ON `locale`.`id` = `language`.`locale_id`
        WHERE `locale`.`code` = :code
        SQL;

        $languageId = $connection->executeQuery($sql, ['code' => $locale])->fetchOne();

        if (empty($languageId)) {
            return null;
        }

        return $languageId;
    }
    
    /**
     * createMailTemplate
     *
     * @param  mixed $connection
     * @param  mixed $mailTemplateTypeId
     * @return void
     */
    private function createMailTemplate(Connection $connection, string $mailTemplateTypeId): void
    {
        $mailTemplateId = Uuid::randomHex();

        $enGbLangId = $this->getLanguageIdByLocale($connection, 'en-GB');
        $deDeLangId = $this->getLanguageIdByLocale($connection, 'de-DE');

        $connection->executeStatement("
        INSERT IGNORE INTO `mail_template`
            (id, mail_template_type_id, system_default, created_at)
        VALUES
            (:id, :mailTemplateTypeId, :systemDefault, :createdAt)
        ",[
            'id' => Uuid::fromHexToBytes($mailTemplateId),
            'mailTemplateTypeId' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'systemDefault' => 0,
            'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        if (!empty($enGbLangId)) {
            $connection->executeStatement("
            INSERT IGNORE INTO `mail_template_translation`
                (mail_template_id, language_id, sender_name, subject, description, content_html, content_plain, created_at)
            VALUES
                (:mailTemplateId, :languageId, :senderName, :subject, :description, :contentHtml, :contentPlain, :createdAt)
            ",[
                'mailTemplateId' => Uuid::fromHexToBytes($mailTemplateId),
                'languageId' => $enGbLangId,
                'senderName' => '{{ salesChannel.name }}',
                'subject' => 'New request',
                'description' => 'E-Mail that is sent, when a user sends a custom request (anfrage).',
                'contentHtml' => $this->getContentHtmlEn(),
                'contentPlain' => $this->getContentPlainEn(),
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

        if (!empty($deDeLangId)) {
            $connection->executeStatement("
            INSERT IGNORE INTO `mail_template_translation`
                (mail_template_id, language_id, sender_name, subject, description, content_html, content_plain, created_at)
            VALUES
                (:mailTemplateId, :languageId, :senderName, :subject, :description, :contentHtml, :contentPlain, :createdAt)
            ",[
                'mailTemplateId' => Uuid::fromHexToBytes($mailTemplateId),
                'languageId' => $deDeLangId,
                'senderName' => '{{ salesChannel.name }}',
                'subject' => 'Neue Anfrage',
                'description' => 'Beispiel E-Mail Template Beschreibung',
                'contentHtml' => $this->getContentHtmlDe(),
                'contentPlain' => $this->getContentPlainDe(),
                'createdAt' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }

    }
    
    /**
     * getContentHtmlEn
     *
     * @return string
     */
    private function getContentHtmlEn(): string
    {
        return <<<MAIL
        <div style="font-family:arial; font-size:12px;">
            <p>
            A new request was sent via the inquiry-list:
            </p>
        </div>
        MAIL;
    }
    
    /**
     * getContentPlainEn
     *
     * @return string
     */
    private function getContentPlainEn(): string
    {
        return <<<MAIL
        A new request was sent via the inquiry-list:
        MAIL;
    }
    
    /**
     * getContentHtmlDe
     *
     * @return string
     */
    private function getContentHtmlDe(): string
    {
        return <<<MAIL
        <div style="font-family:arial; font-size:12px;">
            <p>
                Es wurde eine neue Anfrage über das Anfrage-Formular gesendet:<br/>
                <br/>
                Anrede: {{ inquiry.salutation.displayName }}<br/>
                Vorname: {{ inquiry.firstName }}<br/>
                Nachname: {{ inquiry.lastName }}<br/>
                E-Mail: {{ inquiry.email }}<br/>
                Firma: {{ inquiry.company }}<br/>
                Telefonnummer: {{ inquiry.phoneNumber }}<br/>
                Lieferdatum: {% if inquiry.deliveryDate is not null %}{{ inquiry.deliveryDate|date('d.m.Y') }}{% else %}Nicht angegeben{% endif %}<br/>
                <br/>
                Kommentar: {{ inquiry.comment }}<br/>
                <br/>
                Angefragte Artikel:<br/>
                <br/>
                {% for lineItem in inquiry.lineItems %}
                    {% if not loop.first %}<hr/>{% endif %}
                    {% if lineItem.productId is null %}
                        Artikel-Name: {{ lineItem.extensions.myfavZweidehProductData.productName }}<br />
                        Größe: {{ lineItem.extensions.myfavZweidehProductData.productSize }}<br />
                        Besonderheit: Individuell gestaltet<br />
                        Stückzahl: {{ lineItem.quantity }}<br />
                        Preis: Auf Anfrage<br />
                        Artikel-Bild:<br/>
                        <img src="{{ url('frontend.home.page') }}{{ lineItem.extensions.myfavZweidehProductData.imageUrl }}" style="max-width: 150px;" /><br />
                    {% else %}
                        Artikel-Nr.: {{ lineItem.product.productNumber }}<br/>
                        Artikel-Name: {{ lineItem.product.name }}<br/>
                        Stückzahl: {{ lineItem.quantity }}<br/>
                        Preis: {{ lineItem.price }}<br/>
                        <br/>
                        Artikel-Bild:<br/>
                        {% if lineItem.product.media|first is not null %}
                            {% set firstMedia = lineItem.product.media|first %}
                            <a href="{{ firstMedia.media.url }}"><img src="{{ firstMedia.media.url }}" style="max-width: 150px;"/></a><br/>
                        {% endif %}
                    {% endif %}
                {% endfor %}
            </p>
        </div>
        MAIL;
    }
    
    /**
     * getContentPlainDe
     *
     * @return string
     */
    private function getContentPlainDe(): string
    {
        return <<<MAIL
        Es wurde eine neue Anfrage über das Anfrage-Formular gesendet:

        Anrede: {{ inquiry.salutation.displayName }}
        Vorname: {{ inquiry.firstName }}
        Nachname: {{ inquiry.lastName }}
        E-Mail: {{ inquiry.email }}
        Firma: {{ inquiry.company }}
        Telefonnummer: {{ inquiry.phoneNumber }}
        Lieferdatum: {% if inquiry.deliveryDate is not null %}{{ inquiry.deliveryDate|date('d.m.Y') }}{% else %}Nicht angegeben{% endif %}

        Kommentar: {{ inquiry.comment }}

        Angefragte Artikel:

        {% for lineItem in inquiry.lineItems %}
            {% if lineItem.productId is null %}
                Artikel-Name: {{ lineItem.extensions.myfavZweidehProductData.productName }}
                Größe: {{ lineItem.extensions.myfavZweidehProductData.productSize }}
                Besonderheit: Individuell gestaltet
                Stückzahl: {{ lineItem.quantity }}
                Preis: Auf Anfrage
                Artikel-Bild:
                {{ url('frontend.home.page') }}{{ lineItem.extensions.myfavZweidehProductData.imageUrl }}
            {% else %}
                {% if not loop.first %}------------------------------------{% endif %}
            
                Artikel-Nr.: {{ lineItem.product.productNumber }}
                Artikel-Name.: {{ lineItem.product.name }}
                Stückzahl: {{ lineItem.quantity }}
                Preis: {{ lineItem.price }}
                
                Artikel-Bild:
                {% if lineItem.product.media|first is not null %}
                    {% set firstMedia = lineItem.product.media|first %} {{ firstMedia.media.url }}
                {% endif %}
            {% endif %}
        {% endfor %}
        MAIL;
    }
}