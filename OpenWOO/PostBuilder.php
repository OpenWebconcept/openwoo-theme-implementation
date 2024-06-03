<?php

namespace OpenWOO;

class PostBuilder
{
    private array $formValues = [];

    public function __construct(array $formValues)
    {
        $this->formValues = $formValues;
    }

    public static function make(array $formValues): self
    {
        return new static($formValues);
    }

    public function save(): int
    {
        $postId = $this->insertPost();
        
        if (0 === $postId) {
            return 0;
        }
        
        $this->saveMeta($postId, $this->getMetaValues());

        return $postId;
    }

    private function insertPost(): int
    {
        $data = [
            'post_title'   => $this->formValues['Onderwerp'],
            'post_excerpt' => $this->formValues['Samenvatting'],
            'post_content' => '',
            'post_status'  => 'draft',
            'post_type'    => 'openwoo-item',
        ];

        return \wp_insert_post($data);
    }

    private function getMetaValues(): array
    {
        return MetaBuilder::make($this->formValues)->get();
    }

    private function saveMeta(int $postId, array $metaValues): void
    {
        foreach ($metaValues as $key => $value) {
            \update_post_meta($postId, $key, $value);
        }
    }
}
