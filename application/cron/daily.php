<?php
// include the cron bootstrap file
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

/**
 * Sends daily notifications to users.
 */
class Notifications {

    // option constants
    const EMAIL_LEADER_POST = 1;
    const EMAIL_COMMENT_REPLY = 2;
    const EMAIL_LEADER_COMMENT = 3;
    const EMAIL_PARTY_COMMENT = 4;
    const EMAIL_NOTIFY_FREQ = 5;

    // option type constants
    const EMAIL_CONFIG_ID = 1;
    const EMAIL_FREQ_ID = 2;

    // the database adapter
    protected $db;

    // the current time
    protected $time;

    // the default config options
    protected $defaults = array();

    /**
     * Default constructor.
     */
    public function __construct($db)
    {
        $this->time = time();
        $this->db   = $db;
    }

    public function init()
    {
        // get the set options for all users
        $this->getDailyUsers();
    }

    /**
     * Retrieve all users (with email settings) that requested to have
     * daily digest emails sent.
     *
     * @access  public
     */
    public function getDailyUsers()
    {
        // get the users with an email frequency of "daily"
        $sql = sprintf('SELECT users.id, users.email, users.firstname, users.lastname,
                        GROUP_CONCAT(settings_options.name, "|", user_settings_options.data SEPARATOR "||") AS settings
                        FROM options_types
                        INNER JOIN options ON (options_types.id = options.type_id)
                        INNER JOIN user_options ON (options.id = user_options.option_id)
                        INNER JOIN users ON (user_options.user_id = users.id)
                        INNER JOIN user_options AS user_settings_options ON (users.id = user_settings_options.user_id)
                        INNER JOIN options AS settings_options on (user_settings_options.option_id = settings_options.id)
                        INNER JOIN options_types AS settings_options_types ON (settings_options.type_id = settings_options_types.id)
                        WHERE options_types.id = %d
                        AND user_options.data = "daily"
                        AND users.email IS NOT NULL
                        AND settings_options_types.id = %d
                        GROUP BY users.id',
                        self::EMAIL_FREQ_ID,
                        self::EMAIL_CONFIG_ID);

        $query = $this->db->query($sql);
        $result = $query->fetchAll();
        if ($result) {

            // iterate over users with settings
            foreach ($result as $r) {

                // begin the query building process
                $where = '';

                // split into name => data pairings
                $settings = explode('||', $r['settings']);
                foreach ($settings as $s) {

                    // get the setting key and value
                    list($key, $val) = explode('|', $s);

                    // determine how to handle where clause based on key
                    if ($key == 'EMAIL_LEADER_POST') {

                        // watch for leader post of blog, vote, or petition
                        $union[] = sprintf('SELECT DISTINCT items.id as item_id, items.data, items.icon,
                                            items.created_ts, items.data_id, items.user_info,
                                            items.party_id, items.created_by, items.subtype,
                                            party.name AS party_name,
                                            item_types.label as type_label, item_types.type,
                                            users.email, "EMAIL_LEADER_POST" AS setting
                                            FROM users
                                            INNER JOIN users_party ON (users.id = users_party.user_id)
                                            INNER JOIN party ON (users_party.party_id = party.id)
                                            INNER JOIN items ON (party.id = items.party_id)
                                            INNER JOIN item_types ON (items.type_id = item_types.id)
                                            WHERE party.leader_id = %1$d
                                            AND items.created_ts BETWEEN %2$d AND %3$d
                                            AND items.type_id IN (%4$s)',
                                            $r['id'],
                                            $this->time - 3600*24, // one day ago
                                            $this->time,
                                            ItemTypes::BLOG . ',' . ItemTypes::ISSUES . ',' . ItemTypes::ENDORSEMENT . ',' . ItemTypes::PETITION);

                    } else if ($key == 'EMAIL_COMMENT_REPLY') {

                        $union[] = sprintf('SELECT DISTINCT items.id AS item_id, items.data, items.icon,
                                            items.created_ts, items.data_id, items.user_info,
                                            items.party_id, items.created_by, items.subtype,
                                            party.name AS party_name,
                                            item_types.label as type_label, item_types.type,
                                            users.email, "EMAIL_COMMENT_REPLY" AS setting
                                            FROM comments
                                            INNER JOIN comments AS theirs ON (comments.id = theirs.parent_id)
                                            INNER JOIN items ON (comments.id = items.data_id)
                                            INNER JOIN item_types ON (items.type_id = item_types.id)
                                            AND items.type_id = %1$d
                                            INNER JOIN users_party ON (theirs.created_by = users_party.user_id)
                                            AND theirs.party_id = users_party.party_id
                                            INNER JOIN party ON (users_party.party_id = party.id)
                                            INNER JOIN users ON (users_party.user_id = users.id)
                                            WHERE comments.created_by = %4$d
                                            AND theirs.created_ts BETWEEN %2$s AND %3$s
                                            AND theirs.created_by != %4$d
                                            AND theirs.created_ts > comments.created_ts',
                                            ItemTypes::PARTY_COMMENT,
                                            $this->time - 3600*24, // one day ago
                                            $this->time, // now
                                            $r['id']);

                    } else if ($key == 'EMAIL_LEADER_COMMENT') {

                        $union[] = sprintf('SELECT DISTINCT items.id as item_id, items.data, items.icon,
                                            items.created_ts, items.data_id, items.user_info,
                                            items.party_id, items.created_by, items.subtype,
                                            party.name AS party_name,
                                            item_types.label as type_label, item_types.type,
                                            users.email, "EMAIL_LEADER_COMMENT" AS setting
                                            FROM users
                                            INNER JOIN users_party ON (users.id = users_party.user_id)
                                            INNER JOIN party ON (users_party.party_id = party.id)
                                            INNER JOIN comments ON (party.id = comments.party_id)
                                            AND party.leader_id = comments.created_by
                                            INNER JOIN items ON (comments.id = items.data_id)
                                            INNER JOIN item_types ON (items.type_id = item_types.id)
                                            AND items.type_id = %d
                                            WHERE users_party.user_id = %d
                                            AND comments.created_ts BETWEEN %d AND %d',
                                            ItemTypes::PARTY_COMMENT,
                                            $r['id'],
                                            $this->time - 3600*24,
                                            $this->time);

                    } else if ($key == 'EMAIL_PARTY_COMMENT') {

                        $union[] = sprintf('SELECT DISTINCT items.id as item_id, items.data, items.icon,
                                            items.created_ts, items.data_id, items.user_info,
                                            items.party_id, items.created_by, items.subtype,
                                            party.name AS party_name,
                                            item_types.label as type_label, item_types.type,
                                            users.email, "EMAIL_PARTY_COMMENT" AS setting
                                            FROM users
                                            INNER JOIN users_party ON (users.id = users_party.user_id)
                                            INNER JOIN party ON (users_party.party_id = party.id)
                                            INNER JOIN comments ON (party.id = comments.party_id)
                                            INNER JOIN items ON (comments.id = items.data_id)
                                            INNER JOIN item_types ON (items.type_id = item_types.id)
                                            WHERE users_party.user_id = %d
                                            AND comments.created_ts BETWEEN %d AND %d
                                            AND items.type_id = %d',
                                            $r['id'],
                                            $this->time - 3600*24, // one day ago
                                            $this->time, // now
                                            ItemTypes::PARTY_COMMENT);

                    }

                }

                // query for user activity matching the given criteria
                if (!empty($union)) {

                    $query = $this->db->query(implode(' UNION ', $union) . ' ORDER BY created_ts DESC');
                    $activity = $query->fetchAll();
                    if ($activity) {
                        $this->sendActivityFeed($r['email'], $activity);
                    }

                }

            }
        }
    }

    /**
     * Sends out an activity feed containing all pertinent data.
     *
     * @access  public
     * @param   array   $to
     * @param   array   $activity
     */
    public function sendActivityFeed($to, $activity)
    {
        // generate the text email
        $text = new Zend_View();
        $text->setScriptPath(APPLICATION_PATH . '/views/scripts/emails/');
        $text->assign('activity', $activity);

        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/emails/');
        $html->assign('activity', $activity);

        // render view
        $text = $text->render('text-activity.phtml');
        $html = $html->render('html-activity.phtml');

        // create mail object and attempt send
        $ses = new Amazon_Ses_Simple();
        if (!$ses->send($to, 'Ruck.us Activity Updates ' . date('m-d-y'), $text, $html)) {
            error_log('Unable to send ruck.us activity updates.');
        }
    }

}

// send daily notifications
$notifications = new Notifications($db);
$notifications->init();