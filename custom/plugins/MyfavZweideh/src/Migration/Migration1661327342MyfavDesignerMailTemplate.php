<?php declare(strict_types=1);

namespace Myfav\Zweideh\Migration;

use DateTime;
use Doctrine\DBAL\Connection;
use Myfav\Zweideh\MyfavZweideh;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1661327342MyfavDesignerMailTemplate extends MigrationStep
{    
    /**
     * getCreationTimestamp
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1661327342;
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
    
    /**
     * updateDestructive
     *
     * @param  mixed $connection
     * @return void
     */
    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
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

        $englishName = 'Design Request Mail';
        $germanName = 'Design-Anfrage';

        $connection->executeStatement("
            INSERT IGNORE INTO `mail_template_type`
                (id, technical_name, available_entities, created_at)
            VALUES
                (:id, :technicalName, :availableEntities, :createdAt)
        ",[
            'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'technicalName' => MyfavZweideh::PLUGIN_CONFIG . 'requestFormMail',
            'availableEntities' => json_encode([]),
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
                'subject' => 'Design-Print Request',
                'description' => 'Sent, when a customer requests a print for a design he made.',
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
                'subject' => 'Druck-Anfrage für ein Design',
                'description' => 'Wird gesendet, wenn eine Anfrage für den Druck eines Designs erstellt wird.',
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
                New request for printing a design was made, that was created by the designer.
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
        New request for a design was made.
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
                Es wurde eine neue Anfrage für den Druck eines Designs gestellt, das über den Designer erstellt wurde.
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
        Es wurde eine neue Anfrage für den Druck eines Designs gestellt, das über den Designer erstellt wurde.
        MAIL;
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
        if (!$languageId && $locale !== 'en-GB') {
            return null;
        }
        if (!$languageId) {
            return Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        }
        return $languageId;
    }
}
