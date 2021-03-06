<?php declare(strict_types=1);

namespace Shopware\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1590758953ProductFeatureSet extends MigrationStep
{
    public const TRANSLATIONS = [
        'en-GB' => [
            'name' => 'Default',
            'description' => 'Default template highlighting the product\'s price',
        ],
        'de-DE' => [
            'name' => 'Standard',
            'description' => 'Standardtemplate, hebt den Preis des Produkts hervor',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1590758953;
    }

    public function update(Connection $connection): void
    {
        $defaultFeatureSetId = Uuid::randomBytes();

        $this->createTables($connection);

        $this->insertDefaultFeatureSet($connection, $defaultFeatureSetId);
        $this->insertDefaultFeatureSetTranslations($connection, $defaultFeatureSetId);
        $this->assignDefaultFeatureSet($connection, $defaultFeatureSetId);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function createTables(Connection $connection): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `product_feature_set` (
    `id`         BINARY(16)  NOT NULL,
    `features`   JSON        NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.product_feature_set.features` CHECK (JSON_VALID(`features`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_feature_set_translation` (
    `product_feature_set_id` BINARY(16)   NOT NULL,
    `language_id`            BINARY(16)   NOT NULL,
    `name`                   VARCHAR(255) NULL,
    `description`            MEDIUMTEXT   NULL,
    `created_at`             DATETIME(3)  NOT NULL,
    `updated_at`             DATETIME(3)  NULL,
    PRIMARY KEY (`product_feature_set_id`, `language_id`),
    CONSTRAINT `fk.product_feature_set_translation.language_id` FOREIGN KEY (`language_id`)
        REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.product_feature_set_translation.product_feature_set_id` FOREIGN KEY (`product_feature_set_id`)
        REFERENCES `product_feature_set` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_feature` (
    `product_feature_set_id` BINARY(16) NOT NULL,
    `product_id`             BINARY(16) NOT NULL,
    PRIMARY KEY (`product_feature_set_id`, `product_id`),
    CONSTRAINT `fk.product_feature.product_feature_set_id` FOREIGN KEY (`product_feature_set_id`)
        REFERENCES `product_feature_set` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk.product_feature.product_id` FOREIGN KEY (`product_id`)
        REFERENCES `product` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeUpdate($sql);
    }

    private function insertDefaultFeatureSet(Connection $connection, string $featureSetId): void
    {
        $connection->insert(
            ProductFeatureSetDefinition::ENTITY_NAME,
            $this->getDefaultFeatureSet($featureSetId)
        );
    }

    private function insertDefaultFeatureSetTranslations(Connection $connection, string $featureSetId): void
    {
        $languages = $this->fetchLanguageIds($connection, ['en-GB']);
        $languages[] = hex2bin(Defaults::LANGUAGE_SYSTEM);
        $languages = array_unique($languages);

        $sql = <<<'SQL'
REPLACE INTO `product_feature_set_translation` (`product_feature_set_id`, `language_id`, `name`, `description`, `created_at`)
VALUES (:product_feature_set_id, :language_id, :name, :description, :created_at);
SQL;

        foreach ($languages as $language) {
            $connection->executeUpdate(
                $sql,
                $this->getDefaultFeatureSetTranslation(
                    $featureSetId,
                    $language,
                    self::TRANSLATIONS['en-GB']
                )
            );
        }

        $languages = $this->fetchLanguageIds($connection, ['de-DE']);

        foreach ($languages as $language) {
            $connection->executeUpdate(
                $sql,
                $this->getDefaultFeatureSetTranslation(
                    $featureSetId,
                    $language,
                    self::TRANSLATIONS['de-DE']
                )
            );
        }
    }

    private function assignDefaultFeatureSet(Connection $connection, string $featureSetId): void
    {
        $sql = <<<'SQL'
INSERT INTO `product_feature` (`product_feature_set_id`, `product_id`)
SELECT :feature_set_id, id
FROM `product`;
SQL;

        $connection->executeUpdate(
            $sql,
            [
                'feature_set_id' => $featureSetId,
            ]
        );
    }

    private function getDefaultFeatureSet(string $featureSetId): array
    {
        return [
            'id' => $featureSetId,
            'features' => json_encode([
                'type' => 'product',
                'id' => 'referencePrice',
                'position' => 1,
            ]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];
    }

    private function getDefaultFeatureSetTranslation(string $featureSetId, string $languageId, array $translation): array
    {
        return [
            'product_feature_set_id' => $featureSetId,
            'language_id' => $languageId,
            'name' => $translation['name'],
            'description' => $translation['description'],
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];
    }

    private function fetchLanguageIds(Connection $connection, array $localeCodes): array
    {
        $sql = <<<'SQL'
SELECT lang.id
FROM language lang
INNER JOIN locale loc
ON lang.translation_code_id = loc.id AND loc.code IN (:locale_codes);
SQL;

        $languageId = $connection->fetchColumn(
            $sql,
            [
                'locale_codes' => implode(', ', $localeCodes),
            ]
        );

        if (is_array($languageId)) {
            return $languageId;
        }

        return [$languageId];
    }
}
