<?php

namespace Biloba\ArticleVariantOrderMatrix;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;

class BilobaArticleVariantOrderMatrix extends Plugin
{
    public function install(InstallContext $context): void
    {
        $this->setupCustomAttributes($context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->setupCustomAttributes($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        $this->removeCustomAttributes($context->getContext());
    }

    public function activate(ActivateContext $context): void
    {
        $this->setupCustomAttributes($context->getContext());
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context): void
    {
       parent::deactivate($context);
    }

    public function setupCustomAttributes(Context $context): void {
    // generate UUID (https://www.guidgenerator.com/online-guid-generator.aspx)
	/** @var EntityRepository $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
	    $idCustomFieldSetProductInfo = '14fc61b1c546485d9fcaa61d651d31ab';
        $idCustomFieldSetProductInfo = [
            'id' => $idCustomFieldSetProductInfo,
            'name' => 'biloba_variant_matrix',
            'config' => ["label" => ["en-GB" => "Biloba Variant Matrix", "de-DE" => "Biloba Varianten Matrix"], "translated" => "true"],
            'active' => boolval(1)
        ];
        $customFieldSetRepository->upsert([$idCustomFieldSetProductInfo], $context);
        
	/** @var EntityRepository $customFieldSetRelationRepository */
        $customFieldSetRelationRepository = $this->container->get('custom_field_set_relation.repository');
        $data = [
           'id' => '9fd9daa98cd5410c8c7e5125c6c902dc',
           'customFieldSet' => $idCustomFieldSetProductInfo,
           'entityName' => 'product'
        ];
        
        $customFieldSetRelationRepository->upsert([$data] , $context);


	/** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('custom_field.repository'); 
        $data = [
            // Product Info
            [
                'id' => 'f8f20085fa644f0ea76fa6e0560172e0',
                'name' => 'biloba_variant_matrix_hide',
                'type' => 'bool',
                'config' => ["label" => ["en-GB" => "Disable Biloba Variant Matrix", "de-DE" => "Biloba Varianten Matrix deaktivieren"], "helpText" => ["en-GB" => null, "de-DE" => null], "componentName" => "sw-field", "customFieldType" => "switch", "customFieldPosition" => 1],
                'active' => boolval(1),
                'customFieldSet' => $idCustomFieldSetProductInfo
            ],

            // Matrix Groups
            [
                'id' => '4313c39f9225402bbf241e050e53f79e',
                'name' => 'biloba_variant_matrix_selector_beakpoints_values',
                'type' => 'text',
                'config' => ["type" => "text", "label" => ["en-GB" => "Set table grouping breakpoints", "de-DE" => "Tabellengruppierung Breakpoints festlegen"], "helpText" => ["en-GB" => "Overwrites app config", "de-DE" => "Überschreibt App Konfiguration"], "componentName" => "sw-field", "customFieldType" => "text", "customFieldPosition" => 2],
                'active' => boolval(1),
                'customFieldSet' => $idCustomFieldSetProductInfo
            ],
            [
                'id' => '9aee67f7efe546f6b9f53d55926c9bca',
                'name' => 'biloba_variant_matrix_selector_beakpoints_names',
                'type' => 'text',
                'config' => ["type" => "text", "label" => ["en-GB" => "Table Grouping Set Designations", "de-DE" => "Tabellengruppierung Bezeichnungen festlegen"], "helpText" => ["en-GB" => "Overwrites app config", "de-DE" => "Überschreibt App Konfiguration"], "componentName" => "sw-field", "customFieldType" => "text", "customFieldPosition" => 3],
                'active' => boolval(1),
                'customFieldSet' => $idCustomFieldSetProductInfo
            ],
    ];
    
    $customFieldRepository->upsert($data, $context);
    
    }
    
    public function removeCustomAttributes(Context $context): void {
        //Removing custom_field_set
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository ->delete(
            [
                ['id' => '14fc61b1c546485d9fcaa61d651d31ab'],
                ['id' => '4313c39f9225402bbf241e050e53f79e'],
                ['id' => '9aee67f7efe546f6b9f53d55926c9bca'],
            ],
            $context
        );
    }
}