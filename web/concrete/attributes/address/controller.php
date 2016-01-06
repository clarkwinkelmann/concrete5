<?php

namespace Concrete\Attribute\Address;

use Concrete\Core\Attribute\Controller as AttributeTypeController;
use Concrete\Core\Entity\Attribute\Key\AddressKey;
use Concrete\Core\Entity\Attribute\Key\Type\AddressType;
use Concrete\Core\Entity\Attribute\Value\Value\AddressValue;
use Core;
use Database;

class Controller extends AttributeTypeController
{
    public $helpers = array('form');

    public function searchKeywords($keywords, $queryBuilder)
    {
        $h = $this->attributeKey->getAttributeKeyHandle();

        return $queryBuilder->expr()->orX(
            $queryBuilder->expr()->like("ak_{$h}_address1", ':keywords'),
            $queryBuilder->expr()->like("ak_{$h}_address2", ':keywords'),
            $queryBuilder->expr()->like("ak_{$h}_city", ':keywords'),
            $queryBuilder->expr()->like("ak_{$h}_state_province", ':keywords'),
            $queryBuilder->expr()->like("ak_{$h}_postal_code", ':keywords'),
            $queryBuilder->expr()->like("ak_{$h}_country", ':keywords')
        );
    }

    public function searchForm($list)
    {
        $address1 = $this->request('address1');
        $address2 = $this->request('address2');
        $city = $this->request('city');
        $state_province = $this->request('state_province');
        $postal_code = $this->request('postal_code');
        $country = $this->request('country');
        $akHandle = $this->attributeKey->getAttributeKeyHandle();

        if ($address1) {
            $list->filter('ak_' . $akHandle . '_address1', '%' . $address1 . '%', 'like');
        }
        if ($address2) {
            $list->filter('ak_' . $akHandle . '_address2', '%' . $address2 . '%', 'like');
        }
        if ($city) {
            $list->filter('ak_' . $akHandle . '_city', '%' . $city . '%', 'like');
        }
        if ($state_province) {
            $list->filter('ak_' . $akHandle . '_state_province', $state_province);
        }
        if ($postal_code) {
            $list->filter('ak_' . $akHandle . '_postal_code', '%' . $postal_code . '%', 'like');
        }
        if ($country) {
            $list->filter('ak_' . $akHandle . '_country', $country);
        }

        return $list;
    }

    protected $searchIndexFieldDefinition = array(
        'address1' => array(
            'type' => 'string',
            'options' => array('length' => '255', 'default' => '', 'notnull' => false),
        ),
        'address2' => array(
            'type' => 'string',
            'options' => array('length' => '255', 'default' => '', 'notnull' => false),
        ),
        'city' => array('type' => 'string', 'options' => array('length' => '255', 'default' => '', 'notnull' => false)),
        'state_province' => array(
            'type' => 'string',
            'options' => array('length' => '255', 'default' => '', 'notnull' => false),
        ),
        'country' => array(
            'type' => 'string',
            'options' => array('length' => '255', 'default' => '', 'notnull' => false),
        ),
        'postal_code' => array(
            'type' => 'string',
            'options' => array('length' => '255', 'default' => '', 'notnull' => false),
        ),
    );

    public function search()
    {
        $this->load();
        print $this->form();
        $v = $this->getView();
        $this->set('search', true);
        $v->render('form');
    }

    public function saveForm($data)
    {
        $this->saveValue($data);
    }

    public function validateForm($data)
    {
        return ($data['address1'] != '' && $data['city'] != '' && $data['state_province'] != '' && $data['country'] != '' && $data['postal_code'] != '');
    }

    public function validateValue()
    {
        $v = $this->getValue();
        if (!is_object($v)) {
            return false;
        }
        if (trim((string) $v) == '') {
            return false;
        }

        return true;
    }

    public function getSearchIndexValue()
    {
        $v = $this->attributeValue;
        $args = array();
        $args['address1'] = $v->getAddress1();
        $args['address2'] = $v->getAddress2();
        $args['city'] = $v->getCity();
        $args['state_province'] = $v->getStateProvince();
        $args['country'] = $v->getCountry();
        $args['postal_code'] = $v->getPostalCode();

        return $args;
    }

    public function deleteKey()
    {
        $db = Database::connection();
        $arr = $this->attributeKey->getAttributeValueIDList();
        foreach ($arr as $id) {
            $db->Execute('delete from atAddress where avID = ?', array($id));
        }
    }

    public function deleteValue()
    {
        $db = Database::connection();
        $db->Execute('delete from atAddress where avID = ?', array($this->getAttributeValueID()));
    }

    public function getDisplayValue()
    {
        $v = Core::make('helper/text')->entities($this->getValue());
        $ret = nl2br($v);

        return $ret;
    }

    public function action_load_provinces_js()
    {
        $h = Core::make('helper/lists/states_provinces');
        print "var ccm_attributeTypeAddressStatesTextList = '\\\n";
        $all = $h->getAll();
        foreach ($all as $country => $countries) {
            foreach ($countries as $value => $text) {
                print addslashes($country) . ':' . addslashes($value) . ':' . addslashes($text) . "|\\\n";
            }
        }
        print "'";
    }

