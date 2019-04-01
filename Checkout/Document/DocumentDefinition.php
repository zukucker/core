<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Document;

use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AttributesField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class DocumentDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'document';
    }

    public static function getCollectionClass(): string
    {
        return DocumentCollection::class;
    }

    public static function getEntityClass(): string
    {
        return DocumentEntity::class;
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('document_type_id', 'documentTypeId', DocumentTypeDefinition::class))->addFlags(new Required()),
            (new StringField('file_type', 'fileType'))->addFlags(new Required()),

            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(OrderDefinition::class, 'order_version_id'))->addFlags(new Required()),

            new JsonField('config', 'config'),
            new BoolField('sent', 'sent'),
            (new StringField('deep_link_code', 'deepLinkCode'))->addFlags(new Required()),
            (new CreatedAtField())->addFlags(new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),

            new AttributesField(),

            (new ManyToOneAssociationField('documentType', 'document_type_id', DocumentTypeDefinition::class, true))->addFlags(new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),
        ]);
    }
}
