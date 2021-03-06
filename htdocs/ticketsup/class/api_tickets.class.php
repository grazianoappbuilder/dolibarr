<?php
/* Copyright (C) 2016   Jean-François Ferry     <hello@librethic.io>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

 use Luracast\Restler\RestException;

require 'ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticketsup.lib.php';


/**
 * API class for ticketsup object
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Tickets extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    public static $FIELDS = array(
        'subject',
        'message'
    );

    /**
     * @var array   $FIELDS_MESSAGES     Mandatory fields, checked when create and update object
     */
    public static $FIELDS_MESSAGES = array(
        'track_id',
        'message'
    );

    /**
     * @var Ticketsup $ticketsup {@type Ticketsup}
     */
    public $ticketsup;

    /**
     * Constructor
     */
    public function __construct()
    {
    	global $db;
    	$this->db = $db;
        $this->ticketsup = new Ticketsup($this->db);
    }

    /**
     * Get properties of a Ticket object.
     *
     * Return an array with ticket informations
     *
     * @param	int 			$id 		ID of ticketsup
     * @return 	array|mixed 				Data without useless information
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    function get($id)
    {
    	return $this->getCommon($id, '', '');
    }

    /**
     * Get properties of a Ticket object from track id
     *
     * Return an array with ticket informations
     *
     * @param	string  		$track_id 	Tracking ID of ticket
     * @return 	array|mixed 				Data without useless information
     *
     * @url GET track_id/{track_id}
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    public function getByTrackId($track_id)
    {
		return $this->getCommon(0, $track_id, '');
    }

    /**
     * Get properties of a Ticket object from ref
     *
     * Return an array with ticket informations
     *
     * @param	string  		$ref    	Reference for ticket
     * @return 	array|mixed 				Data without useless information
     *
     * @url GET ref/{ref}
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    public function getByRef($ref)
    {
    	try {
    		return $this->getCommon(0, '', $ref);
    	}
    	catch(Exception $e)
    	{
   			throw $e;
    	}
    }

    /**
     * Get properties of a Ticket object
     *
     * Return an array with ticket informations
     *
     * @param	int 			$id 		ID of ticketsup
     * @param	string  		$track_id 	Tracking ID of ticket
     * @param	string  		$ref    	Reference for ticket
     * @return 	array|mixed 				Data without useless information
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    public function getCommon($id = 0, $track_id = '', $ref = '')
    {
        if (! DolibarrApiAccess::$user->rights->ticketsup->read) {
            throw new RestException(403);
        }

        // Check parameters
        if (!$id && !$track_id && !$ref) {
            throw new RestException(401, 'Wrong parameters');
        }

        $result = $this->ticketsup->fetch($id, $ref, $track_id);
        if (! $result) {
            throw new RestException(404, 'Ticketsup not found');
        }

        // String for user assigned
        if ($this->ticketsup->fk_user_assign > 0) {
          $userStatic = new User($this->db);
          $userStatic->fetch($this->ticketsup->fk_user_assign);
          $this->ticketsup->fk_user_assign_string = $userStatic->firstname.' '.$userStatic->lastname;
        }

        // Messages of ticket
        $messages = array();
        $this->ticketsup->loadCacheMsgsTicket();
        if (is_array($this->ticketsup->cache_msgs_ticket) && count($this->ticketsup->cache_msgs_ticket) > 0) {
            $num = count($this->ticketsup->cache_msgs_ticket);
            $i = 0;
            while ($i < $num) {
                if ($this->ticketsup->cache_msgs_ticket[$i]['fk_user_action'] > 0) {
                    $user_action = new User($this->db);
                    $user_action->fetch($this->ticketsup->cache_msgs_ticket[$i]['fk_user_action']);
                }

                // Now define messages
                $messages[] = array(
                'id' => $this->ticketsup->cache_msgs_ticket[$i]['id'],
                'fk_user_action' => $this->ticketsup->cache_msgs_ticket[$i]['fk_user_action'],
                'fk_user_action_socid' =>  $user_action->socid,
                'fk_user_action_string' => dolGetFirstLastname($user_action->firstname, $user_action->lastname),
                'message' => $this->ticketsup->cache_msgs_ticket[$i]['message'],
                'datec' => $this->ticketsup->cache_msgs_ticket[$i]['datec'],
                'private' => $this->ticketsup->cache_msgs_ticket[$i]['private']
                );
                $i++;
            }
            $this->ticketsup->messages = $messages;
        }

        // History
        $history = array();
        $this->ticketsup->loadCacheLogsTicket();
        if (is_array($this->ticketsup->cache_logs_ticket) && count($this->ticketsup->cache_logs_ticket) > 0) {
            $num = count($this->ticketsup->cache_logs_ticket);
            $i = 0;
            while ($i < $num) {
                if ($this->ticketsup->cache_logs_ticket[$i]['fk_user_create'] > 0) {
                    $user_action = new User($this->db);
                    $user_action->fetch($this->ticketsup->cache_logs_ticket[$i]['fk_user_create']);
                }

                // Now define messages
                $history[] = array(
                'id' => $this->ticketsup->cache_logs_ticket[$i]['id'],
                'fk_user_action' => $this->ticketsup->cache_logs_ticket[$i]['fk_user_create'],
                'fk_user_action_string' => dolGetFirstLastname($user_action->firstname, $user_action->lastname),
                'message' => $this->ticketsup->cache_logs_ticket[$i]['message'],
                'datec' => $this->ticketsup->cache_logs_ticket[$i]['datec'],
                );
                $i++;
            }
            $this->ticketsup->history = $history;
        }


        if (! DolibarrApi::_checkAccessToResource('ticketsup', $this->ticketsup->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        return $this->_cleanObjectDatas($this->ticketsup);
    }

    /**
     * List ticketsups
     *
     * Get a list of ticketsups
     *
     * @param int       $socid      Filter list with thirdparty ID
     * @param string	$mode		Use this param to filter list
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string	$sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     *
     * @return array Array of ticketsup objects
     *
     */
    public function index($socid = 0, $mode = "", $sortfield = "s.rowid", $sortorder = "ASC", $limit = 0, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if (!$socid && DolibarrApiAccess::$user->societe_id) {
            $socid = DolibarrApiAccess::$user->societe_id;
        }

        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
            $search_sale = DolibarrApiAccess::$user->id;
        }

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
            $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."ticketsup as s";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
            $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        }

        $sql.= ' WHERE s.entity IN ('.getEntity('ticketsup', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
            $sql.= " AND s.fk_soc = sc.fk_soc";
        }
        if ($socid > 0) {
            $sql.= " AND s.fk_soc = ".$socid;
        }
        if ($search_sale > 0) {
            $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        }

        // Example of use $mode
        if ($mode == 'new') {
            $sql.= " AND s.fk_statut IN (0)";
        }
        if ($mode == 'read') {
            $sql.= " AND s.fk_statut IN (1)";
        }
        if ($mode == 'answered') {
            $sql.= " AND s.fk_statut IN (3)";
        }
        if ($mode == 'assign') {
            $sql.= " AND s.fk_statut IN (4)";
        }
        if ($mode == 'inprogress') {
            $sql.= " AND s.fk_statut IN (5)";
        }
        if ($mode == 'waiting') {
            $sql.= " AND s.fk_statut IN (6)";
        }
        if ($mode == 'closed') {
            $sql.= " AND s.fk_statut IN (8)";
        }
        if ($mode == 'deleted') {
            $sql.= " AND s.fk_statut IN (9)";
        }

        // Insert sale filter
        if ($search_sale > 0) {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        // Add sql filters
        if ($sqlfilters) {
        	if (! DolibarrApi::_checkFilters($sqlfilters)) {
        		throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
        	}
        	$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
        	$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);

        if ($limit) {
        	if ($page < 0) {
        		$page = 0;
        	}
        	$offset = $limit * $page;

        	$sql .= $this->db->plimit($limit, $offset);
        }

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $ticketsup_static = new Ticketsup($db);
                if ($ticketsup_static->fetch($obj->rowid)) {
                    if ($ticketsup_static->fk_user_assign > 0) {
                      $userStatic = new User($this->db);
                      $userStatic->fetch($ticketsup_static->fk_user_assign);
                      $ticketsup_static->fk_user_assign_string = $userStatic->firstname.' '.$userStatic->lastname;
                    }
                    $obj_ret[] = $this->_cleanObjectDatas($ticketsup_static);
                }
                $i++;
            }
        } else {
            throw new RestException(503, 'Error when retrieve ticketsup list');
        }
        if (! count($obj_ret)) {
            throw new RestException(404, 'No ticketsup found');
        }
		    return $obj_ret;
    }

    /**
     * Create ticketsup object
     *
     * @param array $request_data   Request datas
     * @return int  ID of ticketsup
     *
     */
    public function post($request_data = null)
    {
        $ticketstatic = new Ticketsup($this->db);
        if (! DolibarrApiAccess::$user->rights->ticketsup->write) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach ($request_data as $field => $value) {
            $this->ticketsup->$field = $value;
        }
        if (empty($this->ticketsup->ref)) {
            $this->ticketsup->ref = $ticketstatic->getDefaultRef();
        }
        if (empty($this->ticketsup->track_id)) {
            $this->ticketsup->track_id = generate_random_id(16);
        }
        if (! $this->ticketsup->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->ticketsup->id;
    }

    /**
     * Create ticketsup object
     *
     * @param array $request_data   Request datas
     * @return int  ID of ticketsup
     *
     */
    public function postNewMessage($request_data = null)
    {
        $ticketstatic = new Ticketsup($this->db);
        if (! DolibarrApiAccess::$user->rights->ticketsup->write) {
      throw new RestException(401);
    }
        // Check mandatory fields
        $result = $this->_validateMessage($request_data);

        foreach ($request_data as $field => $value) {
            $this->ticketsup->$field = $value;
        }
        $ticketMessageText = $this->ticketsup->message;
        $result = $this->ticketsup->fetch('', '', $this->ticketsup->track_id);
        if (! $result) {
            throw new RestException(404, 'Ticketsup not found');
        }
        $this->ticketsup->message = $ticketMessageText;
        if (! $this->ticketsup->createTicketMessage(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->ticketsup->id;
    }

    /**
     * Update ticketsup
     *
     * @param int   $id             Id of ticketsup to update
     * @param array $request_data   Datas
     * @return int
     *
     */
    public function put($id, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->ticketsup->write) {
			throw new RestException(401);
		}

        $result = $this->ticketsup->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Ticketsup not found');
        }

		if (! DolibarrApi::_checkAccessToResource('ticketsup', $this->ticketsup->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach ($request_data as $field => $value) {
            $this->ticketsup->$field = $value;
        }

        if ($this->ticketsup->update($id, DolibarrApiAccess::$user)) {
            return $this->get($id);
        }

        return false;
    }

    /**
     * Delete ticketsup
     *
     * @param   int     $id   Ticketsup ID
     * @return  array
     *
     */
    public function delete($id)
    {
        if (! DolibarrApiAccess::$user->rights->ticketsup->delete) {
			throw new RestException(401);
		}
        $result = $this->ticketsup->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Ticketsup not found');
        }

		if (! DolibarrApi::_checkAccessToResource('ticketsup', $this->ticketsup->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if (!$this->ticketsup->delete($id)) {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Ticketsup deleted'
            )
        );
    }


    /**
     * Get the list of tickets categories.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of events types
     *
     * @url     GET setup/dictionary/categories
     *
     * @throws RestException
     */
    function getTicketsCategories($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
    	$list = array();

    	$sql = "SELECT rowid, code, pos,  label, use_default, description";
    	$sql.= " FROM ".MAIN_DB_PREFIX."c_ticketsup_category as t";
    	$sql.= " WHERE t.active = 1";
    	// Add sql filters
    	if ($sqlfilters)
    	{
    		if (! DolibarrApi::_checkFilters($sqlfilters))
    		{
    			throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
    		}
    		$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
    		$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
    	}


    	$sql.= $this->db->order($sortfield, $sortorder);

    	if ($limit) {
    		if ($page < 0) {
    			$page = 0;
    		}
    		$offset = $limit * $page;

    		$sql .= $this->db->plimit($limit, $offset);
    	}

    	$result = $this->db->query($sql);

    	if ($result) {
    		$num = $this->db->num_rows($result);
    		$min = min($num, ($limit <= 0 ? $num : $limit));
    		for ($i = 0; $i < $min; $i++) {
    			$list[] = $this->db->fetch_object($result);
    		}
    	} else {
    		throw new RestException(503, 'Error when retrieving list of ticketsup categories : '.$this->db->lasterror());
    	}

    	return $list;
    }

    /**
     * Get the list of tickets severity.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of events types
     *
     * @url     GET setup/dictionary/severities
     *
     * @throws RestException
     */
    function getTicketsSeverities($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
    	$list = array();

    	$sql = "SELECT rowid, code, pos,  label, use_default, color, description";
    	$sql.= " FROM ".MAIN_DB_PREFIX."c_ticketsup_severity as t";
    	$sql.= " WHERE t.active = 1";
    	// Add sql filters
    	if ($sqlfilters)
    	{
    		if (! DolibarrApi::_checkFilters($sqlfilters))
    		{
    			throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
    		}
    		$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
    		$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
    	}


    	$sql.= $this->db->order($sortfield, $sortorder);

    	if ($limit) {
    		if ($page < 0) {
    			$page = 0;
    		}
    		$offset = $limit * $page;

    		$sql .= $this->db->plimit($limit, $offset);
    	}

    	$result = $this->db->query($sql);

    	if ($result) {
    		$num = $this->db->num_rows($result);
    		$min = min($num, ($limit <= 0 ? $num : $limit));
    		for ($i = 0; $i < $min; $i++) {
    			$list[] = $this->db->fetch_object($result);
    		}
    	} else {
    		throw new RestException(503, 'Error when retrieving list of ticketsup severities : '.$this->db->lasterror());
    	}

    	return $list;
    }

    /**
     * Get the list of tickets types.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of events types
     *
     * @url     GET setup/dictionary/types
     *
     * @throws RestException
     */
    function getTicketsTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
    	$list = array();

    	$sql = "SELECT rowid, code, pos,  label, use_default, description";
    	$sql.= " FROM ".MAIN_DB_PREFIX."c_ticketsup_type as t";
    	$sql.= " WHERE t.active = 1";
    	if ($type) $sql.=" AND t.type LIKE '%" . $this->db->escape($type) . "%'";
    	if ($module)    $sql.=" AND t.module LIKE '%" . $this->db->escape($module) . "%'";
    	// Add sql filters
    	if ($sqlfilters)
    	{
    		if (! DolibarrApi::_checkFilters($sqlfilters))
    		{
    			throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
    		}
    		$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
    		$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
    	}


    	$sql.= $this->db->order($sortfield, $sortorder);

    	if ($limit) {
    		if ($page < 0) {
    			$page = 0;
    		}
    		$offset = $limit * $page;

    		$sql .= $this->db->plimit($limit, $offset);
    	}

    	$result = $this->db->query($sql);

    	if ($result) {
    		$num = $this->db->num_rows($result);
    		$min = min($num, ($limit <= 0 ? $num : $limit));
    		for ($i = 0; $i < $min; $i++) {
    			$list[] = $this->db->fetch_object($result);
    		}
    	} else {
    		throw new RestException(503, 'Error when retrieving list of ticketsup types : '.$this->db->lasterror());
    	}

    	return $list;
    }





    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    private function _validate($data)
    {
        $ticketsup = array();
        foreach (Ticketsups::$FIELDS as $field) {
            if (!isset($data[$field])) {
                throw new RestException(400, "$field field missing");
            }
            $ticketsup[$field] = $data[$field];
        }
        return $ticketsup;
    }

    /**
     * Validate fields before create or update object message
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    private function _validateMessage($data)
    {
        $ticketsup = array();
        foreach (Ticketsups::$FIELDS_MESSAGES as $field) {
            if (!isset($data[$field])) {
                throw new RestException(400, "$field field missing");
            }
            $ticketsup[$field] = $data[$field];
        }
        return $ticketsup;
    }


    /**
     * Clean sensible object datas
     *
     * @param   object  $object	Object to clean
     * @return	array	Array of cleaned object properties
     *
     * @todo use an array for properties to clean
     *
     */
    function _cleanObjectDatas($object)
    {

    	$object = parent::_cleanObjectDatas($object);

    	// Other attributes to clean
        $attr2clean = array(
            "contact",
            "contact_id",
            "ref_previous",
            "ref_next",
            "ref_ext",
            "table_element_line",
            "statut",
            "country",
            "country_id",
            "country_code",
            "barcode_type",
            "barcode_type_code",
            "barcode_type_label",
            "barcode_type_coder",
            "mode_reglement_id",
            "cond_reglement_id",
            "cond_reglement",
            "fk_delivery_address",
            "shipping_method_id",
            "modelpdf",
            "fk_account",
            "note_public",
            "note_private",
            "note",
            "total_ht",
            "total_tva",
            "total_localtax1",
            "total_localtax2",
            "total_ttc",
            "fk_incoterms",
            "libelle_incoterms",
            "location_incoterms",
            "name",
            "lastname",
            "firstname",
            "civility_id",
            "cache_msgs_ticket",
            "cache_logs_ticket",
        	"statuts_short",
        	"statuts"
        );
        foreach ($attr2clean as $toclean) {
            unset($object->$toclean);
        }

        // If object has lines, remove $db property
        if (isset($object->lines) && count($object->lines) > 0) {
            $nboflines = count($object->lines);
            for ($i=0; $i < $nboflines; $i++) {
                $this->_cleanObjectDatas($object->lines[$i]);
            }
        }

        // If object has linked objects, remove $db property
        if (isset($object->linkedObjects) && count($object->linkedObjects) > 0) {
            foreach ($object->linkedObjects as $type_object => $linked_object) {
                foreach ($linked_object as $object2clean) {
                    $this->_cleanObjectDatas($object2clean);
                }
            }
        }
        return $object;
    }
}
