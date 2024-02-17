<?php

declare(strict_types=1);

namespace Symfony\Flex\Configurator;

use Symfony\Flex\Lock;
use Symfony\Flex\Recipe;
use Symfony\Flex\Update\RecipeUpdate;

class ModifyXmlConfigurator extends AbstractConfigurator
{

    /**
     * @var array<string, \DOMDocument>
     */
    private array $files = [];
    
    public const ACTION_REPLACE_TEXT_VALUE = 'replace';

    public const ACTION_UPSERT_ATTRIBUTE_VALUE = 'upsert-attribute';
    
    public function configure(Recipe $recipe, $config, Lock $lock, array $options = [])
    {
        foreach($config as $patch) {
           $domDocument = $this->getDomDocument($patch['file']);

           $elements = $this->findElements($domDocument, $patch['xpath'], $patch['default-namespace'] ?? null);

           if(!$elements) {
               continue;
           }

            $this->handleAction($elements, $patch);
        }
        
        $this->finalize();
    }

    public function unconfigure(Recipe $recipe, $config, Lock $lock)
    {
        // TODO: Implement unconfigure() method.
    }

    public function update(RecipeUpdate $recipeUpdate, array $originalConfig, array $newConfig): void
    {
        // TODO: Implement update() method.
    }

    public function getDomDocument(string $filePath): \DOMDocument
    {
        if(!isset($this->files[$filePath])) {
            $file = $this->buildFilepath($filePath);

            $domDocument = new \DOMDocument();
            $domDocument->load($file);
            $this->files[$filePath] = $domDocument;
        }
        return $domDocument;
    }

    private function findElements(\DOMDocument $domDocument, string $xpathQuery, ?string $defaultNamespaceName = null): ?\DOMNodeList 
    {
        $xpath = new \DOMXPath($domDocument);
        $namespace = $domDocument->lookupNamespaceUri($domDocument->namespaceURI);
        if($namespace !== null) {
            $xpath->registerNamespace($defaultNamespaceName ?? 'defaultNS', $namespace);
        }

        $result = $xpath->query($xpathQuery);
        if($result === false) {
            return null; 
        }
        return $result;
    }

    private function handleAction(\DOMNodeList $elements, array $patch): void
    {
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            
            if($patch['action'] === self::ACTION_REPLACE_TEXT_VALUE) {
                $element->textContent = $patch['value'];
            }

            if($patch['action'] === self::ACTION_UPSERT_ATTRIBUTE_VALUE) {
                $element->setAttribute($patch['attribute'], $patch['value']);
            }

        }
    }
    
    private function finalize(): void {
       
        foreach($this->files as $file => $domDocument) {
            $filePath = $this->buildFilepath($file);
            $domDocument->save($filePath);
        }
        
    }
    
    private function buildFilepath(string $filePath): string {
        return $this->path->concatenate([$this->options->get('root-dir'), $this->options->expandTargetDir($filePath)]);
    }

}