    public function validateKey($data = false)
    {
        $e = parent::validateKey($data);

        // additional validation for select type
        $akCustomCountries = $data['akCustomCountries'];
        $akHasCustomCountries = $data['akHasCustomCountries'];
        if ($data['akHasCustomCountries'] != 1) {
            $akHasCustomCountries = 0;
        }

        if (!is_array($data['akCustomCountries'])) {
            $akCustomCountries = array();
        }

        if ($akHasCustomCountries && (count($akCustomCountries) == 0)) {
            $e->add(t('You must specify at least one country.'));
        } else {
            if ($akHasCustomCountries && $data['akDefaultCountry'] != '' && (!in_array(
                    $data['akDefaultCountry'],
                    $akCustomCountries
                ))
            ) {
                $e->add(t('The default country must be in the list of custom countries.'));
            }
        }

        return $e;
    }

    public function duplicateKey($newAK)
    {
        $this->load();
        $db = Database::connection();
        $db->Execute(
            'insert into atAddressSettings (akID, akHasCustomCountries, akDefaultCountry) values (?, ?, ?)',
            array($newAK->getAttributeKeyID(), $this->akHasCustomCountries, $this->akDefaultCountry)
        );
        if ($this->akHasCustomCountries) {
            foreach ($this->akCustomCountries as $country) {
                $db->Execute(
                    'insert into atAddressCustomCountries (akID, country) values (?, ?)',
                    array($newAK->getAttributeKeyID(), $country)
                );
            }
        }
    }

    public function exportKey($akey)
    {
        $this->load();
        $type = $akey->addChild('type');
        $type->addAttribute('custom-countries', $this->akHasCustomCountries);
        $type->addAttribute('default-country', $this->akDefaultCountry);
        if ($this->akHasCustomCountries) {
            $countries = $type->addChild('countries');
            foreach ($this->akCustomCountries as $country) {
                $countries->addChild('country', $country);
            }
        }

        return $akey;
    }

    public function exportValue(\SimpleXMLElement $akn)
    {
        $avn = $akn->addChild('value');
        $address = $this->getValue();
        $avn->addAttribute('address1', $address->getAddress1());
        $avn->addAttribute('address2', $address->getAddress2());
        $avn->addAttribute('city', $address->getCity());
        $avn->addAttribute('state-province', $address->getStateProvince());
        $avn->addAttribute('country', $address->getCountry());
        $avn->addAttribute('postal-code', $address->getPostalCode());
    }

    public function saveValue($data)
    {
        if ($data instanceof AddressValue) {
            return $data;
        }
        extract($data);
        $av = new AddressValue();
        $av->setAddress1($address1);
        $av->setAddress2($address2);
        $av->setCity($city);
        $av->setStateProvince($state_province);
        $av->setCountry($country);
        $av->setPostalCode($postal_code);
        return $av;
    }



    public function importValue(\SimpleXMLElement $akv)
    {
        if (isset($akv->value)) {
            $av = new AddressValue();
            $av->setAddress1((string) $akv->value['address1']);
            $av->setAddress2((string) $akv->value['address2']);
            $av->setCity((string) $akv->value['city']);
            $av->setStateProvince((string) $akv->value['state-province']);
            $av->setCountry((string) $akv->value['country']);
            $av->setPostalCode((string) $akv->value['postal-code']);
            return $av;
        }
    }

    public function importKey($akey)
    {
        $type = new AddressType();
        if (isset($akey->type)) {
            $type->setHasCustomCountries((bool) $akey->type['custom-countries']);
            $type->setDefaultCountry((string) $akey->type['default-country']);
            if (isset($akey->type->countries)) {
                $countries = array();
                foreach ($akey->type->countries->children() as $country) {
                    $countries[] = (string) $country;
                }
                $type->setCustomCountries($countries);
            }
        }
        return $type;
    }

    public function saveKey($data)
    {
        $type = new AddressType();

        $akCustomCountries = $data['akCustomCountries'];
        $akHasCustomCountries = $data['akHasCustomCountries'];
        if ($data['akHasCustomCountries'] != 1) {
            $akHasCustomCountries = 0;
        }
        if (!is_array($data['akCustomCountries'])) {
            $akCustomCountries = array();
        }

        $type->setCustomCountries($akCustomCountries);
        $type->setHasCustomCountries($akHasCustomCountries);
        $type->setDefaultCountry($data['akDefaultCountry']);
        return $type;
    }

    protected function load()
    {
        $ak = $this->getAttributeKey();
        if (!is_object($ak)) {
            return false;
        }

        $type = $ak->getAttributeKeyType();
        /**
         * @var $type AddressType
         */
        $this->akHasCustomCountries = $type->hasCustomCountries();
        $this->akDefaultCountry = $type->getDefaultCountry();
        $this->akCustomCountries = $type->getCustomCountries();
        $this->set('akDefaultCountry', $this->akDefaultCountry);
        $this->set('akHasCustomCountries', $this->akHasCustomCountries);
        $this->set('akCustomCountries', $this->akCustomCountries);
    }

    public function type_form()
    {
        $this->load();
    }

    public function form()
    {
        $this->load();
        if (is_object($this->attributeValue)) {
            $value = $this->getAttributeValue()->getValue();
            $this->set('address1', $value->getAddress1());
            $this->set('address2', $value->getAddress2());
            $this->set('city', $value->getCity());
            $this->set('state_province', $value->getStateProvince());
            $this->set('country', $value->getCountry());
            $this->set('postal_code', $value->getPostalCode());
        }
        $this->addFooterItem(Core::make('helper/html')->javascript($this->getView()->action('load_provinces_js')));
        $this->addFooterItem(
            Core::make('helper/html')->javascript($this->getAttributeTypeFileURL('country_state.js'))
        );
        $this->set('key', $this->attributeKey);
    }

    public function createAttributeKeyType()
    {
        return new AddressType();
    }
}
