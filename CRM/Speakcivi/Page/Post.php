<?php

require_once 'CRM/Core/Page.php';

class CRM_Speakcivi_Page_Post extends CRM_Core_Page {

  public $contact_id = 0;

  public $activity_id = 0;

  public $campaign_id = 0;

  /**
   * Set values from request.
   *
   * @throws Exception
   */
  public function setValues() {
    $this->contact_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, true);
    CRM_Core_Error::debug_var('$contact_id', $this->contact_id, false, true);
    $this->activity_id = CRM_Utils_Request::retrieve('aid', 'Positive', $this, false);
    CRM_Core_Error::debug_var('$activity_id', $this->activity_id, false, true);
    $this->campaign_id = CRM_Utils_Request::retrieve('cid', 'Positive', $this, false);
    CRM_Core_Error::debug_var('$campaign_id', $this->campaign_id, false, true);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, true);
    $hash1 = sha1(CIVICRM_SITE_KEY . $this->contact_id);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }
  }


  /**
   * Get country prefix based on campaign id.
   *
   * @param int $campaign_id
   *
   * @return string
   */
  public function getCountry($campaign_id) {
    $country = '';
    if ($campaign_id > 0) {
      $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
      $speakcivi->setDefaults();
      $speakcivi->customFields = $speakcivi->getCustomFields($campaign_id);
      $language = $speakcivi->getLanguage();
      if ($language != '') {
        $tab = explode('_', $language);
        if (strlen($tab[0]) == 2) {
          $country = '/'.$tab[0];
        }
      }
    }
    return $country;
  }


  /**
   * Set new activity status for Scheduled activity.
   *
   * @param int $activity_id
   * @param string $status
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setActivityStatus($activity_id, $status = 'optout') {
    if ($activity_id > 0) {
      $scheduled_id = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name', 'String', 'value');
      $params = array(
        'sequential' => 1,
        'id' => $activity_id,
        'status_id' => $scheduled_id,
      );
      CRM_Core_Error::debug_var('$paramsActivityGet', $params, false, true);
      $result = civicrm_api3('Activity', 'get', $params);
      CRM_Core_Error::debug_var('$resultActivityGet', $result, false, true);
      if ($result['count'] == 1) {
        $new_status_id = CRM_Core_OptionGroup::getValue('activity_status', $status, 'name', 'String', 'value');
        $params['status_id'] = $new_status_id;
        CRM_Core_Error::debug_var('$paramsActivity-create', $params, false, true);
        $result = civicrm_api3('Activity', 'create', $params);
        CRM_Core_Error::debug_var('$resultActivity-create', $result, false, true);
      }
    }
  }


  /**
   * Set Added status for group. If group is not assigned to contact, It is added.
   *
   * @param int $contact_id
   * @param int $group_id
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setGroupStatus($contact_id, $group_id) {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contact_id,
      'group_id' => $group_id,
      'status' => "Pending"
    ));
    CRM_Core_Error::debug_var('$resultGroupContact-get', $result, false, true);

    if ($result['count'] == 1) {
      $params = array(
        'id' => $result["id"],
        'status' => "Added",
      );
    } else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $contact_id,
        'group_id' => $group_id,
        'status' => "Added",
      );
    }
    CRM_Core_Error::debug_var('$paramsGroupContact-create', $params, false, true);
    $result = civicrm_api3('GroupContact', 'create', $params);
    CRM_Core_Error::debug_var('$resultGroupContact-create', $result, false, true);
  }
}
