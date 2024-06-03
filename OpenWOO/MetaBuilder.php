<?php

namespace OpenWOO;

use Yard\OpenWOO\Traits\GravityFormsUploadToMediaLibrary;

class MetaBuilder
{
    use GravityFormsUploadToMediaLibrary;
    
    private array $formValues = [];

    public function __construct(array $formValues)
    {
        $this->formValues = $formValues;
    }

    public static function make(array $formValues): self
    {
        return new static($formValues);
    }

    public function get(): array
    {
        $metaValues = [];

        foreach ($this->formValues as $key => $value) {
            $key   = $this->getKey($key);
            $value = $this->getMetaField($key, $value);

            $metaValues[$key] = $value;
        }

        $metaValues = $this->convertToAddressGroup($metaValues);
        $metaValues = $this->convertToThemeGroup($metaValues);
        $metaValues = $this->convertToAttachmentGroup($metaValues);
        $metaValues = $this->convertInformationGroup($metaValues);
        $metaValues = $this->convertGeographicPosition($metaValues);
        $metaValues = $this->convertCoords($metaValues);

        return $metaValues;
    }

    protected function convertToAddressGroup(array $metaValues): array
    {
        $metaValues['woo_Adres'] = [
            [
                'woo_Straat__huisnummer' => $metaValues['woo_Straat__huisnummer'],
                'woo_Postcode' => $metaValues['woo_Postcode'],
                'woo_Stad' => $metaValues['woo_Stad']
            ]
        ];

        $toUnset = [
            'woo_Straat__huisnummer',
            'woo_Adresregel_2',
            'woo_Stad',
            'woo_Staat__provincie',
            'woo_Postcode',
            'woo_Land'
        ];

        return $this->unsetArrayItems($toUnset, $metaValues);
    }

    protected function convertToThemeGroup(array $metaValues): array
    {
        $metaValues['woo_Themas'] = [
            [
                'woo_Hoofdthema' => $metaValues['woo_Hoofdthema'] ?? '',
                'woo_Subthema' => $metaValues['woo_Subthema'] ?? '',
                'woo_Aanvullend_thema' => $metaValues['woo_Aanvullend_thema'] ?? ''
            ]
        ];

        $toUnset = [
            'woo_Hoofdthema',
            'woo_Subthema',
            'woo_Aanvullend_thema'
        ];

        return $this->unsetArrayItems($toUnset, $metaValues);
    }

    protected function convertToAttachmentGroup(array $metaValues): array
    {
        $metaValues['woo_Bijlagen'] = [];

        /**
         * Refactoren.
         * woo_Tijdstip_laatste_wijziging_bijlage omzetten
         */
        if (! empty($metaValues['woo_Upload_bijlage'])) {
            $attachmentsArray = json_decode($metaValues['woo_Upload_bijlage']);

            foreach ($attachmentsArray as $attachmentURL) {
                $attachmentID = $this->gravityFormsUploadToMediaLibrary($attachmentURL);
                $attachmentURL = \wp_get_attachment_url($attachmentID);

                if (! $attachmentID || ! $attachmentURL) {
                    continue;
                }

                $holder = [
                    'woo_Titel_Bijlage' => basename($attachmentURL),
                    'woo_Bijlage' => $attachmentURL
                ];

                $metaValues['woo_Bijlagen'][] = $holder;
            }
        } elseif (! empty($metaValues['woo_URL_bijlage'])) {
            $metaValues['woo_Bijlagen'] = [
                [
                    'woo_Titel_Bijlage' => basename($metaValues['woo_URL_bijlage']),
                    'woo_URL_Bijlage' => $metaValues['woo_URL_bijlage'],
                ]
            ];
        }

        $toUnset = [
            'woo_Titel_bijlage',
            'woo_URL_bijlage',
            'woo_Upload_bijlage',
            'woo_Bijlage'
        ];

        return $this->unsetArrayItems($toUnset, $metaValues);
    }

    protected function convertInformationGroup(array $metaValues): array
    {
        try {
            $date = new \DateTime($metaValues['woo_Tijdstip_laatste_wijziging'] ?? 'now');
        } catch(\Exception $e) {
            $date = new \DateTime('now');
        }

        $metaValues['woo_Wooverzoek_informatie'] = [
            'woo_Status' => $metaValues['woo_Status'] ?? '',
            'woo_Tijdstip_laatste_wijziging' => [
                'timestamp' => $date->getTimestamp(),
                'formatted' => $date->format('d-m-Y H:i:s')
            ]
        ];

        $toUnset = [
            'woo_Status',
            'woo_Tijdstip_laatste_wijziging'
        ];

        return $this->unsetArrayItems($toUnset, $metaValues);
    }

    protected function convertGeographicPosition(array $metaValues): array
    {
        $metaValues['woo_Geografische_positie'] = [
            [
                'woo_Longitude' => $metaValues['woo_Longitude'],
                'woo_Lattitude' => $metaValues['woo_Lattitude'],
            ]
        ];

        $toUnset = [
            'woo_Longitude',
            'woo_Lattitude',
        ];

        return $this->unsetArrayItems($toUnset, $metaValues);
    }

    protected function convertCoords(array $metaValues): array
    {
        $metaValues['woo_COORDS'] = [
            [
                'woo_X' => $metaValues['woo_X'],
                'woo_Y' => $metaValues['woo_Y'],
            ]
        ];

        $toUnset = [
            'woo_X',
            'woo_Y',
        ];

        return $this->unsetArrayItems($toUnset, $metaValues);
    }

    /**
     * Some form values are converted to a group.
     * After conversion this method can be used for unsetting the old values.
     */
    protected function unsetArrayItems(array $toUnset, array $array): array
    {
        foreach ($toUnset as $unset) {
            unset($array[$unset]);
        }

        return $array;
    }

    protected function dd($data): void
    {
        echo "<pre>";
        print_r($data);
        exit;
    }

    private function getKey(string $key): string
    {
        $key = preg_replace('/[^a-zA-Z0-9_ ]/', '', $key);
        $key = str_replace(' ', '_', $key);
        $key = ucfirst($key);

        return 'woo_' . $key;
    }

    private function getMetaField(string $key, string $value): string
    {
        if ($this->isSpecialField($key)) {
            return $this->specialField($key, $value);
        }

        return $value;
    }

    private function isSpecialField(string $key): bool
    {
        return class_exists($this->getSpecialFieldClassName($key));
    }

    private function specialField(string $key, string $value): string
    {
        $className = $this->getSpecialFieldClassName($key);

        return $className::make($key, $value)->get();
    }

    private function getSpecialFieldClassName(string $key): string
    {
        $key = str_replace('woo_', '', $key);
        $key = str_replace('_', '', $key);

        return sprintf('OpenWOO\\Fields\\%sField', $key);
    }
}
