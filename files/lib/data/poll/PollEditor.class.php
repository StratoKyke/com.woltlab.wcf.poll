<?php
namespace wcf\data\poll;
use wcf\data\DatabaseObjectEditor;
use wcf\system\WCF;

/**
 * Extends the poll object with functions to create, update and delete polls.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	data.poll
 * @category 	Community Framework
 */
class PollEditor extends DatabaseObjectEditor {
	/**
	 * @see	wcf\data\DatabaseObjectEditor::$baseClass
	 */
	protected static $baseClass = 'wcf\data\poll\Poll';
	
	/**
	 * Calculates poll votes.
	 */
	public function calculateVotes() {
		// update option votes
		$sql = "UPDATE	wcf".WCF_N."_poll_option poll_option
			SET	poll_option.votes = (
					SELECT	COUNT(*) AS count
					FROM	wcf".WCF_N."_poll_option_vote
					WHERE	optionID = poll_option.optionID
				)
			WHERE	poll_option.pollID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array($this->pollID));
	}
}
