<?php
namespace Apigee\Mint;

use Apigee\Exceptions\NotImplementedException;
use Apigee\Exceptions\ResponseException as ResponseException;
use Apigee\Mint\Exceptions\MintApiException as MintApiException;
use Apigee\Exceptions\ParameterException as ParameterException;
use Apigee\Util\CacheFactory as CacheFactory;

class DeveloperRatePlan extends Base\BaseObject
{

    private $dev;

    /**
     * @var string
     * Format YYYY-MM-DD
     */
    private $startDate;

    /**
     * @var string
     * Format YYYY-MM-DD
     */
    private $endDate;

    /**
     * @var string
     */
    private $id;

    private $nextRecurringFeeDate;

    /**
     * @var \Apigee\Mint\RatePlan
     */
    private $ratePlan;

    /**
     * @var string
     * Format YYYY-MM-DD
     */
    private $renewalDate;


    public function __construct($dev, \Apigee\Util\OrgConfig $config)
    {

        $base_url = '/mint/organizations/' . rawurlencode($config->orgName) . '/developers/' . rawurlencode($dev) . '/developer-accepted-rateplans';
        $this->init($config, $base_url);
        $this->dev = $dev;

        $this->idField = 'id';
        $this->idIsAutogenerated = true;
        $this->wrapperTag = 'developerRatePlan';

        $this->initValues();
    }

    public function getList($page_num = null, $page_size = 20)
    {
        $cache_manager = CacheFactory::getCacheManager(null);
        $data = $cache_manager->get('developer_accepted_rateplan:' . $this->dev, null);
        if (!isset($data)) {
            $this->get();
            $data = $this->responseObj;
            $cache_manager->set('developer_accepted_rateplan:' . $this->dev, $data);
        }
        $return_objects = array();
        foreach ($data[$this->wrapperTag] as $response_data) {
            $obj = $this->instantiateNew();
            $obj->loadFromRawData($response_data);
            $return_objects[] = $obj;
        }
        return $return_objects;
    }

    /**
     * Implements Base\BaseObject::init_values().
     *
     * @return void
     */
    protected function initValues()
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->id = null;
        $this->ratePlan = null;
    }

    /**
     * Implements Base\BaseObject::instantiate_new().
     *
     * @return DeveloperRatePlan
     */
    public function instantiateNew()
    {
        return new DeveloperRatePlan($this->dev, $this->config);
    }

    /**
     * Implements Base\BaseObject::load_from_raw_data().
     *
     * @param array $data
     * @param bool $reset
     */
    public function loadFromRawData($data, $reset = false)
    {
        if ($reset) {
            $this->initValues();
        }
        $excluded_properties = array('ratePlan', 'developer');
        foreach (array_keys($data) as $property) {
            if (in_array($property, $excluded_properties)) {
                continue;
            }

            // form the setter method name to invoke setXxxx
            $setter_method = 'set' . ucfirst($property);

            if (method_exists($this, $setter_method)) {
                $this->$setter_method($data[$property]);
            } else {
                self::$logger->notice('No setter method was found for property "' . $property . '"');
            }
        }

        if (isset($data['ratePlan']) && is_array($data['ratePlan']) && count($data['ratePlan']) > 0) {
            if (isset($data['ratePlan']['monetizationPackage']['id'])) {
                $m_package_id = $data['ratePlan']['monetizationPackage']['id'];
                $this->ratePlan = new RatePlan($m_package_id, $this->config);
                $this->ratePlan->loadFromRawData($data['ratePlan']);
            }
        }
    }

    public function force_save()
    {
        $url = '/mint/organizations/' . rawurlencode($this->config->orgName) . '/developers/' . rawurlencode($this->dev) . '/developer-rateplans';
        try {
            $obj = array(
                'developer' => array('id' => $this->dev),
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'ratePlan' => array('id' => $this->ratePlan->getId()),
                'suppressWarning' => true
            );
            $this->setBaseUrl($url);
            $this->post(null, $obj);
            $this->restoreBaseUrl();
        } catch (ResponseException $re) {
            if (MintApiException::isMintExceptionCode($re)) {
                throw new MintApiException($re);
            }
            throw $re;
        }
    }

    public function save($save_method = 'update')
    {
        $url = '/mint/organizations/' . rawurlencode($this->config->orgName) . '/developers/' . rawurlencode($this->dev) . '/developer-rateplans';
        $obj = array(
            'developer' => array('id' => $this->dev),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'ratePlan' => array('id' => $this->ratePlan->getId()),
        );
        try {
            $this->setBaseUrl($url);
            if ($save_method == 'create') {
                $this->post(null, $obj);
            } elseif ($save_method == 'update') {
                $obj['id'] = $this->id;
                $this->put($this->getId(), $obj);
            } else {
                throw new ParameterException('Unsupported save method argument: ' . $save_method);
            }
            $this->restoreBaseUrl();
        } catch (ResponseException $re) {
            if (MintApiException::isMintExceptionCode($re)) {
                throw new MintApiException($re);
            }
            throw $re;
        }
    }

    public function delete()
    {
        $this->setBaseUrl('/mint/organizations/' . rawurlencode($this->config->orgName) . '/developers/' . rawurlencode($this->dev) . '/developer-rateplans/' . rawurlencode($this->id));
        $this->http_delete(null);
        $this->restoreBaseUrl();
    }

    /**
     * Implements Base\BaseObject::__toString().
     *
     * @return string
     */
    public function __toString()
    {
        $obj = array(
            'developer' => array('id' => $this->dev),
            'endDate' => $this->endDate,
            'startDate' => $this->startDate,
            'id' => $this->id,
            'ratePlan' => null
        );
        if (isset($this->ratePlan)) {
            $obj['ratePlan'] = array('id' => $this->ratePlan->getId());
        }

        return json_encode($obj);
    }

    /* Accessors */

    public function getDeveloperId()
    {
        return $this->dev;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRatePlan()
    {
        return $this->ratePlan;
    }

    public function getRenewalDate()
    {
        return $this->renewalDate;
    }

    public function getNextRecurringFeeDate()
    {
        return $this->nextRecurringFeeDate;
    }


    /* Setters */

    public function setStartDate($start_date)
    {
        $this->startDate = $start_date;
    }

    public function setEndDate($end_date)
    {
        $this->endDate = $end_date;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setRatePlan($rate_plan)
    {
        $this->ratePlan = $rate_plan;
    }

    public function setRenewalDate($renewal_date)
    {
        $this->renewalDate = $renewal_date;
    }

    public function setNextRecurringFeeDate($next_recurring_fee_date)
    {
        $this->nextRecurringFeeDate = $next_recurring_fee_date;
    }

    public function setDeveloperId($dev)
    {
        $this->dev = $dev;
    }
}
