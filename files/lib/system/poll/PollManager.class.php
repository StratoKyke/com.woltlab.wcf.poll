<?php
namespace wcf\system\poll;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\poll\option\PollOptionList;
use wcf\data\poll\Poll;
use wcf\data\poll\PollList;
use wcf\data\poll\PollAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\SingletonFactory;
use wcf\system\WCF;
use wcf\util\ClassUtil;
use wcf\util\StringUtil;

/**
 * Provides methods to create and manage polls.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	system.poll
 * @category	Community Framework
 */
class PollManager extends SingletonFactory {
	/**
	 * list of object types
	 * @var	array<wcf\data\object\type\ObjectType>
	 */
	protected $cache = array();
	
	/**
	 * current object id
	 * @var	integer
	 */
	protected $objectID = 0;
	
	/**
	 * current object type
	 * @var	string
	 */
	protected $objectType = '';
	
	/**
	 * poll object
	 * @var	wcf\data\poll\Poll
	 */
	protected $poll = null;
	
	/**
	 * poll data
	 * @var	array<mixed>
	 */
	protected $pollData = array(
		'endTime' => 0,
		'isChangeable' => 0,
		'isPublic' => 0,
		'maxVotes' => 1,
		'question' => '',
		'resultsRequireVote' => 0,
		'sortByVotes' => 0
	);
	
	/**
	 * poll id
	 * @var	integer
	 */
	protected $pollID = 0;
	
	/**
	 * list of poll options
	 * @var	array<string>
	 */
	protected $pollOptions = array();
	
	/**
	 * @see	wcf\system\SingletonFactory::init()
	 */
	protected function init() {
		$objectTypes = ObjectTypeCache::getInstance()->getObjectTypes('com.woltlab.wcf.poll');
		foreach ($objectTypes as $objectType) {
			$this->cache[$objectType->objectType] = $objectType;
		}
	}
	
	/**
	 * Removes a list of polls by id.
	 * 
	 * @param	array<integer>		$pollIDs
	 */
	public function removePolls(array $pollIDs) {
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("pollID IN (?)", array($pollIDs));
		
		$sql = "DELETE FROM	wcf".WCF_N."_poll
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
	}
	
	/**
	 * Sets object data.
	 * 
	 * @param	string		$objectType
	 * @param	integer		$objectID
	 * @param	integer		$pollID
	 * @return	boolean
	 */
	public function setObject($objectType, $objectID, $pollID = 0) {
		if (!isset($this->cache[$objectType])) {
			throw new SystemException("Object type '".$objectType."' is unknown");
		}
		
		$this->objectID = intval($objectID);
		$this->objectType = $objectType;
		$this->pollID = $pollID;
		
		// load poll
		if ($this->pollID) {
			$this->poll = new Poll($this->pollID);
			if (!$this->poll->pollID) {
				$this->poll = null;
				$this->pollID = 0;
				
				return false;
			}
			
			// populate poll data
			$this->pollData = array(
				'endTime' => $this->poll->endTime,
				'isChangeable' => $this->poll->isChangeable,
				'isPublic' => $this->poll->isPublic,
				'maxVotes' => $this->poll->maxVotes,
				'question' => $this->poll->question,
				'resultsRequireVote' => $this->poll->resultsRequireVote,
				'sortByVotes' => $this->poll->sortByVotes
			);
			
			// load poll options
			$sql = "SELECT		optionID, optionValue
				FROM		wcf".WCF_N."_poll_option
				WHERE		pollID = ?
				ORDER BY	showOrder ASC";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array($this->poll->pollID));
			while ($row = $statement->fetchArray()) {
				$this->pollOptions[] = $row;
			}
		}
		
		return true;
	}
	
