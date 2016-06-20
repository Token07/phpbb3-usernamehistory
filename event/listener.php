<?php

/**
*
* @package phpBB Extension - Username History
* @copyright (c) 2016 Token07
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace token07\usernamehistory\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $helper;
	protected $template;
	protected $config;
	protected $user;
	protected $db;

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'				=> 'load_language_on_setup',
			'core.memberlist_view_profile'	=> 'memberlist_view_profile'
		);
	}

	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\db\driver\factory $db, \phpbb\user $user)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->db = $db;
		$this->user = $user;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'token07/usernamehistory',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
	public function memberlist_view_profile($event)
	{
		$user_id = (int) $event['member']['user_id'];
		$query = 'SELECT log_time, log_data FROM ' . LOG_TABLE . ' WHERE log_operation = \'LOG_USER_UPDATE_NAME\' AND reportee_id = ' . $user_id . ' ORDER BY log_time DESC';
		
		$result = $this->db->sql_query_limit($query, 5);
		
		while ($row = $this->db->sql_fetchrow($result))
		{
			$log_data = unserialize($row['log_data']);
			
			if (!$log_data)
			{
				continue;
			}
			
			$history_entry = array(
				'CHANGED_FROM'	=> $log_data[0],
				'CHANGED_TO'	=> $log_data[1],
				'CHANGED_DATE'	=> $this->user->format_date($row['log_time'])
			);
			$this->template->assign_block_vars('usernamehistory', $history_entry);
		}
	}
}