	/**
	 * Reads form parameters for polls.
	 */
	public function readFormParameters() {
		// reset poll data and options prior to reading form input
		$this->pollData = $this->pollOptions = array();
		
		// poll data
		if (isset($_POST['pollEndTime'])) $this->pollData['endTime'] = intval($_POST['pollEndTime']);
		if (isset($_POST['pollMaxVotes'])) $this->pollData['maxVotes'] = max(intval($_POST['pollMaxVotes']), 1); // force a minimum of 1
		if (isset($_POST['pollQuestion'])) $this->pollData['question'] = StringUtil::trim($_POST['pollQuestion']);
		
		// boolean values
		$this->pollData['isChangeable'] = (isset($_POST['pollIsChangeable'])) ? 1 : 0;
		$this->pollData['resultsRequireVote'] = (isset($_POST['pollResultsRequireVote'])) ? 1 : 0;
		$this->pollData['sortByVotes'] = (isset($_POST['pollSortByVotes'])) ? 1 : 0;
		
		if ($this->poll === null) {
			$this->pollData['isPublic'] = (isset($_POST['pollIsPublic']) && $this->canStartPublicPoll()) ? 1 : 0;
		}
		else {
			// visibility cannot be changed after creation
			$this->pollData['isPublic'] = $this->poll->isPublic;
		}
		
		//  poll options
		if (isset($_POST['pollOptions']) && is_array($_POST['pollOptions'])) {
			foreach ($_POST['pollOptions'] as $showOrder => $value) {
				list($optionID, $optionValue) = explode('_', $value, 2);
				$this->pollOptions[$showOrder] = array(
					'optionID' => intval($optionID),
					'optionValue' => StringUtil::trim($optionValue)
				);
			}
		}
	}
	
	/**
	 * Validates poll parameters.
	 */
	public function validate() {
		// if no question is given, ignore poll completely
		if (empty($this->pollData['question'])) {
			return;
		}
		
		// end time is in the past
		if ($this->pollData['endTime'] != 0 && $this->pollData['endTime'] <= TIME_NOW) {
			throw new UserInputException('pollEndTime', 'notValid');
		}
		
		// no options given
		$count = count($this->pollOptions);
		if (!$count) {
			throw new UserInputException('pollOptions');
		}
		
		// less options available than allowed
		if ($count < $this->pollData['maxVotes']) {
			throw new UserInputException('pollMaxVotes', 'notValid');
		}
	}
	
	/**
	 * Handles poll creation, modification and deletion. Returns poll id or zero
	 * if poll was deleted or nothing was created.
	 * 
	 * @param	integer		$objectID
	 * @return	integer
	 */
	public function save($objectID = null) {
		if ($objectID !== null) {
			$this->objectID = intval($objectID);
		}
		
		// create a new poll
		if ($this->poll === null) {
			// no poll should be created
			if (empty($this->pollData['question'])) {
				return 0;
			}
			
			// validate if object type is given
			if (empty($this->objectType)) {
				throw new SystemException("Could not create poll, missing object type");
			}
			
			$data = $this->pollData;
			$data['objectID'] = $this->objectID;
			$data['objectTypeID'] = $this->cache[$this->objectType]->objectTypeID;
			$data['time'] = TIME_NOW;
			
			$action = new PollAction(array(), 'create', array(
				'data' => $data,
				'options' => $this->pollOptions
			));
			$returnValues = $action->executeAction();
			$this->poll = $returnValues['returnValues'];
		}
		else {
			// remove poll
			if (empty($this->pollData['question'])) {
				$action = new PollAction(array($this->poll), 'delete');
				$returnValues = $action->executeAction();
				$this->poll = null;
				
				return 0;
			}
			else {
				// update existing poll
				$action = new PollAction(array($this->poll), 'update', array(
					'data' => $this->pollData,
					'options' => $this->pollOptions
				));
				$returnValues = $action->executeAction();
			}
		}
		
		return $this->poll->pollID;
	}
	
	/**
	 * Assigns variables for poll management or display.
	 * 
	 * @param	boolean		$management
	 */
	public function assignVariables($management = true) {
		// poll management
		if ($management) {
			$variables = array(
				'__showPoll' => true,
				'pollID' => ($this->poll === null ? 0 : $this->poll->pollID),
				'pollOptions' => $this->pollOptions
			);
			foreach ($this->pollData as $key => $value) {
				$key = 'poll'.ucfirst($key);
				$variables[$key] = $value;
			}
			
			WCF::getTPL()->assign($variables);
		}
		else {
			// poll display
			throw new SystemException("IMPLEMENT ME!");
		}
	}
	
	/**
	 * Validates permissions for given poll object.
	 * 
	 * @param	wcf\data\poll\Poll	$poll
	 */
	public function validatePermissions(Poll $poll) {
		$handler = $this->getHandler($poll->objectTypeID);
		if ($handler === false) {
			// accessing an unknown poll, e.g. from a different environment
			throw new PermissionDeniedException();
		}
		else if ($handler !== null) {
			try {
				$handler->validate($poll);
			}
			catch (\Exception $e) {
				throw new PermissionDeniedException();
			}
		}
	}
	
	/**
	 * Returns true, if current user can start a public poll.
	 * 
	 * @return	boolean
	 */
	public function canStartPublicPoll() {
		$handler = $this->getHandler(null, $this->objectType);
		if ($handler !== null) {
			return $handler->canStartPublicPoll();
		}
		
		return true;
	}
	
	/**
	 * Returns a list of polls including options and vote state for current user.
	 * 
	 * @param	array<integer>		$pollIDs
	 * @return	array<wcf\data\poll\Poll>
	 */
	public function getPolls(array $pollIDs) {
		$pollList = new PollList();
		$pollList->getConditionBuilder()->add("poll.pollID IN (?)", array($pollIDs));
		$pollList->sqlLimit = 0;
		$pollList->readObjects();
		$polls = $pollList->getObjects();
		
		// invalid poll ids
		if (empty($polls)) {
			return array();
		}
		
		// fetch options for every poll
		$optionList = $this->getPollOptions($pollIDs);
		
		// assign options to poll
		foreach ($optionList as $option) {
			$polls[$option->pollID]->addOption($option);
		}
		
		return $polls;
	}
	
	/**
	 * Returns a list of poll options with vote state for current user.
	 * 
	 * @param	array<integer>		$pollIDs
	 * @return	wcf\data\poll\option\PollOptionList
	 */
	public function getPollOptions(array $pollIDs) {
		$optionList = new PollOptionList();
		$optionList->getConditionBuilder()->add("poll_option.pollID IN (?)", array($pollIDs));
		$optionList->sqlLimit = 0;
		
		// check for user votes
		if (WCF::getUser()->userID) {
			$optionList->sqlSelects = "CASE WHEN poll_option_vote.optionID IS NULL THEN '0' ELSE '1' END AS voted";
			$optionList->sqlJoins = "LEFT JOIN wcf".WCF_N."_poll_option_vote poll_option_vote ON (poll_option_vote.optionID = poll_option.optionID AND poll_option_vote.userID = ".WCF::getUser()->userID.")";
		}
		else {
			$optionList->sqlSelects = "'0' AS voted";
		}
		
		$optionList->readObjects();
		
		return $optionList;
	}
	
	/**
	 * Returns the handler object for given object type. Returns false if object type (id)
	 * is not found, or null if no handler is assigned.
	 * 
	 * @param	integer		$objectTypeID
	 * @param	string		$objectType
	 * @return	mixed
	 */
	protected function getHandler($objectTypeID, $objectType = '') {
		if ($objectTypeID !== null) {
			foreach ($this->cache as $objectTypeObj) {
				if ($objectTypeObj->objectTypeID == $objectTypeID) {
					$objectType = $objectTypeObj->objectType;
					break;
				}
			}
		}
		
		if (!isset($this->cache[$objectType])) {
			return false;
		}
		
		// validates against object type's class
		if ($this->cache[$objectType]->classname !== null) {
			$className = $this->cache[$objectType]->classname;
			if (!ClassUtil::isInstanceOf($className, 'wcf\system\poll\IPollHandler')) {
				throw new SystemException("'".$className."' does not implement 'wcf\system\poll\IPollHandler'");
			}
			else if (!ClassUtil::isInstanceOf($className, 'wcf\system\SingletonFactory')) {
				throw new SystemException("'".$className."' does not extend 'wcf\system\SingletonFactory'");
			}
				
			$object = call_user_func(array($className, 'getInstance'));
			return $object;
		}
		
		return null;
	}
}
